<?php

namespace App\Services\Legacy\Report;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    public function getVehiclePortfolio(int $vehicleId): array
    {
        $result = DB::table('report_customers as ReportCustomer')
            ->where('ReportCustomer.vehicle_id', $vehicleId)
            ->selectRaw('
                SUM(ReportCustomer.total_collected) as total_collected,
                SUM(ReportCustomer.tax_collected) as total_tax_collected,
                SUM(ReportCustomer.days) AS totaldays,
                SUM(ReportCustomer.miles) AS miles,
                SUM(ReportCustomer.emf_collected) as emf_collected,
                SUM(ReportCustomer.insurance) as insurance_by_dealer,
                SUM(ReportCustomer.insurance_driver) as insurance_by_renter,
                SUM(ReportCustomer.calculated_insurance) as calculated_insurance,
                SUM(ReportCustomer.write_down_allocation) as write_down_allocation,
                SUM(ReportCustomer.finance_allocation) as finance_allocation,
                SUM(ReportCustomer.maintenance_allocation) as maintenance_allocation,
                SUM(ReportCustomer.disposition_fee) as disposition_fee,
                SUM(ReportCustomer.total_billed) as total_billed,
                SUM(ReportCustomer.tax) as tax,
                SUM(ReportCustomer.stripe_fee) as stripe_fee
            ')
            ->groupBy('ReportCustomer.vehicle_id')
            ->first();

        if ($result) {
            return (array) $result;
        }

        return [
            'totalrent' => 0.00, 'totaldays' => 0.00, 'extra_mileage_fee' => 0.00,
            'insurance_by_dealer' => 0, 'write_down_allocation' => 0,
            'finance_allocation' => 0, 'maintenance_allocation' => 0,
            'total_billed' => 0, 'tax' => 0, 'stripe_fee' => 0,
            'total_collected' => 0, 'total_tax_collected' => 0, 'miles' => 0,
            'emf_collected' => 0, 'insurance_by_renter' => 0,
            'calculated_insurance' => 0, 'disposition_fee' => 0,
        ];
    }

    /**
     * Port of CakePHP Portfolio::getVehicleDepriciationReport (spelling preserved).
     *
     * @return array{depreciation: float|string, financing: float|string, fleet_days: int}
     */
    public function getVehicleDepriciationReport(int $vehicleId): array
    {
        $zero = ['depreciation' => 0, 'financing' => 0, 'fleet_days' => 0];

        $csReport = DB::table('report_customers as ReportCustomer')
            ->where('ReportCustomer.vehicle_id', $vehicleId)
            ->orderBy('ReportCustomer.id')
            ->select('ReportCustomer.start_datetime')
            ->first();

        if ($csReport === null || $csReport->start_datetime === null || $csReport->start_datetime === '') {
            return $zero;
        }

        $vehicleRow = DB::table('vehicles as Vehicle')
            ->leftJoin('cs_deposit_rules as DepositRule', 'DepositRule.vehicle_id', '=', 'Vehicle.id')
            ->where('Vehicle.id', $vehicleId)
            ->select([
                'Vehicle.msrp',
                'Vehicle.vehicleCostInclRecon',
                'DepositRule.depreciation_rate',
                'DepositRule.lender_fee',
                'DepositRule.lender_type',
                'DepositRule.lender_anticipated_date',
            ])
            ->first();

        $referenceEnd = ($vehicleRow !== null && ! empty($vehicleRow->lender_anticipated_date))
            ? (string) $vehicleRow->lender_anticipated_date
            : (string) $csReport->start_datetime;

        $now = Carbon::now();
        $ref = Carbon::parse($referenceEnd);

        // Legacy Common::getDifference(now, reference, 3): floor((now - reference) / 86400)
        $rawDays = (int) floor(($now->getTimestamp() - $ref->getTimestamp()) / 86400);
        $days = $rawDays > 1 ? $rawDays - 1 : 1;

        if (! $days) {
            return $zero;
        }

        if ($vehicleRow === null) {
            return $zero;
        }

        $vehicleCostInclRecon = (float) ($vehicleRow->vehicleCostInclRecon ?? 0);
        $depreciationRate = (float) ($vehicleRow->depreciation_rate ?? 0);
        $lenderFee = (float) ($vehicleRow->lender_fee ?? 0);
        $lenderType = (string) ($vehicleRow->lender_type ?? '');

        $depreciation = 0.0;
        $financing = 0.0;

        if ($depreciationRate > 0) {
            $depreciation = (float) ($days * ((($vehicleCostInclRecon * $depreciationRate) / 100) * 12 / 365));
        }

        if ($lenderFee > 0) {
            $financing = $lenderType === 'P'
                ? ($vehicleCostInclRecon * $lenderFee / 36500)
                : ($lenderFee * 12 / 365);
            $financing = (float) ($financing * $days);
        }

        return [
            'depreciation' => sprintf('%0.2f', $depreciation),
            'financing' => sprintf('%0.2f', $financing),
            'fleet_days' => $days,
        ];
    }

    public function getVehicleFixedProgramCost(int $ownerId): float
    {
        $result = DB::table('cs_deposit_templates')
            ->where('user_id', $ownerId)
            ->value('fixed_program_cost');

        return (float) ($result ?? 0);
    }

    /**
     * @return array{depreciation: float, bodydamage: int|float, mechdamage: float, maintenance: float, toll: float}
     */
    public function getVehicleExpenses(int $vehicleId, string $dateFrom = '', string $dateTo = ''): array
    {
        $return = ['depreciation' => 0.00, 'bodydamage' => 0, 'mechdamage' => 0.00, 'maintenance' => 0.00, 'toll' => 0.00];

        $query = DB::table('cs_vehicle_expenses as CsVehicleExpense')
            ->where('CsVehicleExpense.vehicle_id', $vehicleId)
            ->selectRaw('SUM(CsVehicleExpense.amount) as total, CsVehicleExpense.type')
            ->groupBy('CsVehicleExpense.type');

        if (! empty($dateFrom)) {
            $query->where('CsVehicleExpense.created', '>=', Carbon::parse($dateFrom)->format('Y-m-d'));
        }
        if (! empty($dateTo)) {
            $query->where('CsVehicleExpense.created', '<=', Carbon::parse($dateTo)->format('Y-m-d'));
        }

        $expenses = $query->get();
        foreach ($expenses as $expense) {
            if ((int) $expense->type === 3) {
                $return['mechdamage'] = $expense->total;
            } elseif ((int) $expense->type === 1) {
                $return['bodydamage'] = $expense->total;
            } elseif ((int) $expense->type === 6) {
                $return['maintenance'] = $expense->total;
            } elseif ((int) $expense->type === 5) {
                $return['toll'] = $expense->total;
            }
        }

        return $return;
    }
}
