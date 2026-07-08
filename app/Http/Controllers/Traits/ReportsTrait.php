<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\OrderExtlog;
use App\Services\Legacy\Portfolio;
use App\Services\Legacy\PromoService;
use Carbon\Carbon;

trait ReportsTrait
{
    private function _details($id)
    {
        $id = $this->decodeId($id);
        $data = [];

        if (!empty($id)) {
            $csorder = CsOrder::with('user:id,first_name,last_name,contact_number')
                ->where('id', $id)
                ->first();

            if ($csorder) {
                $Siblingbooking = [$csorder->id => $csorder->increment_id];
                $realBookingId = $csorder->parent_id ?: $csorder->id;
                $OrderDepositRule = OrderDepositRule::where('cs_order_id', $realBookingId)->first();

                if ($OrderDepositRule) {
                    $RevSetting = RevSetting::where('user_id', $csorder->user_id)->first();
                    $revshare = !empty($RevSetting->rental_rev) ? $RevSetting->rental_rev : config('legacy.OWNER_PART', 85);
                    $diAFee = (100 - $revshare * 1);

                    $payments = CsOrderPayment::where('cs_order_id', $id)
                        ->where('status', 1)
                        ->where('type', '!=', 1)
                        ->get();

                    $totalPaid = $paidInitialFee = $totalGrandPaid = $totalDiaFee = $dealerPaidInsurance = 0;

                    foreach ($payments as $payment) {

                        if (empty($payment->payer_id) || $payment->payer_id == $csorder->renter_id) {
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

                    $downpaymentPaid = $totalPaid + $paidInitialFee;
                    $extlogs = $this->_getExtLogs([$id]);
                    $calculation = !empty($OrderDepositRule->calculation) ? json_decode($OrderDepositRule->calculation, true) : [];
                    $insurance_payer = $this->commonService->getInsurancePayer($OrderDepositRule->insurance_payer);
                    $Promo = app(PromoService::class)->getUserPromo($csorder->renter_id);

                    $payments = $payments->groupBy('type')->map(function ($group) {
                        return $group->keyBy('id');
                    })->toArray();

                    $data = compact(
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
                        'dealerPaidInsurance'
                    );
                }
            }
        }
    }
    private function _autorenewddetails($id)
    {
        $id = $this->decodeId($id);
        $data = [];

        if (!empty($id)) {
            $lastOrder = $csorder = CsOrder::with('user:id,first_name,last_name,contact_number')
                ->where('id', $id)
                ->first();

            if ($csorder) {
                $subOrders = CsOrder::select([
                    DB::raw('SUM(rent) as rent'),
                    DB::raw('SUM(dia_fee) as dia_fee'),
                    DB::raw('SUM(tax + emf_tax) as tax'),
                    DB::raw('SUM(initial_fee) as initial_fee'),
                    DB::raw('SUM(initial_fee_tax) as initial_fee_tax'),
                    DB::raw('SUM(extra_mileage_fee) as extra_mileage_fee'),
                    DB::raw('SUM(lateness_fee) as lateness_fee'),
                    DB::raw('SUM(discount) as discount'),
                    DB::raw('SUM(damage_fee) as damage_fee'),
                    DB::raw('SUM(uncleanness_fee) as uncleanness_fee'),
                    DB::raw('SUM(insurance_amt) as insurance_amt'),
                    DB::raw('SUM(dia_insu) as dia_insu'),
                    DB::raw('SUM(toll) as toll'),
                    DB::raw('SUM(pending_toll) as pending_toll'),
                    DB::raw('SUM(end_odometer) as end_odometer'),
                    DB::raw('SUM(initial_discount) as initial_discount'),
                ])
                    ->where(function ($query) use ($id) {
                        $query->where('parent_id', $id)->orWhere('id', $id);
                    })
                    ->whereIn('status', [2, 3])
                    ->first();

                if ($csorder->status == 3) {
                    $lastOrder = CsOrder::where('parent_id', $id)
                        ->orderBy('id', 'DESC')
                        ->first() ?: $csorder;
                }

                $realBookingId = $csorder->parent_id ?: $csorder->id;
                $OrderDepositRule = OrderDepositRule::where('cs_order_id', $realBookingId)->first();
                $Siblingbooking = CsOrder::where(function ($query) use ($csorder) {
                    $query->where('parent_id', $csorder->id)->orWhere('id', $csorder->id);
                })
                    ->pluck('increment_id', 'id')
                    ->toArray();

                $Siblingbookings = array_keys($Siblingbooking);
                $RevSetting = RevSetting::where('user_id', $lastOrder->user_id)->first();
                $revshare = !empty($RevSetting->rental_rev) ? $RevSetting->rental_rev : config('legacy.OWNER_PART', 85);
                $diAFee = (100 - $revshare * 1);
                $payments = CsOrderPayment::select('id', 'rent', 'amount', 'tax', 'dia_fee', 'type', 'payer_id', 'charged_at')
                    ->whereIn('cs_order_id', $Siblingbookings)
                    ->where('status', 1)
                    ->where('type', '!=', 1)
                    ->get();

                $totalPaid = $paidInitialFee = $totalGrandPaid = $totalDiaFee = $dealerPaidInsurance = 0;

                foreach ($payments as $payment) {

                    if (empty($payment->payer_id) || $payment->payer_id == $csorder->renter_id) {
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

                $downpaymentPaid = $totalPaid + $paidInitialFee;
                $extlogs = $this->_getExtLogs($Siblingbookings);
                $calculation = !empty($OrderDepositRule->calculation) ? json_decode($OrderDepositRule->calculation, true) : [];
                $insurance_payer = $this->commonService->getInsurancePayer($OrderDepositRule->insurance_payer);
                $Promo = app(PromoService::class)->getUserPromo($csorder->renter_id);

                $payments = $payments->groupBy('type')->map(function ($group) {
                    return $group->keyBy('id');
                })->toArray();

                $data = compact(
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
                    'dealerPaidInsurance'
                );
            }
        }

        return view('reports._autorenewddetails', $data);
    }
    private function _getExtLogs(array $ids)
    {
        return OrderExtlog::with('owner:id,first_name,last_name')
            ->whereIn('cs_order_id', $ids)
            ->orderBy('id', 'DESC')
            ->get();
    }
    private function exportproductivity($conditions, $date_from, $date_to)
    {

        $ordersData = Vehicle::query()
            ->leftJoin('cs_orders', function ($join) {
                $join->on('cs_orders.vehicle_id', '=', 'vehicles.id')
                    ->where('cs_orders.status', '=', 3);
            })
            ->where($conditions)
            ->select([
                'vehicles.id',
                'vehicles.vehicle_name',
                'vehicles.msrp',
                'vehicles.created',
                DB::raw('SUM(cs_orders.rent + cs_orders.initial_fee + cs_orders.damage_fee + cs_orders.uncleanness_fee) as totalrent'),
                DB::raw('SUM(cs_orders.end_odometer - cs_orders.start_odometer) as mileage'),
                DB::raw('SUM(DATEDIFF(cs_orders.end_datetime, cs_orders.start_datetime)) AS totaldays'),
                DB::raw('SUM(cs_orders.extra_mileage_fee) as extra_mileage_fee')
            ])
            ->groupBy('vehicles.id', 'vehicles.vehicle_name', 'vehicles.msrp', 'vehicles.created')
            ->orderBy('vehicles.id', 'DESC')
            ->limit(5000)
            ->get();

        $portfolioModel = new Portfolio();
        $commonService = $this->commonService;

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=Productivity_Report.csv',
        ];

        return response()->stream(function () use ($ordersData, $portfolioModel, $commonService, $date_from, $date_to) {
            $fp = fopen('php://output', 'w');

            $csvHeader = ["Vehicle", "Vehicle Cost", "Depreciation", "Base Uses ($)", "Extra Usage", "Total Usage Fee", "Total Distance", "Total Days", "Idle Days"];
            fputcsv($fp, $csvHeader);

            foreach ($ordersData as $vehicle) {
                $expenses = $portfolioModel->getVehicleExpenses($vehicle->id, $date_from, $date_to);
                $totalDays = (empty($date_from) || empty($date_to))
                    ? $commonService->days_between_dates($vehicle->created, date('Y-m-d'))
                    : $commonService->days_between_dates($date_from, $date_to);

                $totalRent = $vehicle->totalrent ?? 0;
                $extraMileageFee = $vehicle->extra_mileage_fee ?? 0;
                $mileage = $vehicle->mileage ?? 0;
                $vehicleTotalDays = $vehicle->totaldays ?? 0;
                $depreciation = $expenses['depreciation'] ?? 0;

                $row = [
                    $vehicle->vehicle_name,
                    $vehicle->msrp,
                    $depreciation,
                    sprintf('%0.2f', $totalRent),
                    $extraMileageFee,
                    sprintf('%0.2f', ($totalRent + $extraMileageFee)),
                    $mileage,
                    $vehicleTotalDays,
                    ($totalDays - $vehicleTotalDays)
                ];

                fputcsv($fp, $row);
            }

            fclose($fp);
        }, 200, $headers);
    }
    private function export($conditions, $status_type = '')
    {

        $query = CsOrder::query()
            ->select([
                'id',
                'increment_id',
                'parent_id',
                'rent',
                'timezone',
                'start_datetime',
                'end_datetime',
                'pickup_address',
                'end_odometer',
                'insurance_amt',
                'renter_id',
                'user_id',
                'vehicle_id'
            ])
            ->with([
                'user:id,first_name,last_name',
                'owner:id,business_name',
                'vehicle:id,make,model,year,vin_no'
            ])
            ->where($conditions);

        if ($status_type === 'incomplete') {
            $query->whereIn('status', [0, 1]);
        }

        $ordersData = $query->orderBy('id', 'ASC')
            ->limit(5000)
            ->get();

        $commonService = $this->commonService;

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=Booking_Report.csv',
        ];

        return response()->stream(function () use ($ordersData, $commonService) {
            $fp = fopen('php://output', 'w');

            if ($fp) {
                $header = [
                    "No",
                    "Booking No",
                    "Duration",
                    "Total Rental",
                    "Mileage",
                    "Insurance",
                    "Type",
                    "Car Info",
                    "VIN Number",
                    "Start Date",
                    "End Date",
                    "Owner Name",
                    "Driver Name",
                    "DIA Commission",
                    "Late Fee Share",
                    "Late Insurance",
                    "City Tax",
                    "Tourism Surcharge",
                    "City",
                    "Amount To Owner"
                ];
                fputcsv($fp, $header);

                $i = 1;
                foreach ($ordersData as $order) {
                    $startDate = $order->start_datetime
                        ? Carbon::parse($order->start_datetime, $order->timezone)->format('m/d/Y')
                        : '';
                    $endDate = $order->end_datetime
                        ? Carbon::parse($order->end_datetime, $order->timezone)->format('m/d/Y')
                        : '';

                    $ownerName = $order?->owner?->business_name ?? '';
                    $driverName = trim(($order?->user?->first_name ?? '') . ' ' . ($order?->user?->last_name ?? ''));
                    $row = [
                        $i++,
                        $order->increment_id,
                        $commonService->days_between_dates($order->start_datetime, $order->end_datetime),
                        $order->rent,
                        $order->end_odometer,
                        $order->insurance_amt,
                        ($order->parent_id ? "Extended" : ""),
                        trim("{$order?->vehicle?->make} {$order?->vehicle?->model} {$order?->vehicle?->year}"),
                        $order?->vehicle?->vin_no,
                        $startDate,
                        $endDate,
                        $ownerName,
                        $driverName,
                        sprintf("%0.2f", ($order->rent * 15 / 100)),
                        0.0,
                        0.0,
                        3.43,
                        2,
                        $order->pickup_address,
                        sprintf("%0.2f", ($order->rent * 85 / 100))
                    ];

                    fputcsv($fp, $row);
                }
            }
            fclose($fp);
        }, 200, $headers);
    }
}
