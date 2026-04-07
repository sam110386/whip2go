<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\OrderDepositRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

trait MobileApi {

    public function IntercomCarousels() {
        // Model missing, returning placeholder
        return [
            'status' => 1,
            "message" => "",
            "result" => []
        ];
    }

    public function mobileWalletTermText() {
        $text = "Wallet term text coming soon..";
        return ['status' => 1, "message" => "", "result" => $text];
    }

    private function retryRental($queue, $error) {
        $csOrderId = $queue->id;
        $paidData = CsOrderPayment::getTotalRentalTax($csOrderId);
        
        $rent = (float) preg_replace("/[^0-9.]/", "", $queue->rent ?? 0);
        $damageFee = (float) preg_replace("/[^0-9.]/", "", $queue->damage_fee ?? 0);
        $uncleannessFee = (float) preg_replace("/[^0-9.]/", "", $queue->uncleanness_fee ?? 0);
        $tax = (float) preg_replace("/[^0-9.]/", "", $queue->tax ?? 0);
        $diaFee = (float) preg_replace("/[^0-9.]/", "", $queue->dia_fee ?? 0);

        $pendingRent = sprintf('%0.2f', (($rent + $damageFee + $uncleannessFee) - ($paidData['rent'] ?? 0)));
        $pendingTax = sprintf('%0.2f', ($tax - ($paidData['tax'] ?? 0)));
        $pendingDiaFee = sprintf('%0.2f', ($diaFee - ($paidData['dia_fee'] ?? 0)));
        
        $renterId = $queue->renter_id;
        $amount = (float) sprintf('%0.2f', ($pendingRent + $pendingTax + $pendingDiaFee));

        if ($pendingRent > 0 || $pendingTax > 0) {
            $rentResult = CsWallet::chargePartialFromWallet($renterId, $amount, $amount . ' rental amount from retryRental', $csOrderId, 2);
            if ($rentResult['status']) {
                $success = true;
                if ($rentResult['pending'] > 0) {
                    $success = false;
                }
                
                // Save payment info
                $payment = new CsOrderPayment();
                $payment->order_id = $csOrderId;
                $payment->amount = ($amount - $rentResult['pending']);
                $payment->transaction_id = $rentResult['transactions'];
                $payment->tax = $pendingTax;
                $payment->dia_fee = $pendingDiaFee;
                $payment->saveRentalTransaction();

                if ($success) {
                    $queue->update(['payment_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $error = true;
            }
        } else {
            $queue->update(['payment_status' => 1]);
        }
        return $error;
    }

    private function retryDeposit($amount, $renterId, $depositType, $csOrderId) {
        $result = ['status' => false];
        if ($depositType == "C") {
            $result = CsWallet::chargeFromWallet($renterId, $amount, $amount . ' deposit amount from retryDeposit', 1, $csOrderId);
        }

        if ($result['status'] ?? false) {
            $payment = new CsOrderPayment();
            $payment->order_id = $csOrderId;
            $payment->amount = $amount;
            $payment->transaction_id = $result['transactions'];
            $payment->type = 'C';
            $payment->saveDepositTransaction();
            return true;
        }
        return false;
    }

    private function retryInsurance($amount, $queue) {
        $rule = OrderDepositRule::where('cs_order_id', ($queue->parent_id ?: $queue->id))->first();
        if ($rule && in_array($rule->insurance_payer, [1, 3])) {
            return false;
        }

        $insuResult = CsWallet::chargePartialFromWallet($queue->renter_id, $amount, $amount . ' insurance fee from retryInsurance', $queue->id, 4);
        if ($insuResult['status']) {
            $payment = new CsOrderPayment();
            $payment->order_id = $queue->id;
            $payment->amount = ($amount - $insuResult['pending']);
            $payment->transaction_id = $insuResult['transactions'];
            $payment->payer_id = $queue->renter_id;
            $payment->saveInsuranceTransaction();
            return ($insuResult['pending'] <= 0);
        }
        return false;
    }

    private function retryInitialfee($queue, $error) {
        $paidInitial = CsOrderPayment::getTotalInitialFee($queue->id);
        $amountRaw = (float) preg_replace("/[^0-9.]/", "", $queue->initial_fee ?? 0);
        $taxRaw = (float) preg_replace("/[^0-9.]/", "", $queue->initial_fee_tax ?? 0);
        
        $amount = sprintf('%0.2f', ($amountRaw - ($paidInitial['initial_fee'] ?? 0)));
        $tax = sprintf('%0.2f', ($taxRaw - ($paidInitial['initial_fee_tax'] ?? 0)));
        $total = (float) ($amount + $tax);

        if ($total > 0) {
            $result = CsWallet::chargePartialFromWallet($queue->renter_id, $total, $total . ' initial amount from retryInitialfee', $queue->id, 3);
            if ($result['status']) {
                $payment = new CsOrderPayment();
                $payment->order_id = $queue->id;
                $payment->amount = ($total - $result['pending']);
                $payment->tax = $tax;
                $payment->transaction_id = $result['transactions'];
                $payment->saveInitialFeeTransaction();

                if ($result['pending'] <= 0) {
                    $queue->update(['infee_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $error = true;
            }
        } else {
            $queue->update(['infee_status' => 1]);
        }
        return $error;
    }

    private function retryToll($queue, $error) {
        $amount = (float) preg_replace("/[^0-9.]/", "", $queue->pending_toll ?? 0);
        if ($amount > 0) {
            $tollResult = CsWallet::chargePartialFromWallet($queue->renter_id, $amount, $amount . ' toll fee from retryToll', $queue->id, 6);
            if ($tollResult['status']) {
                $paid = $amount - $tollResult['pending'];
                $queue->update([
                    'pending_toll' => $tollResult['pending'],
                    'toll' => ((float) $queue->toll + $paid),
                    'toll_status' => ($tollResult['pending'] <= 0 ? 1 : 2)
                ]);
                
                $payment = new CsOrderPayment();
                $payment->saveTollTransaction($queue->id, $paid, $tollResult['transactions'], $queue->user_id);
                
                if ($tollResult['pending'] > 0) $error = true;
            } else {
                $error = true;
            }
        } else {
            $queue->update(['toll_status' => 1]);
        }
        return $error;
    }

    public function retryPendingPaymentFromWallet($queue) {
        $error = false;
        if ($queue->payment_status == 2) {
            $error = $this->retryRental($queue, $error);
        }
        
        if (!$error && $queue->dpa_status == 2) {
            $paidDeposit = CsOrderPayment::getTotalDeposit($queue->id);
            $balance = sprintf('%0.2f', (preg_replace("/[^0-9.]/", "", $queue->deposit ?? 0) - $paidDeposit));
            
            if ($queue->deposit_type == 'D') {
                // Dynamic deposit logic... (stub for now as model DynamicDeposit is not vetted)
                Log::info("DynamicDeposit retry not yet implemented for order " . $queue->id);
            } elseif ($balance > 0) {
                if ($this->retryDeposit($balance, $queue->renter_id, $queue->deposit_type, $queue->id)) {
                    $queue->update(['dpa_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $queue->update(['dpa_status' => 1]);
            }
        }

        if (!$error && $queue->insu_status == 2) {
            $paidInsu = CsOrderPayment::getTotalInsurance($queue->id);
            $pending = sprintf('%0.2f', (preg_replace("/[^0-9.]/", "", $queue->insurance_amt ?? 0) - $paidInsu));
            if ($pending > 0) {
                if ($this->retryInsurance($pending, $queue)) {
                    $queue->update(['insu_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $queue->update(['insu_status' => 1]);
            }
        }

        if (!$error && $queue->infee_status == 2) {
            $error = $this->retryInitialfee($queue, $error);
        }

        return $error;
    }

    public function _getCountryCounty() {
        return [
            'US' => [
                "title" => "USA",
                "state" => [
                    'AL'=> 'Alabama','AK' => 'Alaska','AZ' => 'Arizona','AR' => 'Arkansas','CA' => 'California','CO' => 'Colorado','CT' => 'Connecticut','DE' => 'Delaware','DC' => 'District Of Columbia',
                    'FL' => 'Florida','GA' => 'Georgia','HI' => 'Hawaii','ID' => 'Idaho','IL' => 'Illinois','IN' => 'Indiana','IA' => 'Iowa','KS' => 'Kansas',
                    'KY' => 'Kentucky','LA' => 'Louisiana','ME' => 'Maine','MD' => 'Maryland','MA' => 'Massachusetts','MI' => 'Michigan','MN' => 'Minnesota','MS' => 'Mississippi',
                    'MO' => 'Missouri','MT' => 'Montana','NE' => 'Nebraska','NV' => 'Nevada','NH' => 'New Hampshire','NJ' => 'New Jersey','NM' => 'New Mexico','NY' => 'New York',
                    'NC' => 'North Carolina','ND' => 'North Dakota','OH' => 'Ohio','OK' => 'Oklahoma','OR' => 'Oregon','PA' => 'Pennsylvania','RI' => 'Rhode Island',
                    'SC' => 'South Carolina','SD' => 'South Dakota','TN' => 'Tennessee','TX' => 'Texas','UT' => 'Utah','VT' => 'Vermont','VA' => 'Virginia','WA' => 'Washington',
                    'WV' => 'West Virginia','WI' => 'Wisconsin','WY' => 'Wyoming'
                ]
            ],
            'CA' => [
                "title" => "Canada",
                "state" => [
                    'AB'=> 'Alberta',"BC"=>"British Columbia","MB"=>"Manitoba","NB"=>"New Brunswick","NL"=>"Newfoundland and Labrador",
                    "NS"=>"Nova Scotia","NT"=>"Northwest Territories","NU"=>"Nunavut","ON"=>"Ontario","PE"=>"Prince Edward Island",
                    "QC"=>"Quebec","SK"=>"Saskatchewan","YT"=>"Yukon"
                ]
            ]
        ];
    }
}
