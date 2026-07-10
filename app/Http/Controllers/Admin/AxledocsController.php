<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\PenaltyInsuranceTrait;
use App\Http\Controllers\Traits\PolicyValidateTrait;
use App\Http\Controllers\Traits\ValidateInsuranceTrait;
use App\Services\Legacy\AxleService;
use App\Services\Legacy\MeasureOneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\AxleStatus;


class AxledocsController extends LegacyAppController
{
    use PolicyValidateTrait, PenaltyInsuranceTrait, ValidateInsuranceTrait;

    public function index(Request $request)
    {
        $title = 'Axle Connected Insurance Report';
        $sessLimitName = "axledocs_limit";

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } elseif (session()->has($sessLimitName)) {
            $limit = session($sessLimitName);
        } else {
            $limit = $this->recordsPerPage ?? 50;
        }

        $query = OrderDepositRule::select([
            'axle_status.*',
            'cs_orders.id as cs_order_id',
            'cs_orders.increment_id',
            'cs_orders.vehicle_name',
            'cs_orders.start_datetime',
            'cs_orders.end_datetime',
            'cs_orders.timezone',
            'cs_orders.renter_id',
            'cs_order_deposit_rules.vehicle_reservation_id',
            'cs_order_deposit_rules.id as order_deposit_rule_id'
        ])
            ->leftJoin('axle_status', 'axle_status.order_id', '=', 'cs_order_deposit_rules.id')
            ->leftJoin('cs_orders', function ($join) {
                $join->on('cs_orders.status', '=', \DB::raw(1))
                    ->where(function ($query) {
                        $query->on('cs_orders.id', '=', 'cs_order_deposit_rules.cs_order_id')
                            ->orOn('cs_orders.parent_id', '=', 'cs_order_deposit_rules.cs_order_id');
                    });
            })
            ->whereNotNull('cs_orders.id')
            ->whereIn('cs_order_deposit_rules.insurance_payer', [0, 1, 2, 3, 4, 5, 6, 7])
            ->orderBy('cs_order_deposit_rules.id', 'DESC');

        $records = $query->paginate($limit);
        $policyStatus = AxleService::$PolicyStatus;

        if ($request->ajax()) {
            return view('admin.axle.elements._index', compact('records', 'policyStatus', 'limit'));
        }

