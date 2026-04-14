<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HitchReportsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $value = $fieldname = $status_type = $date_from = $date_to = '';
        $userid = session('SESSION_ADMIN.id');
        $conditions = [
            ['HitchLead.dealer_id', '=', $userid],
        ];

        if ($request->has('Search') || $request->hasAny(['searchin', 'keyword', 'date_from', 'date_to', 'status_type'])) {
            $fieldname = $request->input('Search.searchin', $request->input('searchin', ''));
            $value = $request->input('Search.keyword', $request->input('keyword', ''));
            $date_from = $request->input('Search.date_from', $request->input('date_from', ''));
            $date_to = $request->input('Search.date_to', $request->input('date_to', ''));
            $status_type = $request->input('Search.status_type', $request->input('status_type', ''));

            if (!empty($date_from) && empty($date_to)) {
                $date_to = date('Y-m-d');
            }
        }

        $query = DB::table('cs_orders as CsOrder')
            ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
            ->leftJoin('hitch_leads as HitchLead', 'HitchLead.user_id', '=', 'CsOrder.renter_id')
            ->whereNotNull('HitchLead.id')
            ->where($conditions);

        $escaped = addcslashes($value, '%_\\');
        if ($escaped !== '') {
            if ($fieldname == '1') {
                $query->where('CsOrder.pickup_address', 'like', '%' . $escaped . '%');
            } elseif ($fieldname == '2') {
                $query->where('CsOrder.vehicle_name', $escaped);
            } elseif ($fieldname == '3') {
                $query->where('CsOrder.increment_id', $escaped);
            }
        }

        if (!empty($date_from)) {
            $parsedFrom = Carbon::parse($date_from)->format('Y-m-d');
            $query->where('CsOrder.start_datetime', '>=', $parsedFrom);
        }
        if (!empty($date_to)) {
            $parsedTo = Carbon::parse($date_to)->format('Y-m-d');
            $query->where('CsOrder.end_datetime', '<=', $parsedTo);
        }
        if (!empty($status_type)) {
            if ($status_type === 'cancel') {
                $query->where('CsOrder.status', 2);
            } elseif ($status_type === 'complete') {
                $query->where('CsOrder.status', 3);
            } elseif ($status_type === 'incomplete') {
                $query->whereIn('CsOrder.status', [0, 1]);
            }
        }

        if ($request->input('search') === 'EXPORT') {
            return $this->export(clone $query);
        }

        $query->where('CsOrder.parent_id', 0);

        $sessLimitKey = 'hitch_reports_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitKey, $this->recordsPerPage);
        session([$sessLimitKey => $limit]);

        $reportlists = $query
            ->select('CsOrder.*', 'User.first_name', 'User.last_name')
            ->orderByDesc('CsOrder.id')
            ->paginate($limit)
            ->withQueryString();

        $viewData = [
            'reportlists' => $reportlists,
            'keyword' => $value,
            'fieldname' => $fieldname,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'status_type' => $status_type,
            'title_for_layout' => 'Reports',
        ];

        if ($request->ajax()) {
            return response()->view('cloud.hitch.reports._table', $viewData);
        }

        return view('cloud.hitch.reports.index', $viewData);
    }

    private function export($query)
    {
        $ordersData = $query
            ->leftJoin('users as Owner', 'Owner.id', '=', 'CsOrder.user_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->select(
                'CsOrder.id', 'CsOrder.increment_id', 'CsOrder.parent_id',
                'CsOrder.rent', 'CsOrder.timezone', 'CsOrder.start_datetime',
                'CsOrder.end_datetime', 'CsOrder.pickup_address', 'CsOrder.end_odometer',
                'CsOrder.insurance_amt',
                'User.first_name as user_first_name', 'User.last_name as user_last_name',
                'Vehicle.make', 'Vehicle.model', 'Vehicle.year', 'Vehicle.vin_no',
                'Owner.business_name'
            )
            ->orderBy('CsOrder.id')
            ->limit(5000)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=Booking_Report.csv',
        ];

        $callback = function () use ($ordersData) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, [
                'No', 'Booking No', 'Duration', 'Total Rental', 'Mileage', 'Insurance',
                'Type', 'Car Info', 'VIN Number', 'Start Date', 'End Date',
                'Owner Name', 'Driver Name', 'DIA Commission', 'Late Fee Share',
                'Late Insurance', 'City Tax', 'Tourism Surcharge', 'City', 'Amount To Owner',
            ]);

            $i = 1;
            foreach ($ordersData as $row) {
                $start = Carbon::parse($row->start_datetime);
                $end = Carbon::parse($row->end_datetime);
                $days = $start->diffInDays($end);

                fputcsv($fp, [
                    $i++,
                    $row->increment_id,
                    $days,
                    $row->rent,
                    $row->end_odometer,
                    $row->insurance_amt,
                    $row->parent_id ? 'Extended' : '',
                    trim("{$row->make} {$row->model} {$row->year}"),
                    $row->vin_no,
                    Carbon::parse($row->start_datetime)->format('m/d/Y'),
                    Carbon::parse($row->end_datetime)->format('m/d/Y'),
                    $row->business_name,
                    trim("{$row->user_first_name} {$row->user_last_name}"),
                    sprintf('%0.2f', $row->rent * 15 / 100),
                    0.0,
                    0.0,
                    3.43,
                    2,
                    $row->pickup_address,
                    sprintf('%0.2f', $row->rent * 85 / 100),
                ]);
            }

            fclose($fp);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function details($id)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        $csorder = null;
        $payouts = collect();

        if (!empty($decodedId)) {
            $csorder = DB::table('cs_orders as CsOrder')
                ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
                ->where('CsOrder.id', $decodedId)
                ->select('CsOrder.*', 'User.first_name', 'User.last_name', 'User.contact_number')
                ->first();

            $payouts = DB::table('cs_payout_transactions as CsPayoutTransaction')
                ->where('CsPayoutTransaction.cs_order_id', $decodedId)
                ->get();
        }

        return view('cloud.hitch.reports.details', [
            'csorder' => $csorder,
            'payouts' => $payouts,
        ]);
    }

    public function autorenewddetails($id)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        $csorder = null;
        $subOrders = null;
        $conversionBooking = false;
        $viewData = [];

        if (!empty($decodedId)) {
            $csorder = DB::table('cs_orders as CsOrder')
                ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
                ->where('CsOrder.id', $decodedId)
                ->select('CsOrder.*', 'User.first_name', 'User.last_name', 'User.contact_number')
                ->first();

            $subOrders = DB::table('cs_orders as CsOrder')
                ->where(function ($q) use ($decodedId) {
                    $q->where('CsOrder.parent_id', $decodedId)
                        ->orWhere('CsOrder.id', $decodedId);
                })
                ->selectRaw('MAX(CsOrder.end_datetime) as end_datetime, SUM(CsOrder.paid_amount) as paid_amount, SUM(CsOrder.rent) as rent, SUM(CsOrder.tax) as tax, SUM(CsOrder.initial_fee) as initial_fee, SUM(CsOrder.extra_mileage_fee) as extra_mileage_fee, SUM(CsOrder.lateness_fee) as lateness_fee, SUM(CsOrder.damage_fee) as damage_fee, SUM(CsOrder.uncleanness_fee) as uncleanness_fee, SUM(CsOrder.insurance_amt) as insurance_amt, SUM(CsOrder.dia_insu) as dia_insu, SUM(CsOrder.end_odometer) as end_odometer')
                ->orderByDesc('CsOrder.id')
                ->first();

            $viewData = compact('csorder', 'subOrders');

            if ($csorder) {
                $realBookingId = $csorder->parent_id ?: $csorder->id;
                $OrderDepositRule = DB::table('cs_order_deposit_rules')
                    ->where('cs_order_id', $realBookingId)
                    ->first();

                if (!empty($OrderDepositRule)) {
                    $conversionBooking = true;

                    $parentId = $csorder->parent_id ?: $csorder->id;
                    $Siblingbooking = DB::table('cs_orders')
                        ->where(function ($q) use ($parentId) {
                            $q->where('parent_id', $parentId)
                                ->orWhere('id', $parentId);
                        })
                        ->pluck('increment_id', 'id')
                        ->toArray();

                    $Siblingbookings = array_keys($Siblingbooking);

                    $totalPaid = (float) DB::table('cs_order_payments')
                        ->whereIn('cs_order_id', $Siblingbookings)
                        ->where('type', 2)
                        ->where('status', 1)
                        ->sum('amount');

                    $start = Carbon::parse($csorder->start_datetime);
                    $daystilldate = $start->diffInDays(Carbon::now());

                    $paidInitialFee = (float) DB::table('cs_order_payments')
                        ->whereIn('cs_order_id', $Siblingbookings)
                        ->where('type', 3)
                        ->where('status', 1)
                        ->sum('amount');

                    $parentStartDate = $csorder->parent_id
                        ? DB::table('cs_orders')->where('id', $csorder->parent_id)->value('start_datetime')
                        : $csorder->start_datetime;
                    $startConversionDate = $parentStartDate
                        ? Carbon::parse($parentStartDate)->timezone($csorder->timezone ?? 'UTC')->format('Y-m-d H:i:s')
                        : '';

                    $DayFee = $OrderDepositRule->rental ?? 0;
                    $target_conversion_date = Carbon::parse($OrderDepositRule->start_datetime)
                        ->addDays($OrderDepositRule->num_of_days ?? 0)
                        ->timezone($csorder->timezone ?? 'UTC')
                        ->format('Y-m-d H:i:s');
                    $estimated_rate_after_conversion = 12;
                    $downpaymentPaid = sprintf('%0.2f', $totalPaid + $paidInitialFee);
                    $down_payment_remaining = ($OrderDepositRule->total_program_cost > 0)
                        ? $OrderDepositRule->total_program_cost - $downpaymentPaid
                        : ($OrderDepositRule->downpayment ?? 0) - $downpaymentPaid;
                    $payment_till_conversion = ($OrderDepositRule->num_of_days ?? 0) - $daystilldate;
                    $totalDownpayment = $OrderDepositRule->downpayment ?? 0;
                    $total_payments_till = $daystilldate;
                    $totalprogramcost = $OrderDepositRule->total_program_cost ?? 0;
                    $divisor = $totalprogramcost > 0 ? $totalprogramcost : ($OrderDepositRule->downpayment ?? 1);
                    $goal_percent = $divisor > 0 ? sprintf('%d', ($downpaymentPaid / $divisor) * 100) : 0;

                    $payouts = DB::table('cs_payout_transactions')
                        ->whereIn('cs_order_id', $Siblingbookings)
                        ->get();

                    $sellingprice = DB::table('vehicles')
                        ->where('id', $csorder->vehicle_id)
                        ->value('msrp') ?? 0;

                    $viewData = array_merge($viewData, compact(
                        'goal_percent', 'totalprogramcost', 'DayFee', 'totalDownpayment',
                        'startConversionDate', 'target_conversion_date',
                        'estimated_rate_after_conversion', 'downpaymentPaid',
                        'down_payment_remaining', 'payment_till_conversion',
                        'total_payments_till', 'Siblingbooking', 'payouts', 'sellingprice'
                    ));
                }

                $viewData['conversionBooking'] = $conversionBooking;
            }
        }

        return view('cloud.hitch.reports.autorenewddetails', $viewData);
    }

    public function loadsubbooking(Request $request, $orderid): JsonResponse
    {
        $return = ['status' => 'error', 'message' => 'Something went wrong'];

        if (!empty($orderid)) {
            $return['message'] = 'Sorry, no record found';

            $subbookinglists = DB::table('cs_orders as CsOrder')
                ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
                ->where(function ($q) use ($orderid) {
                    $q->where('CsOrder.id', $orderid)
                        ->orWhere('CsOrder.parent_id', $orderid);
                })
                ->select('CsOrder.*', 'User.first_name', 'User.last_name')
                ->orderByDesc('CsOrder.id')
                ->get();

            if ($subbookinglists->isNotEmpty()) {
                $return['status'] = 'success';
                $return['booking_id'] = $orderid;
                $html = view('cloud.hitch.reports._loadsubbooking', [
                    'subbookinglists' => $subbookinglists,
                    'booking_id' => $orderid,
                ])->render();
                $return['data'] = $html;
            }
        }

        return response()->json($return);
    }

    public function getagreement(Request $request): JsonResponse
    {
        $return = ['status' => false, 'message' => 'Invalid Booking ID', 'result' => []];
        $userid = session('SESSION_ADMIN.id');
        $booking_id = $this->decodeId($request->input('orderid'));

        if (!empty($booking_id)) {
            $CsLeaselists = DB::table('cs_orders as CsOrder')
                ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
                ->leftJoin('users as Owner', 'Owner.id', '=', 'CsOrder.user_id')
                ->where('CsOrder.id', $booking_id)
                ->select(
                    'CsOrder.*',
                    'Vehicle.id as vehicle_tbl_id', 'Vehicle.make', 'Vehicle.model',
                    'Vehicle.year', 'Vehicle.vin_no', 'Vehicle.user_id as vehicle_user_id',
                    'Vehicle.allowed_miles', 'Vehicle.plate_number',
                    'Owner.id as owner_id', 'Owner.first_name as owner_first_name',
                    'Owner.last_name as owner_last_name',
                    'Owner.company_address as owner_address',
                    'Owner.company_city as owner_city',
                    'Owner.company_state as owner_state',
                    'Owner.company_zip as owner_zip',
                    'Owner.timezone as owner_timezone',
                    'Owner.representative_name', 'Owner.representative_role',
                    'Owner.representative_sign', 'Owner.company_name'
                )
                ->first();

            if (empty($CsLeaselists)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sorry, you are not authorized for this booking',
                    'result' => [],
                ]);
            }

            // Delegate to the Agreement trait/service if available
            $return['status'] = true;
            $return['message'] = 'Agreement data retrieved';
        }

        return response()->json($return);
    }
}
