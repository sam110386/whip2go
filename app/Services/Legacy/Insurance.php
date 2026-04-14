<?php

namespace App\Services\Legacy;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Insurance
{
    private StripeProcessor $stripeProcessor;

    public function __construct()
    {
        $this->stripeProcessor = new StripeProcessor();
    }

    public function initiateCalculatedAndChargeInsurance(array $data = [], bool $updateOrder = false): void
    {
        $res = $this->getCalculatedAndChargeInsurance($data);
        if (!$res['status'] && $updateOrder && !empty($data['orderid'])) {
            DB::table('cs_orders')->where('id', $data['orderid'])->update(['pending_insu' => $res['pending_insu']]);
        }
    }

    public function getCalculatedAndChargeInsurance(array $data = []): array
    {
        $return = ["status" => false, "message" => "Sorry, wrong attempt", "pending_insu" => $data['pending_insu']];

        $InsurancePayerObj = DB::table('insurance_payers')->where('order_deposit_rule_id', $data['order_rule_id'])->first();
        if (empty($InsurancePayerObj) || empty($InsurancePayerObj->stripe_key)) {
            $return["message"] = "Sorry, ROI vendor stripe account is not configured yet";
            return $return;
        }

        $return["message"] = "Sorry, please enter correct value for days";
        $last_date = empty($InsurancePayerObj->next)
            ? Carbon::parse($data['start_datetime'])->format('Y-m-d')
            : $InsurancePayerObj->next;

        $days = (int) Carbon::parse($last_date)->diffInDays(Carbon::today());
        if (!$days) {
            $days = 1;
        }
        $calculatedAmount = sprintf('%0.2f', ($days * $InsurancePayerObj->daily_rate));
        $return['pending_insu'] = $calculatedAmount;
        if ($calculatedAmount <= 0) {
            $return["message"] = "Sorry, calculated insurance is not valid value.";
            return $return;
        }

        $InsurancePayerTokenObj = DB::table('insurance_payer_tokens')
            ->where('order_rule_id', $InsurancePayerObj->order_deposit_rule_id)
            ->where('is_default', 1)
            ->first();
        if (empty($InsurancePayerTokenObj)) {
            $return["message"] = "Sorry, Driver didnt add his CC info yet";
            return $return;
        }

        $paymentResult = $this->stripeProcessor->chargeInsurance(
            $calculatedAmount,
            $InsurancePayerTokenObj->stripe_token,
            $InsurancePayerObj->stripe_key,
            $InsurancePayerObj->order_deposit_rule_id
        );
        if ($paymentResult['status'] != 'success') {
            $return["message"] = $paymentResult['message'];
            DB::table('insurance_payers')->where('id', $InsurancePayerObj->id)->update(['last_attempt' => now()]);
            return $return;
        }

        DB::table('insurance_payer_payments')->insert([
            "order_rule_id" => $InsurancePayerObj->order_deposit_rule_id,
            "amount" => $calculatedAmount,
            "transaction_id" => $paymentResult['transaction_id'],
            "created" => now(),
        ]);

        DB::table('insurance_payers')->where('id', $InsurancePayerObj->id)->update([
            "last_attempt" => now(),
            'attepmt' => 1,
            "next" => Carbon::parse($last_date)->addDays($days)->format('Y-m-d'),
        ]);

        return ["status" => true, "message" => "Amount : \${$calculatedAmount} is charged successfully", "pending_insu" => $calculatedAmount];
    }
}
