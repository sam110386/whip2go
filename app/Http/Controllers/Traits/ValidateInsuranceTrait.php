<?php

namespace App\Http\Controllers\Traits;

use App\Services\Legacy\AxleService;
use Illuminate\Support\Facades\DB;

trait ValidateInsuranceTrait
{
    protected function insuranceDetails(string $policy, $insuranceDetails): array
    {
        if (!is_array($insuranceDetails)) return [];
        foreach ($insuranceDetails as $detail) {
            if (($detail['datasource']['id'] ?? '') == $policy) {
                return $detail;
            }
        }
        return [];
    }

    protected function filterCoverages(array $coverages, string $vin): array
    {
        foreach ($coverages as $coverage) {
            if (strtolower($coverage['type'] ?? '') === 'auto' && isset($coverage['details']['vehicle_info']['vin']) && strtoupper($vin) == strtoupper($coverage['details']['vehicle_info']['vin'])) {
                return $coverage;
            }
        }
        return [];
    }

    protected function validateInsurance(array $policy, array $axleStatus): void
    {
        $checklists = AxleService::$rules;

        $orderDepositRule = DB::table('order_deposit_rules as OrderDepositRule')
            ->leftJoin('cs_orders as CsOrder', function ($join) {
                $join->whereIn('CsOrder.status', [0, 1])
                    ->where(function ($q) {
                        $q->whereColumn('CsOrder.id', 'OrderDepositRule.cs_order_id')
                            ->orWhereColumn('CsOrder.parent_id', 'OrderDepositRule.cs_order_id');
                    });
            })
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->where('OrderDepositRule.id', $axleStatus['order_id'])
            ->whereNotNull('CsOrder.vehicle_id')
            ->select('OrderDepositRule.*', 'CsOrder.id as cs_order_id_val', 'CsOrder.renter_id', 'CsOrder.vehicle_id', 'Vehicle.allowed_miles', 'Vehicle.vin_no')
            ->first();

        if (empty($orderDepositRule)) return;

        $coverage = $this->filterCoverages($policy['coverages'] ?? [], $orderDepositRule->vin_no ?? '');

        $vinActive = $lienholderActive = $lessorActive = $compActive = $collActive = false;
        $checklists['vin']['accepted'] = 0;
        $checklists['vin']['policy_text'] = "VIN dont match";

        if (!empty($coverage)) {
            $checklists['vin']['policy_text'] = strtoupper($coverage['details']['vehicle_info']['vin'] ?? '');
            $vinActive = true;
            $checklists['vin']['accepted'] = 1;
        }

        $thirdParties = $coverage['details']['vehicle_info']['interested_parties'] ?? [];
        foreach ($thirdParties as $thirdParty) {
            $name = preg_replace("/[^a-zA-Z]/", '', strtolower($thirdParty['name'] ?? ''));
            $isDia = in_array($name, ['dialeasingllc', 'driveitawayinc', 'driveitaway', 'dialeasing']);
            if (strtoupper($thirdParty['type'] ?? '') === 'LIEN_HOLDER') {
                $checklists['lienholder']['policy_text'] = $thirdParty['name'];
                $lienholderActive = $isDia;
                $checklists['lienholder']['accepted'] = $lienholderActive;
            }
            if (strtoupper($thirdParty['type'] ?? '') === 'LESSOR') {
                $checklists['lessor']['policy_text'] = $thirdParty['name'];
                $lessorActive = $isDia;
                $checklists['lessor']['accepted'] = $lessorActive;
            }
        }

        foreach ($coverage['details']['coverage_items'] ?? [] as $item) {
            if (strtoupper($item['type'] ?? '') === 'COMPREHENSIVE_COVERAGE') {
                $deductible = end($item['deductibles'])['value']['amount'] ?? 0;
                $checklists['compreshensive']['policy_text'] = $deductible;
                $compActive = ($item['deductible'] ?? 0) <= 500;
                $checklists['compreshensive']['accepted'] = $compActive;
            }
            if (strtoupper($item['type'] ?? '') === 'COLLISION_COVERAGE') {
                $deductible = end($item['deductibles'])['value']['amount'] ?? 0;
                $checklists['collision']['policy_text'] = $deductible;
                $collActive = ($item['deductible'] ?? 0) <= 500;
                $checklists['collision']['accepted'] = $collActive;
            }
        }

        $policyActive = $vinActive && $lienholderActive && $lessorActive && $compActive && $collActive;

        if ($policyActive) {
            $extra = json_decode($axleStatus['extra'] ?? '{}', true) ?: [];
            DB::table('axle_status')->where('id', $axleStatus['id'])->update([
                'extra' => json_encode(array_merge($extra, $checklists)),
            ]);
        }

        if (!$policyActive) {
            $axleStatus['axle_status'] = 4;
            if (($orderDepositRule->insurance_payer ?? 0) == 7) {
                $axleStatus['expired_on'] = date('Y-m-d', strtotime($policy['expirationDate'] ?? date('Y-m-d')));
            }
        }
        if (($policy['status'] ?? '') != 'ACTIVE') {
            $policyActive = false;
            $axleStatus['axle_status'] = 3;
        }

        if (!$policyActive) {
            $extra = json_decode($axleStatus['extra'] ?? '{}', true) ?: [];
            DB::table('axle_status')->where('id', $axleStatus['id'])->update([
                'extra' => json_encode(array_merge($extra, $checklists)),
                'axle_status' => $axleStatus['axle_status'],
                'expired_on' => $axleStatus['expired_on'] ?? null,
            ]);

            if (($orderDepositRule->insurance_payer ?? 0) != 7) return;

            $depositRule = DB::table('deposit_rules')->where('vehicle_id', $orderDepositRule->vehicle_id)->select('insurance_fee', 'emf_insu')->first();
            if (empty($depositRule)) return;

            $allowedMiles = $orderDepositRule->allowed_miles ?? 0;
            $diaInsu = $depositRule->emf_insu ?: 0;
            $insuranceFee = sprintf('%0.2f', ($depositRule->insurance_fee + (($orderDepositRule->miles ?? 0) - $allowedMiles) * $diaInsu));

            DB::table('order_deposit_rules')->where('id', $orderDepositRule->id)->update([
                'insurance' => $insuranceFee,
                'emf_insu_rate' => $diaInsu,
            ]);

            $extra = json_decode($axleStatus['extra'] ?? '{}', true) ?: [];
            DB::table('axle_status')->where('id', $axleStatus['id'])->update([
                'extra' => json_encode(array_merge($extra, $checklists)),
            ]);
        }
    }
}
