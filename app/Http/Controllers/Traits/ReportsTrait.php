<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\RevSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait ReportsTrait {
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
                'Driver.first_name as driver_first', 'Driver.last_name as driver_last',
                'Owner.business_name as owner_business', 'Owner.first_name as owner_first', 'Owner.last_name as owner_last',
                'Vehicle.vehicle_name', 'Vehicle.make', 'Vehicle.model', 'Vehicle.year', 'Vehicle.vin_no'
            );

        // Apply filters
        if ($request->filled('Search.keyword')) {
            $keyword = $request->input('Search.keyword');
            $searchIn = $request->input('Search.searchin');
            if ($searchIn == 1) $query->where('CsOrder.pickup_address', 'LIKE', "%$keyword%");
            elseif ($searchIn == 2) $query->where('CsOrder.vehicle_name', 'LIKE', "%$keyword%");
            elseif ($searchIn == 3) $query->where('CsOrder.increment_id', 'LIKE', "%$keyword%");
        }

        if ($request->filled('Search.date_from')) {
            $query->where('CsOrder.start_datetime', '>=', Carbon::parse($request->input('Search.date_from'))->toDateTimeString());
        }
        if ($request->filled('Search.date_to')) {
            $query->where('CsOrder.end_datetime', '<=', Carbon::parse($request->input('Search.date_to'))->toDateTimeString());
        }

        if ($request->filled('Search.status_type')) {
            $status = $request->input('Search.status_type');
            if ($status == 'cancel') $query->where('CsOrder.status', 2);
            elseif ($status == 'complete') $query->where('CsOrder.status', 3);
            elseif ($status == 'incomplete') $query->whereIn('CsOrder.status', [0, 1]);
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
            ->leftJoin('cs_orders as CsOrder', function($join) {
                $join->on('CsOrder.vehicle_id', '=', 'Vehicle.id')
                     ->where('CsOrder.status', 3);
            })
            ->select(
                'Vehicle.id', 'Vehicle.vehicle_name', 'Vehicle.msrp', 'Vehicle.created_at',
                DB::raw('SUM(CsOrder.rent + CsOrder.initial_fee + CsOrder.damage_fee + CsOrder.uncleanness_fee) as totalrent'),
                DB::raw('SUM(CsOrder.end_odometer - CsOrder.start_odometer) as mileage'),
                DB::raw('SUM(DATEDIFF(CsOrder.end_datetime, CsOrder.start_datetime)) as totaldays'),
                DB::raw('SUM(CsOrder.extra_mileage_fee) as extra_mileage_fee')
            )
            ->groupBy('Vehicle.id');

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

    /**
     * _getReportDetails: Common details logic
     */
    protected function _getReportDetails($id)
    {
        $csOrder = CsOrder::with(['Vehicle', 'Renter', 'Owner'])->findOrFail($id);
        
        $realBookingId = $csOrder->parent_id ?: $csOrder->id;
        $orderDepositRule = OrderDepositRule::where('cs_order_id', $realBookingId)->first();
        
        if (!$orderDepositRule) return null;

        $revSetting = RevSetting::where('user_id', $csOrder->user_id)->first();
        $revShare = $revSetting ? $revSetting->rental_rev : 85; // Default 85/15 split
        $diaFeePerc = (100 - $revShare);

        $payments = CsOrderPayment::where('cs_order_id', $id)->where('status', 1)->get();
        
        $totalGrandPaid = 0;
        $totalPaid = 0;
        $paidInitialFee = 0;
        $totalDiaFee = 0;

        foreach ($payments as $payment) {
            if (empty($payment->payer_id) || $payment->payer_id == $csOrder->renter_id) {
                $totalGrandPaid += $payment->amount;
            }
            
            if (in_array($payment->type, [2, 16])) { // Rental, EMF
                $cleanAmount = ($payment->amount - $payment->tax - $payment->dia_fee);
                $totalPaid += $cleanAmount;
                $totalDiaFee += ($cleanAmount * $diaFeePerc / 100);
            }
            if ($payment->type == 3) { // Initial Fee
                $cleanAmount = ($payment->amount - $payment->tax);
                $paidInitialFee += $cleanAmount;
                $totalDiaFee += ($cleanAmount * $diaFeePerc / 100);
            }
        }

        return [
            'csorder' => $csOrder,
            'downpaymentPaid' => ($totalPaid + $paidInitialFee),
            'payments' => $payments,
            'totalGrandPaid' => $totalGrandPaid,
            'orderDepositRule' => $orderDepositRule,
            'totalDiaFee' => $totalDiaFee,
            'revShare' => $revShare
        ];
    }

    /**
     * _getAutoRenewDetails: Aggregated view for auto-renewed bookings
     */
    protected function _getAutoRenewDetails($id)
    {
        $csOrder = CsOrder::with(['Renter'])->findOrFail($id);
        $siblingIds = CsOrder::where('parent_id', $id)->orWhere('id', $id)->pluck('id')->toArray();

        $subOrders = CsOrder::whereIn('id', $siblingIds)
            ->whereIn('status', [2, 3])
            ->selectRaw('
                SUM(rent) as rent, 
                SUM(dia_fee) as dia_fee,
                SUM(tax + emf_tax) as tax,
                SUM(initial_fee) as initial_fee,
                SUM(extra_mileage_fee) as extra_mileage_fee,
                SUM(insurance_amt) as insurance_amt
            ')->first();

        $payments = CsOrderPayment::whereIn('cs_order_id', $siblingIds)->where('status', 1)->get();
        
        return [
            'csorder' => $csOrder,
            'subOrders' => $subOrders,
            'payments' => $payments
        ];
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

        $callback = function() use ($ordersData) {
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
}
