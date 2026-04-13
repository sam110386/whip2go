<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\OrderDepositRule;
use App\Services\Legacy\PaymentProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
// use App\Libraries\PaymentProcessor;

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

    private function retryDiaInsurance($queue, $error) {
        $paidDiaInsurance = CsOrderPayment::getTotalDiaInsurance($queue->id);
        $amount = sprintf('%0.2f', (preg_replace("/[^0-9.]/", "", $queue->dia_insu ?? 0) - $paidDiaInsurance));
        
        if ($amount > 0) {
            $renterid = $queue->renter_id;
            $csOrderId = $queue->id;

            $rule = OrderDepositRule::where('cs_order_id', ($queue->parent_id ?: $queue->id))->first();
            if ($rule && in_array($rule->insurance_payer, [1, 3])) {
                return false;
            }
            
            $insuresult = CsWallet::chargePartialFromWallet($renterid, $amount, $amount . ' insurance fee from retryDiaInsurance', $csOrderId, 4);
            if ($insuresult['status']) {
                $return = true;
                if ($insuresult['pending'] > 0) {
                    $return = false;
                }
                
                $payment = new CsOrderPayment();
                $payment->order_id = $csOrderId;
                $payment->amount = ($amount - $insuresult['pending']);
                $payment->transaction_id = $insuresult['transactions'];
                $payment->payer_id = $renterid;
                $payment->saveDiaInsuranceTransaction();
                
                if ($return) {
                    $queue->update(['dia_insu_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $error = true;
            }
        } else {
            $queue->update(['dia_insu_status' => 1]);
        }
        return $error;
    }

    private function retryEmf($queue, $error) {
        $paidData = CsOrderPayment::getTotalEmf($queue->id);
        $emf = sprintf('%0.2f', (preg_replace("/[^0-9.]/", "", $queue->extra_mileage_fee ?? 0) - ($paidData['emf'] ?? 0)));
        $tax = sprintf('%0.2f', (preg_replace("/[^0-9.]/", "", $queue->emf_tax ?? 0) - ($paidData['tax'] ?? 0)));
        
        $renterid = $queue->renter_id;
        $csOrderId = $queue->id;

        if ($emf > 0 || $tax > 0) {
            $amount = sprintf('%0.2f', ($emf + $tax));
            $rentResult = CsWallet::chargePartialFromWallet($renterid, $amount, $amount . ' emf amount from retryEmf', $csOrderId, 2);
            
            if ($rentResult['status']) {
                $return = true;
                if ($rentResult['pending'] > 0) {
                    $return = false;
                }
                
                $payment = new CsOrderPayment();
                $payment->order_id = $csOrderId;
                $payment->amount = ($amount - $rentResult['pending']);
                $payment->transaction_id = $rentResult['transactions'];
                $payment->tax = $tax;
                $payment->saveEmfTransaction();
                
                if ($return) {
                    $queue->update(['emf_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $error = true;
            }
        } else {
            $queue->update(['emf_status' => 1]);
        }
        return $error;
    }

    private function retryToll($queue, $error) {
        $amount = (float) preg_replace("/[^0-9.]/", "", $queue->pending_toll ?? 0);
        $paidToll = (float) preg_replace("/[^0-9.]/", "", $queue->toll ?? 0);

        if ($amount > 0) {
            $tollResult = CsWallet::chargePartialFromWallet($queue->renter_id, $amount, $amount . ' toll fee from retryToll', $queue->id, 6);
            if ($tollResult['status']) {
                $paid = $amount - $tollResult['pending'];
                $queue->update([
                    'pending_toll' => $tollResult['pending'],
                    'toll' => sprintf('%0.2f', ($paidToll + $paid)),
                    'toll_status' => ($tollResult['pending'] <= 0 ? 1 : 2)
                ]);
                
                $payment = new CsOrderPayment();
                if (method_exists($payment, 'saveTollTransaction')) {
                    $payment->saveTollTransaction($queue->id, $paid, $tollResult['transactions'], $queue->user_id);
                }
                
                if ($tollResult['pending'] > 0) $error = true;
            } else {
                $error = true;
            }
        } else {
            $queue->update(['toll_status' => 1]);
        }
        return $error;
    }

    private function retryLatefee($queue, $error) {
        $paidData = CsOrderPayment::getTotalPaidLateFee($queue->id);
        $amount = sprintf('%0.2f', (preg_replace("/[^0-9.]/", "", $queue->lateness_fee ?? 0) - ($paidData ?? 0)));
        
        $renterid = $queue->renter_id;
        $csOrderId = $queue->id;
        
        if ($amount > 0) {
            $rentResult = CsWallet::chargePartialFromWallet($renterid, $amount, $amount . ' latefee amount from retryLatefee', $csOrderId, 19);
            if ($rentResult['status']) {
                $return = true;
                if ($rentResult['pending'] > 0) {
                    $return = false;
                }
                
                $payment = new CsOrderPayment();
                $payment->order_id = $csOrderId;
                $payment->amount = ($amount - $rentResult['pending']);
                $payment->transaction_id = $rentResult['transactions'];
                $payment->tax = 0;
                $payment->dia_fee = 0;
                if (method_exists($payment, 'saveLateFeeTransaction')) {
                    $payment->saveLateFeeTransaction();
                }
                
                if ($return) {
                    $queue->update(['lateness_fee_status' => 1]);
                } else {
                    $queue->update(['lateness_fee_status' => 2]);
                    $error = true;
                }
            } else {
                $error = true;
            }
        } else {
            $queue->update(['lateness_fee_status' => 1]);
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
                // Dynamic deposit logic
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

        if ($queue->emf_status == 2) {
            $error = $this->retryEmf($queue, $error);
        }

        if (!$error && $queue->dia_insu_status == 2) {
            $error = $this->retryDiaInsurance($queue, $error);
        }

        if (!$error && ($queue->pending_toll > 0 || $queue->toll_status == 2)) {
            $error = $this->retryToll($queue, $error);
        }

        if (!$error && $queue->lateness_fee_status == 2) {
            $error = $this->retryLatefee($queue, $error);
        }

        return $error;
    }

    public function processRetryPendingPayment($queue) {
        $error = false;
        $paymentProcessorObj = app(PaymentProcessor::class);
        
        if ($queue->payment_status == 2 || $queue->payment_status == 0) {
            $paidData = CsOrderPayment::getTotalRentalTax($queue->id);
            $pendingRent = sprintf('%0.2f', (($queue->rent + $queue->damage_fee + $queue->uncleanness_fee) - ($paidData['rent'] ?? 0)));
            $pendingTax = sprintf('%0.2f', ($queue->tax - ($paidData['tax'] ?? 0)));
            $pendingDiaFee = sprintf('%0.2f', ($queue->dia_fee - ($paidData['dia_fee'] ?? 0)));
            
            if ($pendingRent > 0 || $pendingTax > 0) {
                $return = $paymentProcessorObj->retryRental($pendingRent, $pendingTax, $pendingDiaFee, $queue->toArray());
                if (($return['status'] ?? '') == 'success') {
                    $queue->update(['payment_status' => 1]);
                } else {
                    $error = true;
                    $queue->update(['payment_status' => 2]);
                }
            } else {
                $queue->update(['payment_status' => 1]);
            }
        }
        
        if (!$error && $queue->dpa_status == 2) {
            $paidDeposit = CsOrderPayment::getTotalDeposit($queue->id);
            $balanceDeposit = sprintf('%0.2f', ($queue->deposit - $paidDeposit));

            if ($queue->deposit_type == 'D') {
                $queue->update(['dpa_status' => 1]);
                Log::info("DynamicDeposit retry via processor not yet fully implemented for order " . $queue->id);
            } elseif ($balanceDeposit > 0) {
                $return = $paymentProcessorObj->retryDeposit($balanceDeposit, $queue->renter_id, $queue->deposit_type, $queue->id, $queue->cc_token_id);
                if (($return['status'] ?? '') == 'success') {
                    $queue->update(['dpa_status' => 1]);
                }
            } else {
                $queue->update(['dpa_status' => 1]);
            }
        }

        if (!$error && ($queue->insu_status == 2 || $queue->insu_status == 0)) {
            $rule = OrderDepositRule::where('cs_order_id', ($queue->parent_id ?: $queue->id))->first();
            $insurancePayer = $rule ? $rule->insurance_payer : null;
            
            $queueArray = $queue->toArray();
            $queueArray['insurance_payer'] = $insurancePayer;
            
            $paidInsurance = CsOrderPayment::getTotalInsurance($queue->id);
            $pendingInsurance = sprintf('%0.2f', ($queue->insurance_amt - $paidInsurance));
            
            if ($pendingInsurance > 0) {
                $return = $paymentProcessorObj->retryInsurance($pendingInsurance, $queueArray);
                if (($return['status'] ?? '') == 'success') {
                    $queue->update(['insu_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $queue->update(['insu_status' => 1]);
            }
        }

        if (!$error && ($queue->infee_status == 2 || $queue->infee_status == 0)) {
            $paidInitialfee = CsOrderPayment::getTotalInitialFee($queue->id);
            $pendings = sprintf('%0.2f', ($queue->initial_fee - ($paidInitialfee['initial_fee'] ?? 0)));
            $pendingTax = sprintf('%0.2f', ($queue->initial_fee_tax - ($paidInitialfee['initial_fee_tax'] ?? 0)));
            
            if ($pendings > 0) {
                $return = $paymentProcessorObj->retryInitialfee($pendings, $queue->toArray(), $pendingTax);
                if (($return['status'] ?? '') == 'success') {
                    $queue->update(['infee_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $queue->update(['infee_status' => 1]);
            }
        }

        if (!$error && ($queue->pending_toll > 0 || $queue->toll_status == 2)) {
            $return = $paymentProcessorObj->retryTollfee($queue->pending_toll, $queue->toArray());
            if (($return['status'] ?? '') == 'success') {
                $pendingToll = $queue->pending_toll;
                $queue->update([
                    'toll' => DB::raw("toll + $pendingToll"),
                    'pending_toll' => 0,
                    'toll_status' => 1
                ]);
            } else {
                $error = true;
            }
        }

        if (!$error && $queue->dia_insu_status == 2) {
            $rule = OrderDepositRule::where('cs_order_id', ($queue->parent_id ?: $queue->id))->first();
            $insurancePayer = $rule ? $rule->insurance_payer : null;
            
            $queueArray = $queue->toArray();
            $queueArray['insurance_payer'] = $insurancePayer;
            
            $paidDiaInsurance = CsOrderPayment::getTotalDiaInsurance($queue->id);
            $pendingDiaInsurance = sprintf('%0.2f', ($queue->dia_insu - $paidDiaInsurance));
            
            if ($pendingDiaInsurance > 0) {
                $return = $paymentProcessorObj->retryDiaInsurance($pendingDiaInsurance, $queueArray);
                if (($return['status'] ?? '') == 'success') {
                    $queue->update(['dia_insu_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $queue->update(['dia_insu_status' => 1]);
            }
        }
        
        if ($queue->emf_status == 2 || $queue->emf_status == 0) {
            $paidData = CsOrderPayment::getTotalEmf($queue->id);
            $pendingRent = sprintf('%0.2f', ($queue->extra_mileage_fee - ($paidData['emf'] ?? 0)));
            $pendingTax = sprintf('%0.2f', ($queue->emf_tax - ($paidData['tax'] ?? 0)));
            
            if ($pendingRent > 0 || $pendingTax > 0) {
                $return = $paymentProcessorObj->retryEmf($pendingRent, $pendingTax, $queue->toArray());
                if (($return['status'] ?? '') == 'success') {
                    $queue->update(['emf_status' => 1]);
                } else {
                    $error = true;
                }
            } else {
                $queue->update(['emf_status' => 1]);
            }
        }

        if ($queue->lateness_fee_status == 2 || $queue->lateness_fee_status == 0) {
            $totalPaidLateFee = CsOrderPayment::getTotalPaidLateFee($queue->id);
            $pendingLateFee = $queue->lateness_fee - $totalPaidLateFee;
            
            if ($pendingLateFee > 0) {
                $return = $paymentProcessorObj->retryLatefee($pendingLateFee, $queue->toArray());
                if (($return['status'] ?? '') == 'success') {
                    $queue->update(['lateness_fee_status' => 1]);
                } else {
                    $error = true;
                    $queue->update(['lateness_fee_status' => 2]);
                }
            } else {
                $queue->update(['lateness_fee_status' => 1]);
            }
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
