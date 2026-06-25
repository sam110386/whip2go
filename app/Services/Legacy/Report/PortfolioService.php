<?php

namespace App\Services\Legacy\Report;

use Carbon\Carbon;
use App\Services\Legacy\Common as CommonService;
use App\Models\Legacy\ReportCustomer;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\CsVehicleExpense;

/**
 * Port of CakePHP app/Plugin/Report/Lib/Portfolio.php
 */
class PortfolioService
{
    public function getVehiclePortfolio($vehicleId)
    {

        $report = ReportCustomer::where('vehicle_id', $vehicleId)
            ->selectRaw('
                SUM(total_collected) as total_collected,
                SUM(tax_collected) as tax_collected,
                SUM(days) AS totaldays,
                SUM(miles) AS miles,
                SUM(emf_collected) as emf_collected,
                SUM(insurance) as insurance_by_dealer,
                SUM(insurance_driver) as insurance_by_renter,
                SUM(calculated_insurance) as calculated_insurance,
                SUM(write_down_allocation) as write_down_allocation,
                SUM(finance_allocation) as finance_allocation,
                SUM(maintenance_allocation) as maintenance_allocation,
                SUM(disposition_fee) as disposition_fee,
                SUM(total_billed) as total_billed,
                SUM(tax) as tax,
                SUM(stripe_fee) as stripe_fee
            ')
            ->groupBy('vehicle_id')
            ->first();

        if ($report) {
            return $report->toArray();
        }

        return [
            "totalrent" => 0.00,
            "totaldays" => 0.00,
            "extra_mileage_fee" => 0.00,
            "insurance_by_dealer" => 0,
            'write_down_allocation' => 0,
            'finance_allocation' => 0,
            'maintenance_allocation' => 0,
            "total_billed" => 0,
            "tax" => 0,
            "stripe_fee" => 0
        ];
    }

    public function getVehicleDepriciationReport($vehicleId)
    {
        $commonService = new CommonService();
        $firstReport = ReportCustomer::where('vehicle_id', $vehicleId)
            ->orderBy('id', 'asc')
            ->first(['start_datetime']);

        if (!$firstReport) {
            return ["depreciation" => 0, "financing" => 0];
        }

        $vehicle = Vehicle::with('depositRule:vehicle_id,depreciation_rate,lender_fee,lender_type,lender_anticipated_date')
            ->find($vehicleId, ['id', 'msrp', 'vehicleCostInclRecon']);

        if (!$vehicle) {
            return ["depreciation" => 0, "financing" => 0];
        }

        $rule = $vehicle->depositRule;
        $startDate = !empty($rule->lender_anticipated_date)
            ? $rule->lender_anticipated_date
            : $firstReport->start_datetime;

        $days = $commonService->getDifference(date('Y-m-d H:i:s'), $startDate, 3);
        $days = $days > 1 ? $days - 1 : 1;

        if (!$days) {
            return ["depreciation" => 0, "financing" => 0];
        }

        $depreciation = $financing = 0;

        if ($rule && $rule->depreciation_rate > 0) {
            $depreciation = $days * ((($vehicle->vehicleCostInclRecon * $rule->depreciation_rate) / 100) * 12 / 365);
        }

        if ($rule && $rule->lender_fee > 0) {
            $financing = ($rule->lender_type === 'P') ? ($vehicle->vehicleCostInclRecon * $rule->lender_fee / 36500) : ($rule->lender_fee * 12 / 365);
            $financing *= $days;
        }

        return [
            "depreciation" => number_format($depreciation, 2, '.', ''),
            "financing" => number_format($financing, 2, '.', ''),
            "fleet_days" => $days
        ];
    }

    public function getVehicleFixedProgramCost($ownerId)
    {
        $template = DepositTemplate::where('user_id', $ownerId)->first(['fixed_program_cost']);
        return $template ? $template->fixed_program_cost : 0;
    }

    public function getVehicleExpenses($vehicleId, $dateFrom = '', $dateTo = '')
    {
        $return = [
            "depreciation" => 0.00,
            "bodydamage" => 0.00,
            "mechdamage" => 0.00,
            "maintenance" => 0.00,
            "toll" => 0.00
        ];

        $query = CsVehicleExpense::where('vehicle_id', $vehicleId)
            ->selectRaw('SUM(amount) as total, type')
            ->groupBy('type');

        if (!empty($dateFrom)) {
            $query->where('created', '>=', Carbon::parse($dateFrom)->toDateTimeString());
        }

        if (!empty($dateTo)) {
            $query->where('created', '<=', Carbon::parse($dateTo)->toDateTimeString());
        }

        $expenses = $query->get();

        foreach ($expenses as $expense) {
            switch ($expense->type) {
                case 3:
                    $return['mechdamage'] = (float) $expense->total;
                    break;
                case 1:
                    $return['bodydamage'] = (float) $expense->total;
                    break;
                case 6:
                    $return['maintenance'] = (float) $expense->total;
                    break;
                case 5:
                    $return['toll'] = (float) $expense->total;
                    break;
            }
        }

        return $return;
    }

}
