<?php

namespace App\Http\Controllers\Traits;

use App\Services\Legacy\AxleService;
use Illuminate\Support\Facades\DB;

trait PolicyValidateTrait
{
    private function convertBookingInsuranceTypeIfPolicyExpired(array $policy = [], array $AxleStatus = []): void
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
            ->where('OrderDepositRule.id', $AxleStatus['order_id'])
            ->whereNotNull('CsOrder.vehicle_id')
            ->select('OrderDepositRule.*', 'CsOrder.id as cs_order_id_val', 'CsOrder.user_id', 'CsOrder.renter_id', 'CsOrder.vehicle_id', 'Vehicle.allowed_miles', 'Vehicle.vin_no')
            ->first();

        $properties = $policy['properties'] ?? [];
        $vinActive = $lienholderActive = $lessorActive = $compActive = $collActive = false;
        $propertyId = '';
        $checklists['vin']['accepted'] = 0;
        $checklists['vin']['policy_text'] = "VIN dont match";

        foreach ($properties as $property) {
            if (strtolower($property['type']) === 'vehicle' && isset($property['data']['vin']) && strtoupper($orderDepositRule->vin_no ?? '') == strtoupper($property['data']['vin'])) {
                $propertyId = $property['id'];
                $checklists['vin']['policy_text'] = strtoupper($property['data']['vin']);
                $vinActive = true;
                $checklists['vin']['accepted'] = 1;
                break;
            }
        }

        $thirdParties = $policy['thirdParties'] ?? [];
        foreach ($thirdParties as $thirdParty) {
            $name = preg_replace("/[^a-zA-Z]/", '', strtolower($thirdParty['name'] ?? ''));
            $isDia = in_array($name, ['dialeasingllc', 'driveitawayinc', 'driveitaway', 'dialeasing']);
            if (strtolower($thirdParty['type']) === 'lienholder' && $propertyId == $thirdParty['property']) {
                $checklists['lienholder']['policy_text'] = $thirdParty['name'];
                $lienholderActive = $isDia;
                $checklists['lienholder']['accepted'] = $lienholderActive;
            }
            if (strtolower($thirdParty['type']) === 'lessor' && $propertyId == $thirdParty['property']) {
                $checklists['lessor']['policy_text'] = $thirdParty['name'];
                $lessorActive = $isDia;
                $checklists['lessor']['accepted'] = $lessorActive;
            }
        }

        $coverages = $policy['coverages'] ?? [];
        foreach ($coverages as $coverage) {
            if (strtolower($coverage['code'] ?? '') === 'comp' && $propertyId == ($coverage['property'] ?? '')) {
                $checklists['compreshensive']['policy_text'] = $coverage['deductible'];
                $compActive = $coverage['deductible'] <= 500;
                $checklists['compreshensive']['accepted'] = $compActive;
            }
            if (strtolower($coverage['code'] ?? '') === 'coll' && $propertyId == ($coverage['property'] ?? '')) {
                $checklists['collision']['policy_text'] = $coverage['deductible'];
                $collActive = $coverage['deductible'] <= 500;
                $checklists['collision']['accepted'] = $collActive;
            }
        }

        $policyActive = $vinActive && $lienholderActive && $lessorActive && $compActive && $collActive;

        if ($policyActive) {
            $extra = json_decode(!empty($AxleStatus['extra']) ? $AxleStatus['extra'] : '{}', true);
            DB::table('axle_status')->where('id', $AxleStatus['id'])->update([
                'extra' => json_encode(array_merge((array) $extra, (array) $checklists)),
            ]);
        }

        $checklists['old_status']['status'] = ($policy['isActive'] ?? false) != true ? 3 : $AxleStatus['axle_status'];

        if (!$policyActive) {
            $AxleStatus['axle_status'] = 4;
            if (($orderDepositRule->insurance_payer ?? 0) == 7) {
                $AxleStatus['expired_on'] = date('Y-m-d', strtotime($policy['expirationDate'] ?? date('Y-m-d')));
            }
        }
        if (($policy['isActive'] ?? false) != true) {
            $policyActive = false;
            if (($orderDepositRule->insurance_payer ?? 0) == 7) {
                $AxleStatus['expired_on'] = date('Y-m-d', strtotime($policy['expirationDate'] ?? date('Y-m-d')));
            }
            $AxleStatus['axle_status'] = 3;
        }

        if (!$policyActive) {
            $extra = json_decode(!empty($AxleStatus['extra']) ? $AxleStatus['extra'] : '{}', true);
            DB::table('axle_status')->where('id', $AxleStatus['id'])->update([
                'extra' => json_encode(array_merge((array) $extra, (array) $checklists)),
                'axle_status' => $AxleStatus['axle_status'],
                'expired_on' => $AxleStatus['expired_on'] ?? null,
            ]);

            if (empty($orderDepositRule)) return;
            if (($orderDepositRule->insurance_payer ?? 0) != 7) return;

            $depositRule = DB::table('deposit_rules')->where('vehicle_id', $orderDepositRule->vehicle_id)->select('insurance_fee', 'emf_insu')->first();
            if (empty($depositRule)) return;

            [$insuranceFee, $diaInsu] = $this->getInsurance($orderDepositRule->miles ?? 0, $orderDepositRule, $depositRule);

            $checklists['insurance_old']['insurance_payer'] = $orderDepositRule->insurance_payer;
            $checklists['insurance_old']['insurance_rate'] = $orderDepositRule->insurance ?? 0;
            $checklists['emfinsurance_old']['insurance_rate'] = $orderDepositRule->emf_insu_rate ?? 0;

            DB::table('order_deposit_rules')->where('id', $orderDepositRule->id)->update([
                'insurance' => $insuranceFee, 'emf_insu_rate' => $diaInsu,
            ]);

            $checklists['insurance_new']['insurance_payer'] = 0;
            $checklists['insurance_new']['insurance_rate'] = $insuranceFee;
            $checklists['emfinsurance_new']['insurance_rate'] = $diaInsu;

            $extra = json_decode(!empty($AxleStatus['extra']) ? $AxleStatus['extra'] : '{}', true);
            DB::table('axle_status')->where('id', $AxleStatus['id'])->update([
                'extra' => json_encode(array_merge((array) $extra, (array) $checklists)),
            ]);

            if ($AxleStatus['axle_status'] == 3) {
                $oldTicket = DB::table('cs_vehicle_issues')
                    ->where('vehicle_id', $orderDepositRule->vehicle_id)
                    ->where('type', 10)->where('status', '!=', 3)->first();
                if (!empty($oldTicket)) return;
                DB::table('cs_vehicle_issues')->insert([
                    'user_id' => $orderDepositRule->user_id,
                    'vehicle_id' => $orderDepositRule->vehicle_id,
                    'renter_id' => $orderDepositRule->renter_id,
                    'cs_order_id' => $orderDepositRule->cs_order_id_val,
                    'type' => 10,
                ]);
            }
        }
    }

    private function getInsurance($milesOptions, $vehicleData, $depositRule): array
    {
        $allowedMiles = $vehicleData->allowed_miles ?? 0;
        $diaInsu = $depositRule->emf_insu ?: 0;
        $insurance = $depositRule->insurance_fee ?? 0;
        return [sprintf('%0.2f', ($insurance + ($milesOptions - $allowedMiles) * $diaInsu)), $diaInsu];
    }
}
