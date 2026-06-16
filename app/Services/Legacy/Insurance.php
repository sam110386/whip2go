<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\InsurancePayer;
use App\Models\Legacy\InsurancePayerPayment;
use App\Models\Legacy\InsurancePayerToken;
use App\Services\Legacy\Common;
use Carbon\Carbon;

class Insurance
{
    public function initiateCalculatedAndChargeInsurance(array $data = [], bool $updateOrder = false): void
    {
        $res = $this->getCalculatedAndChargeInsurance($data);

        if (!$res['status'] && $updateOrder && !empty($data['orderid'])) {
            CsOrder::withoutEvents(function () use ($data, $res) {
                CsOrder::where('id', $data['orderid'])->update([
                    'pending_insu' => $res['pending_insu']
                ]);
            });
        }
    }
    public function getCalculatedAndChargeInsurance(array $data = []): array
    {
        $return = [
            "status" => false,
            "message" => "Sorry, wrong attempt",
            "pending_insu" => $data['pending_insu']
        ];

        $insurancePayer = InsurancePayer::where('order_deposit_rule_id', $data['order_rule_id'] ?? null)->first();

        if (empty($insurancePayer) || empty($insurancePayer->stripe_key)) {
            $return["message"] = "Sorry, ROI vendor stripe account is not configured yet";
            return $return;
        }

        $return["message"] = "Sorry, please enter correct value for days";
        $lastDate = $insurancePayer->next ?? date('Y-m-d', strtotime($data['start_datetime']));
        $days = (new Common())->days_between_dates($lastDate, date('Y-m-d'));

        if (!$days) {
            $days = 1;
        }

        $calculatedAmount = sprintf('%0.2f', ($days * $insurancePayer->daily_rate));
        $return['pending_insu'] = $calculatedAmount;

        if ($calculatedAmount <= 0) {
            $return["message"] = "Sorry, calculated insurance is not valid value.";
            return $return;
        }

        $insurancePayerToken = InsurancePayerToken::where('order_rule_id', $insurancePayer->order_deposit_rule_id)
            ->where('is_default', 1)
            ->first();

        if (empty($insurancePayerToken)) {
            $return["message"] = "Sorry, Driver didnt add his CC info yet";
            return $return;
        }

        $stripeProcessor = new StripeProcessor();
        $paymentResult = $stripeProcessor->chargeInsurance(
            $calculatedAmount,
            $insurancePayerToken->stripe_token,
            $insurancePayer->stripe_key,
            $insurancePayer->order_deposit_rule_id
        );

        if (($paymentResult['status'] ?? '') !== 'success') {
            $return["message"] = $paymentResult['message'] ?? 'Payment failed';
            $insurancePayer->update([
                'last_attempt' => now()->toDateTimeString()
            ]);

            return $return;
        }

        DB::transaction(function () use ($insurancePayer, $calculatedAmount, $paymentResult, $lastDate, $days) {

            InsurancePayerPayment::create([
                'order_rule_id' => $insurancePayer->order_deposit_rule_id,
                'amount' => $calculatedAmount,
                'transaction_id' => $paymentResult['transaction_id']
            ]);

            $insurancePayer->update([
                'last_attempt' => now()->toDateTimeString(),
                'attepmt' => 1,
                'next' => date('Y-m-d', strtotime($lastDate . " + $days days"))
            ]);
        });

        return [
            "status" => true,
            "message" => "Amount : \${$calculatedAmount} is charged successfully",
            "pending_insu" => $calculatedAmount
        ];
    }
}
