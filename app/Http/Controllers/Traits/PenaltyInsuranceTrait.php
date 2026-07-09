<?php
namespace App\Http\Controllers\Traits;

use App\Models\Legacy\AxleStatus;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsUserBalance;
use App\Models\Legacy\CsUserBalanceLog;
use App\Models\Legacy\OrderDepositRule;

trait PenaltyInsuranceTrait
{
    public function calculateAndSavePenaltyInsurance(array $axleStatusObj = [])
    {
        if (($axleStatusObj['expired_on'] ?? '') >= date('Y-m-d')) {
            return false;
        }

        $orderDepositRule = OrderDepositRule::where('id', $axleStatusObj['order_id'])
            ->select('id', 'cs_order_id', 'insurance')
            ->first();

        if (empty($orderDepositRule)) {
            return false;
        }

        $expiredOn = $axleStatusObj['expired_on'] ?? date('Y-m-d');
        $days = (int) ((strtotime(date('Y-m-d')) - strtotime($expiredOn)) / 86400);
        $totalInsurance = sprintf('%0.2f', ($days * $orderDepositRule->insurance));
        $calculatedInsurance = sprintf('%0.2f', ($totalInsurance - ($axleStatusObj['calculated_insurance'] ?? 0)));

        if ($calculatedInsurance <= 0) {
            return false;
        }

        $orderObj = CsOrder::select('id', 'renter_id', 'user_id')
            ->where(function ($q) use ($orderDepositRule) {
                $q->where('id', $orderDepositRule->cs_order_id)
                    ->orWhere('parent_id', $orderDepositRule->cs_order_id);
            })
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($orderObj)) {
            return false;
        }

        CsUserBalanceLog::create([
            'user_id' => $orderObj->renter_id,
            'credit' => $calculatedInsurance,
            'type' => 20,
            'owner_id' => $orderObj->user_id,
            'note' => 'Penalty for Vehicle Insurance is not valid',
        ]);

        CsUserBalance::create([
            'owner_id' => $orderObj->user_id,
            'user_id' => $orderObj->renter_id,
            'note' => 'Penalty for Vehicle Alert',
            'credit' => $calculatedInsurance,
            'balance' => $calculatedInsurance,
            'debit' => 0,
            'type' => 20,
            'chargetype' => 'lumpsum',
            'installment_type' => 'daily',
            'installment_day' => null,
            'installment' => 0,
        ]);

        $newCalc = ($axleStatusObj['calculated_insurance'] ?? 0) + $calculatedInsurance;

        AxleStatus::where('id', $axleStatusObj['id'])->update([
            'calculated_insurance' => $newCalc,
        ]);

        return $calculatedInsurance;
    }
    public function savePenaltyInsurance(array $orderObj = [], $calculatedInsurance = 0)
    {
        if ($calculatedInsurance <= 0 || empty($orderObj)) {
            return false;
        }

        CsUserBalanceLog::create([
            'user_id' => $orderObj['renter_id'],
            'credit' => $calculatedInsurance,
            'type' => 20,
            'owner_id' => $orderObj['user_id'],
            'note' => 'Penalty for Vehicle Insurance is not valid',
        ]);

        CsUserBalance::create([
            'owner_id' => $orderObj['user_id'],
            'user_id' => $orderObj['renter_id'],
            'note' => 'Penalty for Vehicle Alert',
            'credit' => $calculatedInsurance,
            'balance' => $calculatedInsurance,
            'debit' => 0,
            'type' => 20,
            'chargetype' => 'lumpsum',
            'installment_type' => 'daily',
            'installment_day' => null,
            'installment' => 0,
        ]);

        $axleStatusObj = AxleStatus::where('order_id', $orderObj['deposit_rule_id'])->first();

        if ($axleStatusObj) {
            AxleStatus::where('id', $axleStatusObj->id)->update([
                'calculated_insurance' => ($axleStatusObj->calculated_insurance ?? 0) + $calculatedInsurance,
            ]);
        }

        return $calculatedInsurance;
    }
}