        return view('admin.axle.index', compact('records', 'policyStatus', 'title', 'limit'));
    }
    public function singleload(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $orderid = $request->input('orderid');
        $record = DB::table('cs_order_deposit_rules as OrderDepositRule')
            ->leftJoin('axle_status as AxleStatus', 'AxleStatus.order_id', '=', 'OrderDepositRule.id')
            ->leftJoin('cs_orders as CsOrder', function ($join) {
                $join->where('CsOrder.status', 1)
                    ->where(function ($q) {
                        $q->whereColumn('CsOrder.id', 'OrderDepositRule.cs_order_id')
                            ->orWhereColumn('CsOrder.parent_id', 'OrderDepositRule.cs_order_id');
                    });
            })
            ->whereNotNull('CsOrder.id')
            ->where('OrderDepositRule.id', $orderid)
            ->whereIn('OrderDepositRule.insurance_payer', [0, 1, 2, 3, 4, 5, 6, 7])
            ->select(
                'AxleStatus.*',
                'CsOrder.id as cs_order_id_val',
                'CsOrder.increment_id',
                'CsOrder.vehicle_name',
                'CsOrder.start_datetime',
                'CsOrder.end_datetime',
                'CsOrder.timezone',
                'CsOrder.renter_id',
                'OrderDepositRule.vehicle_reservation_id',
                'OrderDepositRule.id as order_deposit_rule_id'
            )
            ->first();

        $policyStatus = AxleService::$PolicyStatus;
        return view('admin.axle._single', compact('record', 'policyStatus'));
    }
    public function connect($orderid)
    {
        $axleStatusObj = AxleStatus::where('order_id', $orderid)->first();

        if (empty($axleStatusObj) || !in_array($axleStatusObj->axle_status, [1, 2])) {
            $odr = DB::table('cs_order_deposit_rules as OrderDepositRule')
                ->leftJoin('vehicle_reservations as VehicleReservation', 'VehicleReservation.id', '=', 'OrderDepositRule.vehicle_reservation_id')
                ->leftJoin('users as Renter', 'Renter.id', '=', 'VehicleReservation.renter_id')
                ->where('OrderDepositRule.id', $orderid)
                ->select('OrderDepositRule.id', 'Renter.id as renter_id', 'Renter.first_name', 'Renter.last_name')
                ->first();

            if (!$odr) {
                return redirect()->back()->with('error', 'No record found!');
            }

            $dataToPass = [
                "order_id" => $odr->id,
                'renter_id' => $odr->renter_id,
                'first_name' => $odr->first_name,
                'last_name' => $odr->last_name,
                'x-access-token' => $axleStatusObj->access_token ?? '',
            ];

            $resp = (new AxleService())->startIgnition($dataToPass);

            if (isset($resp['success']) && $resp['success'] == 1) {
                return redirect($resp['data']['ignitionUri']);
            }

            return redirect()->back()->with('error', 'Failed to start Axle ignition');
        }

        return redirect()->back()->with('error', 'Sorry, this record already connected with Axle');
    }
    public function accountDetails(Request $request)
    {
        $return = [
            "success" => false,
            "message" => "Sorry, seems policy is not active"
        ];

        if ($request->isMethod('post')) {
            $orderid = $request->input('orderid');
            $axleStatusObj = AxleStatus::where('order_id', $orderid)->first();

            if (!empty($axleStatusObj) && $axleStatusObj->type == 'measureone') {
                $return['message'] = "Sorry, this is not an Axle connected policy, so account details cannot be fetched";
                return response()->json($return);
            }

            if (!empty($axleStatusObj) && !empty($axleStatusObj->account_id)) {
                $axleObj = (new AxleService())->fetchAccountDetails($axleStatusObj->access_token, $axleStatusObj->account_id);

                if ($axleObj['success'] ?? false) {
                    $policy = current($axleObj['data']['policies']);
                    AxleStatus::where('id', $axleStatusObj->id)->update(['policy' => $policy]);
                    $return['success'] = true;
                    $return['message'] = "Account details fetched successfully";
                } else {
                    $return['message'] = $axleObj['message'] ?? 'Unknown error';
                }
            }
        }

        return response()->json($return);
    }
    public function policyDetails(Request $request)
    {
        $return = [
            "success" => false,
            "message" => "Sorry, seems policy is not active"
        ];

        if ($request->isMethod('post')) {
            $orderid = $request->input('orderid');
            $axleObj = [];
            $axleStatusObj = AxleStatus::where('order_id', $orderid)->first();
            $axleStatusArr = $axleStatusObj ? $axleStatusObj->toArray() : [];

            if (!empty($axleStatusObj) && !empty($axleStatusObj->policy) && $axleStatusObj->type == 'axle') {
                $axleObj = (new AxleService())->fetchPolicyDetails($axleStatusObj->access_token, $axleStatusObj->policy);

                if (!($axleObj['success'] ?? false) && $axleStatusObj->axle_status != 0) {
                    $axleStatusObj->axle_status = 3;
                }

                if (($axleObj['success'] ?? false) && $axleStatusObj->axle_status != 0) {
                    $axleStatusObj->axle_status = ($axleObj['data']['isActive'] ?? false) == true ? 2 : 3;
                }

                if ($axleObj['success'] ?? false) {
                    $axleStatusObj->policy_details = json_encode([
                        'policy_number' => $axleObj['data']['policyNumber'] ?? '',
                        'provider' => $axleObj['data']['carrier'] ?? '',
                        'start_date' => date('Y-m-d H:i:s', strtotime($axleObj['data']['effectiveDate'] ?? 'now')),
                        'end_date' => date('Y-m-d H:i:s', strtotime($axleObj['data']['expirationDate'] ?? 'now')),
                        'premium' => $axleObj['data']['premium'] ?? '',
                    ]);
                }

                $axleStatusObj->save();

                if ($axleStatusObj->axle_status != 0) {
                    $this->_convertBookingInsuranceTypeIfPolicyExpired($axleObj['data'] ?? [], $axleStatusArr);
                }
            }

            if (!empty($axleStatusObj) && !empty($axleStatusObj->policy) && $axleStatusObj->type == 'measureone') {
                $transactionId = $axleStatusObj->access_token ?? '';
                $policyStatus = (new MeasureOneService())->getInsuranceDetails(["transaction_id" => $transactionId]);
                $insuranceDetails = [];

                if (!$policyStatus['status']) {
                    $axleStatusObj->axle_status = 3;
                }

                if ($policyStatus['status'] && ($policyStatus['result']['processing_status'] ?? '') == "COMPLETED") {
                    $insuranceDetails = $this->insuranceDetails($axleStatusObj->policy, $policyStatus['result']['insurance_details'] ?? []);
                    $axleStatusObj->axle_status = ($policyStatus['result']['insurance_details']['status'] ?? '') == 'ACTIVE' ? 2 : 3;
                    $axleStatusObj->policy_details = json_encode([
                        'policy_number' => $insuranceDetails['policy_number'] ?? '',
                        'provider' => $insuranceDetails['insurance_provider']['name'] ?? '',
                        'start_date' => isset($insuranceDetails['coverage_period']['start_date']) ? date('Y-m-d H:i:s', $insuranceDetails['coverage_period']['start_date'] / 1000) : '',
                        'end_date' => isset($insuranceDetails['coverage_period']['end_date']) ? date('Y-m-d H:i:s', $insuranceDetails['coverage_period']['end_date'] / 1000) : '',
                        'premium' => $insuranceDetails['premium_amount']['amount'] ?? '',
                    ]);
                }

                $axleStatusObj->save();

                if ($axleStatusObj->axle_status != 0 && !empty($insuranceDetails)) {
                    $this->validateInsurance($insuranceDetails, $axleStatusArr);
                }

                $axleObj['data'] = $insuranceDetails;
                $axleObj['success'] = true;
            }

            $return['html'] = view('admin.axle._policy', compact('axleObj'))->render();
        }

        return response()->json($return);
    }
    public function policyDetailsPopup(Request $request)
    {
        $return = [
            "success" => false,
            "message" => "Sorry, seems policy is not active"
        ];

        if ($request->isMethod('post')) {
            $orderid = $request->input('orderid');
            $axleStatusObj = AxleStatus::where('order_id', $orderid)->first();
            $policychecks = json_decode(!empty($axleStatusObj->extra) ? $axleStatusObj->extra : '{}', true);
            $calculatedInsurance = 0;

            if (in_array($axleStatusObj->axle_status ?? 0, [3, 4]) && ($axleStatusObj->expired_on ?? '') < date('Y-m-d')) {
                $odr = OrderDepositRule::where('id', $orderid)
                    ->select('id', 'cs_order_id', 'insurance')
                    ->first();
                $days = (int) ((strtotime(date('Y-m-d')) - strtotime($axleStatusObj->expired_on)) / 86400);
                $totalInsurance = sprintf('%0.2f', ($days * $odr->insurance));
                $calculatedInsurance = sprintf('%0.2f', ($totalInsurance - ($axleStatusObj->calculated_insurance ?? 0)));
            }

            $checklist = AxleService::$rules;
            $axleStatusArr = $axleStatusObj->toArray();

            $return['html'] = view('admin.axle._policypopup', compact(
                'axleStatusArr',
                'policychecks',
                'orderid',
                'checklist',
                'calculatedInsurance'
            ))->render();
        }

        return response()->json($return);
    }
    public function policysave(Request $request)
    {
        $return = [
            "success" => false,
            "message" => "Sorry, seems policy is not active",
            'orderid' => 0
        ];

        if ($request->isMethod('post')) {
            $id = $request->input('AxleStatus.id');
            $axleStatusObj = AxleStatus::find($id);

            if (!empty($axleStatusObj)) {
                $extra = $request->input('AxleStatus.extra', []);
                $axleStatusObj->update(['extra' => json_encode($extra)]);
                $return = [
                    "success" => true,
                    "message" => "Your request is saved successfully",
                    'orderid' => $axleStatusObj->order_id
                ];

                $insurancePenalty = $request->input('AxleStatus.insurance_penalty', 0);

                if ($insurancePenalty > 0) {
                    $odr = OrderDepositRule::with('csOrder:id,renter_id,user_id')
                        ->where('id', $axleStatusObj->order_id)
                        ->select('id', 'cs_order_id', 'insurance')
                        ->first();

                    $orderObj = [
                        'renter_id' => $odr->renter_id,
                        'user_id' => $odr->user_id,
                        'day_insurance' => $odr->insurance,
                        'deposit_rule_id' => $axleStatusObj->order_id,
                    ];

                    $this->savePenaltyInsurance($orderObj, $insurancePenalty);
                }
            }
        }

        return response()->json($return);
    }
    public function acceptsave(Request $request)
    {
        $return = [
            "success" => false,
            "message" => "Sorry, seems policy is not active",
            'orderid' => 0
        ];

        if ($request->isMethod('post')) {
            $id = $request->input('AxleStatus.id');
            $axleStatusObj = AxleStatus::find($id);

            if ($axleStatusObj) {
                $extra = $request->input('AxleStatus.extra', []);
                $axleStatusObj->update([
                    'axle_status' => 5,
                    'expired_on' => null,
                    'calculated_insurance' => 0,
                    'extra' => json_encode($extra),
                ]);
                $return = [
                    "success" => true,
                    "message" => "Your request is saved successfully",
                    'orderid' => $axleStatusObj->order_id
                ];
            }
        }

        return response()->json($return);
    }
    public function disconnect(Request $request)
    {
        $return = ["success" => false, "message" => "Sorry, seems policy is not active", 'orderid' => 0];

        if ($request->isMethod('post')) {
            $orderid = $request->input('orderid');
            $axleStatusObj = AxleStatus::where('order_id', $orderid)->first();

            if (!empty($axleStatusObj)) {
                $terminate = (new AxleService())->terminateToken($axleStatusObj->access_token);

                if (!($terminate['success'] ?? false)) {
                    $return['message'] = $terminate['message'] ?? 'Failed to terminate';
                    return response()->json($return);
                }

                AxleStatus::where('id', $axleStatusObj->id)->update([
                    'axle_status' => 0,
                    'expired_on' => null,
                    'calculated_insurance' => 0,
                    'extra' => json_encode('[]'),
                    'policy' => null,
                    'access_token' => null,
                    'account_id' => null,
                ]);

                $return = [
                    "success" => true,
                    "message" => "Your request is saved successfully",
                    'orderid' => $axleStatusObj->order_id
                ];
            }
        }

        return response()->json($return);
    }
}
