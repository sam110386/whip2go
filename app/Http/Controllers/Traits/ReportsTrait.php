<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\OrderExtlog;
use App\Models\Legacy\PromoTerm;
use App\Models\Legacy\PromotionRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait ReportsTrait
{
    use CommonTrait, ActiveBookingTotalPending;

    /**
     * _getBookingReportsQuery: Common query for booking reports
     */
    protected function _getBookingReportsQuery(Request $request, array $extraConditions = [])
    {
        $query = CsOrder::query()
            ->from('cs_orders as CsOrder')
            ->leftJoin('users as Driver', 'Driver.id', '=', 'CsOrder.renter_id')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'CsOrder.user_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->select(
                'CsOrder.*',
                'Driver.first_name as driver_first',
                'Driver.last_name as driver_last',
                'Owner.business_name as owner_business',
                'Owner.first_name as owner_first',
                'Owner.last_name as owner_last',
                'Vehicle.vehicle_name',
                'Vehicle.make',
                'Vehicle.model',
                'Vehicle.year',
                'Vehicle.vin_no'
            );

        // Apply filters
        if ($request->filled('Search.keyword')) {
            $keyword = $request->input('Search.keyword');
            $searchIn = $request->input('Search.searchin');
            if ($searchIn == 1)
                $query->where('CsOrder.pickup_address', 'LIKE', "%$keyword%");
            elseif ($searchIn == 2)
                $query->where('CsOrder.vehicle_name', 'LIKE', "%$keyword%");
            elseif ($searchIn == 3)
                $query->where('CsOrder.increment_id', 'LIKE', "%$keyword%");
        }

        if ($request->filled('Search.date_from')) {
            $query->where('CsOrder.start_datetime', '>=', Carbon::parse($request->input('Search.date_from'))->toDateTimeString());
        }
        if ($request->filled('Search.date_to')) {
            $query->where('CsOrder.end_datetime', '<=', Carbon::parse($request->input('Search.date_to'))->toDateTimeString());
        }

        if ($request->filled('Search.status_type')) {
            $status = $request->input('Search.status_type');
            if ($status == 'cancel')
                $query->where('CsOrder.status', 2);
            elseif ($status == 'complete')
                $query->where('CsOrder.status', 3);
            elseif ($status == 'incomplete')
                $query->whereIn('CsOrder.status', [0, 1]);
        }

        foreach ($extraConditions as $col => $val) {
            $query->where($col, $val);
        }

        return $query;
    }

    /**
     * _getVehicleReportsQuery: Aggregation by vehicle
     */
    protected function _getVehicleReportsQuery(Request $request, array $extraConditions = [])
    {
        $query = Vehicle::query()
            ->from('vehicles as Vehicle')
            ->leftJoin('cs_orders as CsOrder', function ($join) {
                $join->on('CsOrder.vehicle_id', '=', 'Vehicle.id')
                    ->where('CsOrder.status', 3);
            })
            ->select(
                'Vehicle.id',
                'Vehicle.vehicle_name',
                'Vehicle.msrp',
                'Vehicle.created as created_at',
                DB::raw('SUM(CsOrder.rent + CsOrder.initial_fee + CsOrder.damage_fee + CsOrder.uncleanness_fee) as totalrent'),
                DB::raw('SUM(CsOrder.end_odometer - CsOrder.start_odometer) as mileage'),
                DB::raw('SUM(DATEDIFF(CsOrder.end_datetime, CsOrder.start_datetime)) as totaldays'),
                DB::raw('SUM(CsOrder.extra_mileage_fee) as extra_mileage_fee')
            )
            ->groupBy('Vehicle.id', 'Vehicle.vehicle_name', 'Vehicle.msrp', 'Vehicle.created');

        if ($request->filled('Search.date_from')) {
            $query->where('CsOrder.end_datetime', '>=', Carbon::parse($request->input('Search.date_from'))->toDateTimeString());
        }
        if ($request->filled('Search.date_to')) {
            $query->where('CsOrder.end_datetime', '<=', Carbon::parse($request->input('Search.date_to'))->toDateTimeString());
        }

        foreach ($extraConditions as $col => $val) {
            $query->where($col, $val);
        }

        return $query;
    }

    private function _details($id)
    {
        $orderId = is_numeric($id) ? (int) $id : (int) base64_decode((string) $id);
        if (empty($orderId)) {
            return response('<p>Sorry no order found.</p>', 404);
        }

        $orderRow = CsOrder::with(['renter'])->find($orderId);
        if (!$orderRow) {
            return response('<p>Sorry no order found.</p>', 404);
        }

        $csorder = [
            'CsOrder' => $orderRow->toArray(),
            'User' => [
                'first_name' => $orderRow->renter->first_name ?? '',
                'last_name' => $orderRow->renter->last_name ?? '',
                'contact_number' => $orderRow->renter->contact_number ?? '',
            ]
        ];

        $Siblingbooking = [$orderRow->id => $orderRow->increment_id];
        $realBookingId = $orderRow->parent_id ?: $orderRow->id;

        $ruleRow = OrderDepositRule::where('cs_order_id', $realBookingId)->first();
        $OrderDepositRule = $ruleRow ? ['OrderDepositRule' => $ruleRow->toArray()] : [];

        if (!empty($OrderDepositRule)) {
            $revshareRow = RevSetting::where('user_id', $orderRow->user_id)->first();
            $revshare = ($revshareRow && !empty($revshareRow->rental_rev)) ? $revshareRow->rental_rev : config('legacy.owner_part', 85);
            $diAFee = (100 - $revshare * 1);

            $paymentsData = CsOrderPayment::where('cs_order_id', $orderId)
                ->where('status', 1)
                ->where('type', '!=', 1)
                ->get();

            $totalPaid = $paidInitialFee = $totalGrandPaid = $totalDiaFee = $dealerPaidInsurance = 0;
            if ($paymentsData->isNotEmpty()) {
                foreach ($paymentsData as $payment) {
                    $payerId = $payment->payer_id;
                    if (empty($payerId) || $payerId == $orderRow->renter_id) {
                        $totalGrandPaid += $payment->amount;
                    } else {
                        $dealerPaidInsurance += $payment->amount;
                    }

                    if (in_array($payment->type, [2, 16])) {
                        $totalPaid += ($payment->amount - $payment->tax - $payment->dia_fee);
                        $totalDiaFee += (($payment->amount - $payment->tax - $payment->dia_fee) * $diAFee / 100);
                    }
                    if (in_array($payment->type, [3])) {
                        $paidInitialFee += ($payment->amount - $payment->tax);
                        $totalDiaFee += (($payment->amount - $payment->tax - $payment->dia_fee) * $diAFee / 100);
                    }
                }
            }
            $downpaymentPaid = $totalPaid + $paidInitialFee;

            $extlogsRows = OrderExtlog::query()
                ->from('cs_order_extlogs as el')
                ->leftJoin('users as ow', 'ow.id', '=', 'el.owner')
                ->where('el.cs_order_id', $orderId)
                ->orderByDesc('el.id')
                ->select('el.*', 'ow.first_name as owner_first_name', 'ow.last_name as owner_last_name')
                ->get();

            $extlogs = [];
            foreach ($extlogsRows as $r) {
                $ownerFn = $r->owner_first_name ?? '';
                $ownerLn = $r->owner_last_name ?? '';
                $a = $r->toArray();
                unset($a['owner_first_name'], $a['owner_last_name']);
                $extlogs[] = [
                    'OrderExtlog' => $a,
                    'Owner' => ['first_name' => $ownerFn, 'last_name' => $ownerLn],
                ];
            }

            $calculation = [];
            if (!empty($OrderDepositRule['OrderDepositRule']['calculation'])) {
                $calc = $OrderDepositRule['OrderDepositRule']['calculation'];
                $calculation = is_string($calc) ? json_decode($calc, true) : $calc;
            }

            $insurance_payer = app(\App\Services\Legacy\Common::class)->getInsurancePayer($OrderDepositRule['OrderDepositRule']['insurance_payer'] ?? null);

            $promoRow = PromoTerm::query()
                ->from('promo_terms as pt')
                ->join('promotion_rules as pr', 'pr.id', '=', 'pt.promo_rule_id')
                ->where('pt.user_id', $orderRow->renter_id)
                ->where('pr.status', 1)
                ->select(['pr.*', 'pt.id as promo_term_id'])
                ->first();
            $Promo = $promoRow ? ['PromotionRule' => $promoRow->toArray()] : null;

            $payments = [];
            foreach ($paymentsData as $p) {
                $payments[$p->type][$p->id] = $p->toArray();
            }

            $paymentTypeValue = app(\App\Services\Legacy\Common::class)->getPayoutTypeValue(true);

            return view('reports._details', compact(
                'csorder',
                'Siblingbooking',
                'downpaymentPaid',
                'payments',
                'extlogs',
                'calculation',
                'insurance_payer',
                'totalGrandPaid',
                'OrderDepositRule',
                'totalDiaFee',
                'Promo',
                'dealerPaidInsurance',
                'paymentTypeValue'
            ));
        }

        return response('<p>Sorry no order found.</p>', 404);
    }

    /**
     * _loadSubBookings: Get all extensions for a booking
     */
    protected function _loadSubBookings($orderId)
    {
        return CsOrder::where('id', $orderId)
            ->orWhere('parent_id', $orderId)
            ->orderBy('id', 'DESC')
            ->get();
    }

    /**
     * _getPaymentsData: Get all payments for a booking
     */
    protected function _getPaymentsData($orderId)
    {
        return CsOrderPayment::where('cs_order_id', $orderId)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->get();
    }

    /**
     * _exportReportsCsv: Standardized CSV export
     */
    protected function _exportReportsCsv($ordersData)
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=Booking_Report.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($ordersData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ["No", "Booking No", "Duration", "Total Rental", "Mileage", "Insurance", "Type", "Car Info", "VIN Number", "Start Date", "End Date", "Owner Name", "Driver Name", "DIA Commission", "Amount To Owner"]);

            foreach ($ordersData as $index => $row) {
                $start = Carbon::parse($row->start_datetime);
                $end = Carbon::parse($row->end_datetime);
                $duration = $start->diffInDays($end);

                fputcsv($file, [
                    $index + 1,
                    $row->increment_id,
                    $duration . " Days",
                    $row->rent,
                    $row->end_odometer,
                    $row->insurance_amt,
                    $row->parent_id ? "Extended" : "New",
                    $row->make . " " . $row->model . " " . $row->year,
                    $row->vin_no,
                    $start->format('m/d/Y'),
                    $end->format('m/d/Y'),
                    $row->owner_business ?: ($row->owner_first . " " . $row->owner_last),
                    $row->driver_first . " " . $row->driver_last,
                    sprintf("%0.2f", ($row->rent * 0.15)),
                    sprintf("%0.2f", ($row->rent * 0.85))
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    private function _autorenewddetails($id)
    {
        $orderId = $this->decodeId((string) $id);

        if (empty($orderId)) {
            return response('<p>Sorry no order found.</p>', 404);
        }

        $orderRow = CsOrder::with(['renter'])->find($orderId);
        if (!$orderRow) {
            return response('<p>Sorry no order found.</p>', 404);
        }

        $csorder = [
            'CsOrder' => $orderRow->toArray(),
            'User' => [
                'first_name' => $orderRow->renter->first_name ?? '',
                'last_name' => $orderRow->renter->last_name ?? '',
                'contact_number' => $orderRow->renter->contact_number ?? '',
            ]
        ];

        $lastOrderObj = $orderRow;
        $agg = CsOrder::where(function ($q) use ($orderId) {
                $q->where('parent_id', $orderId)->orWhere('id', $orderId);
            })
            ->whereIn('status', [2, 3])
            ->selectRaw('
                COALESCE(SUM(rent),0) as rent, COALESCE(SUM(dia_fee),0) as dia_fee, 
                COALESCE(SUM(tax + emf_tax),0) as tax, COALESCE(SUM(initial_fee),0) as initial_fee, 
                COALESCE(SUM(initial_fee_tax),0) as initial_fee_tax, COALESCE(SUM(extra_mileage_fee),0) as extra_mileage_fee, 
                COALESCE(SUM(lateness_fee),0) as lateness_fee, COALESCE(SUM(discount),0) as discount, 
                COALESCE(SUM(damage_fee),0) as damage_fee, COALESCE(SUM(uncleanness_fee),0) as uncleanness_fee, 
                COALESCE(SUM(insurance_amt),0) as insurance_amt, COALESCE(SUM(dia_insu),0) as dia_insu, 
                COALESCE(SUM(toll),0) as toll, COALESCE(SUM(pending_toll),0) as pending_toll, 
                COALESCE(SUM(end_odometer),0) as end_odometer, COALESCE(SUM(initial_discount),0) as initial_discount
            ')->first();

        $subOrders = [];
        if ($agg) {
            $subOrders[0] = $agg->toArray();
        } else {
            $subOrders[0] = [
                'rent' => 0,
                'dia_fee' => 0,
                'tax' => 0,
                'initial_fee' => 0,
                'initial_fee_tax' => 0,
                'extra_mileage_fee' => 0,
                'lateness_fee' => 0,
                'discount' => 0,
                'damage_fee' => 0,
                'uncleanness_fee' => 0,
                'insurance_amt' => 0,
                'dia_insu' => 0,
                'toll' => 0,
                'pending_toll' => 0,
                'end_odometer' => 0,
                'initial_discount' => 0
            ];
        }

        if ($orderRow->status == 3) {
            $child = CsOrder::where('parent_id', $orderId)->orderByDesc('id')->first();
            if ($child) {
                $lastOrderObj = $child;
            }
        }
        $lastOrder = ['CsOrder' => $lastOrderObj->toArray()];

        $realBookingId = $orderRow->parent_id ?: $orderRow->id;

        $ruleRow = OrderDepositRule::where('cs_order_id', $realBookingId)->first();
        $OrderDepositRule = $ruleRow ? ['OrderDepositRule' => $ruleRow->toArray()] : [];

        $Siblingbooking = CsOrder::where('parent_id', $orderRow->id)
            ->orWhere('id', $orderRow->id)
            ->pluck('increment_id', 'id')
            ->toArray();

        $Siblingbookings = array_keys($Siblingbooking);

        $revshareRow = RevSetting::where('user_id', $lastOrder['CsOrder']['user_id'])->first();
        $revshare = ($revshareRow && !empty($revshareRow->rental_rev)) ? $revshareRow->rental_rev : config('legacy.owner_part', 85);
        $diAFee = (100 - $revshare * 1);

        $paymentsData = CsOrderPayment::whereIn('cs_order_id', empty($Siblingbookings) ? [0] : $Siblingbookings)
            ->where('status', 1)
            ->where('type', '!=', 1)
            ->select(['id', 'rent', 'amount', 'tax', 'dia_fee', 'type', 'payer_id', 'charged_at'])
            ->get();

        $totalPaid = $paidInitialFee = $totalGrandPaid = $totalDiaFee = $dealerPaidInsurance = 0;
        if ($paymentsData->isNotEmpty()) {
            foreach ($paymentsData as $payment) {
                $payerId = $payment->payer_id;
                if (empty($payerId) || $payerId == $orderRow->renter_id) {
                    $totalGrandPaid += $payment->amount;
                } else {
                    $dealerPaidInsurance += $payment->amount;
                }

                if (in_array($payment->type, [2, 16])) {
                    $totalPaid += ($payment->amount - $payment->tax - $payment->dia_fee);
                    $totalDiaFee += (($payment->amount - $payment->tax - $payment->dia_fee) * $diAFee / 100);
                }
                if (in_array($payment->type, [3])) {
                    $paidInitialFee += ($payment->amount - $payment->tax);
                    $totalDiaFee += (($payment->amount - $payment->tax - $payment->dia_fee) * $diAFee / 100);
                }
            }
        }
        $downpaymentPaid = $totalPaid + $paidInitialFee;

        $extlogsRows = OrderExtlog::query()
            ->from('cs_order_extlogs as el')
            ->leftJoin('users as ow', 'ow.id', '=', 'el.owner')
            ->whereIn('el.cs_order_id', empty($Siblingbookings) ? [0] : $Siblingbookings)
            ->orderByDesc('el.id')
            ->select('el.*', 'ow.first_name as owner_first_name', 'ow.last_name as owner_last_name')
            ->get();

        $extlogs = [];
        foreach ($extlogsRows as $r) {
            $ownerFn = $r->owner_first_name ?? '';
            $ownerLn = $r->owner_last_name ?? '';
            $a = $r->toArray();
            unset($a['owner_first_name'], $a['owner_last_name']);
            $extlogs[] = [
                'OrderExtlog' => $a,
                'Owner' => ['first_name' => $ownerFn, 'last_name' => $ownerLn],
            ];
        }

        $calculation = [];
        if (!empty($OrderDepositRule['OrderDepositRule']['calculation'])) {
            $calc = $OrderDepositRule['OrderDepositRule']['calculation'];
            $calculation = is_string($calc) ? json_decode($calc, true) : $calc;
        }

        $insurance_payer = app(\App\Services\Legacy\Common::class)->getInsurancePayer($OrderDepositRule['OrderDepositRule']['insurance_payer'] ?? null);

        $promoRow = PromoTerm::query()
            ->from('promo_terms as pt')
            ->join('promotion_rules as pr', 'pr.id', '=', 'pt.promo_rule_id')
            ->where('pt.user_id', $orderRow->renter_id)
            ->where('pr.status', 1)
            ->select(['pr.*', 'pt.id as promo_term_id'])
            ->first();
        $Promo = $promoRow ? ['PromotionRule' => $promoRow->toArray()] : null;

        $payments = [];
        foreach ($paymentsData as $p) {
            $payments[$p->type][$p->id] = $p->toArray();
        }

        $paymentTypeValue = app(\App\Services\Legacy\Common::class)->getPayoutTypeValue(true);

        return view('reports._autorenewddetails', compact(
            'csorder',
            'subOrders',
            'lastOrder',
            'payments',
            'downpaymentPaid',
            'Siblingbooking',
            'extlogs',
            'calculation',
            'insurance_payer',
            'totalGrandPaid',
            'OrderDepositRule',
            'totalDiaFee',
            'Promo',
            'dealerPaidInsurance',
            'paymentTypeValue'
        ));
    }
}
