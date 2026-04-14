<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Ported from CakePHP app/Controller/Traits/MobileApi.php
 *
 * Mobile API helpers for intercom, wallet retry logic, and country/state lists.
 */
trait MobileApiTrait
{
    /**
     * Fetch Intercom carousel configuration.
     */
    public function IntercomCarousels(): array
    {
        $all = DB::table('intercom_carousels')
            ->select('screen', 'intercom')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return [
            'status'  => 1,
            'message' => '',
            'result'  => $all,
        ];
    }

    /**
     * Return wallet terms text.
     */
    public function mobileWalletTermText(): array
    {
        return ['status' => 1, 'message' => '', 'result' => 'Wallet term text coming soon..'];
    }

    /**
     * Retry a failed rental payment from wallet.
     */
    private function retryRental(array $queue, bool $error): bool
    {
        $paidData = $this->getTotalRentalTaxFromDb($queue['CsOrder']['id']);

        $pendingRent   = sprintf('%0.2f', ((preg_replace('/[^0-9.]/', '', $queue['CsOrder']['rent']) + preg_replace('/[^0-9.]/', '', $queue['CsOrder']['damage_fee']) + preg_replace('/[^0-9.]/', '', $queue['CsOrder']['uncleanness_fee'])) - $paidData['rent']));
        $pendingTax    = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['tax']) - $paidData['tax']));
        $pendingDiaFee = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['dia_fee']) - $paidData['dia_fee']));

        $renterid  = $queue['CsOrder']['renter_id'];
        $CsOrderId = $queue['CsOrder']['id'];
        $CsOrder   = ['id' => $CsOrderId];
        $return    = false;
        $amount    = sprintf('%0.2f', ($pendingRent + $pendingTax + $pendingDiaFee));

        if ($pendingRent > 0 || $pendingTax > 0) {
            // TODO: Replace with CsWallet service – chargePartialFromWallet()
            $Rentresult = $this->chargePartialFromWalletStub($renterid, $amount, $amount . ' rental amount from retryRental', $CsOrderId, 2);
            if ($Rentresult['status']) {
                $return = true;
                if ($Rentresult['pending'] > 0) {
                    $return = false;
                }
                $this->saveRentalPayment($CsOrderId, ($amount - $Rentresult['pending']), $Rentresult['transactions'], $pendingTax, $pendingDiaFee, $queue['CsOrder']['renter_id'], $queue['CsOrder']['currency'] ?? 'USD');
            }
            if ($return) {
                $CsOrder['payment_status'] = 1;
            } else {
                $error = true;
            }
        } else {
            $CsOrder['payment_status'] = 1;
        }

        DB::table('cs_orders')->where('id', $CsOrderId)->update(['payment_status' => $CsOrder['payment_status'] ?? 2]);
        return $error;
    }

    /**
     * Retry a failed deposit payment.
     */
    private function retryDeposit($amount, $renterid, $deposit_type, $CsOrderId): bool
    {
        $return = false;
        $result = ['status' => false];

        if ($deposit_type == 'C') {
            // TODO: Replace with CsWallet service – chargeFromWallet()
            $result = $this->chargeFromWalletStub($renterid, $amount, $amount . ' deposit amount from retryDeposit', 1, $CsOrderId);
        }

        if ($result['status']) {
            $return = true;
            DB::table('cs_order_payments')->insert([
                'cs_order_id'    => $CsOrderId,
                'amount'         => $amount,
                'transaction_id' => $result['transactions'],
                'type'           => 1,
                'status'         => 1,
                'created'        => now(),
                'modified'       => now(),
            ]);
        }

        return $return;
    }

    /**
     * Retry a failed insurance payment.
     */
    private function retryInsurance($amount, array $CsOrder): bool
    {
        $return = false;

        $parentId = !empty($CsOrder['parent_id']) ? $CsOrder['parent_id'] : $CsOrder['id'];
        $OrderDepositRule = DB::table('order_deposit_rules')
            ->where('cs_order_id', $parentId)
            ->select('insurance_payer')
            ->first();

        if (!empty($OrderDepositRule) && in_array($OrderDepositRule->insurance_payer, [1, 3])) {
            return $return;
        }

        // TODO: Replace with CsWallet service – chargePartialFromWallet()
        $insuresult = $this->chargePartialFromWalletStub($CsOrder['renter_id'], $amount, $amount . ' insurance fee from retryInsurance', $CsOrder['id'], 4);

        if ($insuresult['status']) {
            $return = true;
            if ($insuresult['pending'] > 0) {
                $return = false;
            }
            DB::table('cs_order_payments')->insert([
                'cs_order_id'    => $CsOrder['id'],
                'amount'         => ($amount - $insuresult['pending']),
                'transaction_id' => $insuresult['transactions'],
                'payer_id'       => $CsOrder['renter_id'],
                'type'           => 4,
                'status'         => 1,
                'created'        => now(),
                'modified'       => now(),
            ]);
        }

        return $return;
    }

    /**
     * Retry a failed initial fee payment.
     */
    private function retryInitialfee(array $queue, bool $error): bool
    {
        $paidInitialfee = $this->getTotalInitialFeeFromDbMobile($queue['CsOrder']['id']);
        $amount = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['initial_fee']) - $paidInitialfee['initial_fee']));
        $tax    = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['initial_fee_tax']) - $paidInitialfee['initial_fee_tax']));
        $CsOrderId = $queue['CsOrder']['id'];
        $CsOrder   = ['id' => $CsOrderId];

        if ($amount > 0) {
            $return = false;
            // TODO: Replace with CsWallet service – chargePartialFromWallet()
            $result = $this->chargePartialFromWalletStub($queue['CsOrder']['renter_id'], ($amount + $tax), ($amount + $tax) . ' initial amount from retryInitialfee', $CsOrderId, 3);
            if ($result['status']) {
                $return = true;
                if ($result['pending'] > 0) {
                    $return = false;
                }
                DB::table('cs_order_payments')->insert([
                    'cs_order_id'    => $CsOrderId,
                    'amount'         => (($amount + $tax) - $result['pending']),
                    'transaction_id' => $result['transactions'],
                    'tax'            => $tax,
                    'type'           => 3,
                    'status'         => 1,
                    'renter_id'      => $queue['CsOrder']['renter_id'],
                    'currency'       => $queue['CsOrder']['currency'] ?? 'USD',
                    'created'        => now(),
                    'modified'       => now(),
                ]);
            }
            $CsOrder['infee_status'] = $return ? 1 : 2;
            if (!$return) {
                $error = true;
            }
        } else {
            $CsOrder['infee_status'] = 1;
        }

        DB::table('cs_orders')->where('id', $CsOrderId)->update(['infee_status' => $CsOrder['infee_status']]);
        return $error;
    }

    /**
     * Retry a failed DIA insurance payment.
     */
    private function retryDiaInsurance(array $queue, bool $error): bool
    {
        $paidDiaInsurance = $this->getTotalDiaInsuranceFromDb($queue['CsOrder']['id']);
        $amount = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['dia_insu']) - $paidDiaInsurance));
        $CsOrderId = $queue['CsOrder']['id'];
        $CsOrder   = ['id' => $CsOrderId];

        if ($amount > 0) {
            $return   = false;
            $renterid = $queue['CsOrder']['renter_id'];

            $parentId = !empty($queue['CsOrder']['parent_id']) ? $queue['CsOrder']['parent_id'] : $queue['CsOrder']['id'];
            $OrderDepositRule = DB::table('order_deposit_rules')
                ->where('cs_order_id', $parentId)
                ->select('insurance_payer')
                ->first();

            if (!empty($OrderDepositRule) && in_array($OrderDepositRule->insurance_payer, [1, 3])) {
                return $error;
            }

            // TODO: Replace with CsWallet service – chargePartialFromWallet()
            $insuresult = $this->chargePartialFromWalletStub($renterid, $amount, $amount . ' insurance fee from retryDiaInsurance', $CsOrderId, 4);
            if ($insuresult['status']) {
                $return = true;
                if ($insuresult['pending'] > 0) {
                    $return = false;
                }
                DB::table('cs_order_payments')->insert([
                    'cs_order_id'    => $CsOrderId,
                    'amount'         => $amount - $insuresult['pending'],
                    'transaction_id' => $insuresult['transactions'],
                    'payer_id'       => $renterid,
                    'type'           => 14,
                    'status'         => 1,
                    'created'        => now(),
                    'modified'       => now(),
                ]);
            }
            $CsOrder['dia_insu_status'] = $return ? 1 : 2;
            if (!$return) {
                $error = true;
            }
        } else {
            $CsOrder['dia_insu_status'] = 1;
        }

        DB::table('cs_orders')->where('id', $CsOrderId)->update(['dia_insu_status' => $CsOrder['dia_insu_status']]);
        return $error;
    }

    /**
     * Retry a failed EMF (extra mileage fee) payment.
     */
    private function retryEmf(array $queue, bool $error): bool
    {
        $paidData = $this->getTotalEmfFromDb($queue['CsOrder']['id']);
        $emf = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['extra_mileage_fee']) - $paidData['emf']));
        $tax = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['emf_tax']) - $paidData['tax']));
        $CsOrderId = $queue['CsOrder']['id'];
        $CsOrder   = ['id' => $CsOrderId];

        if ($emf > 0 || $tax > 0) {
            $return = false;
            $amount = sprintf('%0.2f', ($emf + $tax));
            // TODO: Replace with CsWallet service – chargePartialFromWallet()
            $Rentresult = $this->chargePartialFromWalletStub($queue['CsOrder']['renter_id'], $amount, $amount . ' emf amount from retryEmf', $CsOrderId, 2);
            if ($Rentresult['status']) {
                $return = true;
                if ($Rentresult['pending'] > 0) {
                    $return = false;
                }
                DB::table('cs_order_payments')->insert([
                    'cs_order_id'    => $CsOrderId,
                    'amount'         => ($amount - $Rentresult['pending']),
                    'transaction_id' => $Rentresult['transactions'],
                    'tax'            => $tax,
                    'type'           => 16,
                    'status'         => 1,
                    'renter_id'      => $queue['CsOrder']['renter_id'],
                    'currency'       => $queue['CsOrder']['currency'] ?? 'USD',
                    'created'        => now(),
                    'modified'       => now(),
                ]);
            }
            $CsOrder['emf_status'] = $return ? 1 : 2;
            if (!$return) {
                $error = true;
            }
        } else {
            $CsOrder['emf_status'] = 1;
        }

        DB::table('cs_orders')->where('id', $CsOrderId)->update(['emf_status' => $CsOrder['emf_status']]);
        return $error;
    }

    /**
     * Retry a failed toll payment.
     */
    private function retryToll(array $queue, bool $error): bool
    {
        $amount   = sprintf('%0.2f', preg_replace('/[^0-9.]/', '', $queue['CsOrder']['pending_toll']));
        $paidtoll = sprintf('%0.2f', preg_replace('/[^0-9.]/', '', $queue['CsOrder']['toll']));
        $CsOrderId = $queue['CsOrder']['id'];
        $CsOrder   = ['id' => $CsOrderId];

        if ($amount > 0) {
            $return   = false;
            $renterid = $queue['CsOrder']['renter_id'];

            // TODO: Replace with CsWallet service – chargePartialFromWallet()
            $tollresult = $this->chargePartialFromWalletStub($renterid, $amount, $amount . ' toll fee from retryToll', $CsOrderId, 6);
            if ($tollresult['status']) {
                $return = true;
                if ($tollresult['pending'] > 0) {
                    $CsOrder['pending_toll'] = $tollresult['pending'];
                    $CsOrder['toll'] = sprintf('%0.2f', ($paidtoll + ($amount - $tollresult['pending'])));
                    $return = false;
                } else {
                    $CsOrder['pending_toll'] = 0;
                    $CsOrder['toll'] = sprintf('%0.2f', ($paidtoll + $amount));
                }
                DB::table('cs_order_payments')->insert([
                    'cs_order_id'    => $CsOrderId,
                    'amount'         => ($amount - $tollresult['pending']),
                    'transaction_id' => $tollresult['transactions'],
                    'type'           => 6,
                    'status'         => 1,
                    'renter_id'      => $renterid,
                    'currency'       => $queue['CsOrder']['currency'] ?? 'USD',
                    'created'        => now(),
                    'modified'       => now(),
                ]);
            }
            $CsOrder['toll_status'] = $return ? 1 : 2;
            if (!$return) {
                $error = true;
            }
        } else {
            $CsOrder['toll_status'] = 1;
        }

        $updateData = ['toll_status' => $CsOrder['toll_status']];
        if (isset($CsOrder['pending_toll'])) {
            $updateData['pending_toll'] = $CsOrder['pending_toll'];
            $updateData['toll'] = $CsOrder['toll'];
        }
        DB::table('cs_orders')->where('id', $CsOrderId)->update($updateData);
        return $error;
    }

    /**
     * Retry a failed late fee payment.
     */
    private function retryLatefee(array $queue, bool $error): bool
    {
        $paidData = $this->getTotalPaidLateFeeFromDb($queue['CsOrder']['id']);
        $amount   = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['lateness_fee']) - $paidData));
        $CsOrderId = $queue['CsOrder']['id'];
        $CsOrder   = ['id' => $CsOrderId];
        $return    = false;

        if ($amount > 0) {
            // TODO: Replace with CsWallet service – chargePartialFromWallet()
            $Rentresult = $this->chargePartialFromWalletStub($queue['CsOrder']['renter_id'], $amount, $amount . ' latefee amount from retryLatefee', $CsOrderId, 19);
            if ($Rentresult['status']) {
                $return = true;
                if ($Rentresult['pending'] > 0) {
                    $return = false;
                }
                DB::table('cs_order_payments')->insert([
                    'cs_order_id'    => $CsOrderId,
                    'amount'         => ($amount - $Rentresult['pending']),
                    'transaction_id' => $Rentresult['transactions'],
                    'tax'            => 0,
                    'dia_fee'        => 0,
                    'type'           => 19,
                    'status'         => 1,
                    'renter_id'      => $queue['CsOrder']['renter_id'],
                    'currency'       => $queue['CsOrder']['currency'] ?? 'USD',
                    'created'        => now(),
                    'modified'       => now(),
                ]);
            }
            $CsOrder['lateness_fee_status'] = $return ? 1 : 2;
            if (!$return) {
                $error = true;
            }
            DB::table('cs_orders')->where('id', $CsOrderId)->update(['lateness_fee_status' => $CsOrder['lateness_fee_status']]);
        }

        return $error;
    }

    /**
     * Retry all pending payments from wallet for an order.
     */
    public function retryPendingPaymentFromWallet(array $queue): bool
    {
        $error = false;

        if ($queue['CsOrder']['payment_status'] == 2) {
            $error = $this->retryRental($queue, $error);
        }

        if (!$error && $queue['CsOrder']['dpa_status'] == 2) {
            $paidDeposit = $this->getTotalDepositFromDb($queue['CsOrder']['id']);
            $balanceDeposit = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['deposit']) - $paidDeposit));

            if ($queue['CsOrder']['deposit_type'] == 'D') {
                $failedDeposits = DB::table('dynamic_deposits')
                    ->where('cs_order_id', $queue['CsOrder']['id'])
                    ->where('status', '!=', 1)
                    ->get();
                $dpaStatus = 1;
                foreach ($failedDeposits as $failedDeposit) {
                    $fd = (array) $failedDeposit;
                    $return = $this->retryDeposit($fd['amount'], $queue['CsOrder']['renter_id'], 'C', $queue['CsOrder']['id']);
                    if ($return) {
                        DB::table('dynamic_deposits')->where('id', $fd['id'])->update(['status' => 1]);
                    } else {
                        $dpaStatus = 2;
                        $error = true;
                    }
                }
                DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['dpa_status' => $dpaStatus]);
            } elseif ($balanceDeposit > 0) {
                $return = $this->retryDeposit($balanceDeposit, $queue['CsOrder']['renter_id'], $queue['CsOrder']['deposit_type'], $queue['CsOrder']['id']);
                if ($return) {
                    DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['dpa_status' => 1]);
                }
            } else {
                DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['dpa_status' => 1]);
            }
        }

        if (!$error && $queue['CsOrder']['insu_status'] == 2) {
            $paidInsurance = $this->getTotalInsuranceFromDb($queue['CsOrder']['id']);
            $pendingInsurance = sprintf('%0.2f', (preg_replace('/[^0-9.]/', '', $queue['CsOrder']['insurance_amt']) - $paidInsurance));
            if ($pendingInsurance > 0) {
                $return = $this->retryInsurance($pendingInsurance, $queue['CsOrder']);
                DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['insu_status' => $return ? 1 : 2]);
                if (!$return) {
                    $error = true;
                }
            } else {
                DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['insu_status' => 1]);
            }
        }

        if (!$error && $queue['CsOrder']['infee_status'] == 2) {
            $error = $this->retryInitialfee($queue, $error);
        }

        if ($queue['CsOrder']['emf_status'] == 2) {
            $error = $this->retryEmf($queue, $error);
        }

        if (!$error && $queue['CsOrder']['dia_insu_status'] == 2) {
            $error = $this->retryDiaInsurance($queue, $error);
        }

        if (!$error && ($queue['CsOrder']['pending_toll'] > 0 || $queue['CsOrder']['toll_status'] == 2)) {
            $error = $this->retryToll($queue, $error);
        }

        if (!$error && $queue['CsOrder']['lateness_fee_status'] == 2) {
            $error = $this->retryLatefee($queue, $error);
        }

        return $error;
    }

    /**
     * Process retry of pending payments via PaymentProcessor (CC-based).
     */
    public function processRetryPendingPayment(array $queue): bool
    {
        $error = false;

        if (in_array($queue['CsOrder']['payment_status'], [2, 0])) {
            $paidData = $this->getTotalRentalTaxFromDb($queue['CsOrder']['id']);
            $pendingRent   = sprintf('%0.2f', (($queue['CsOrder']['rent'] + $queue['CsOrder']['damage_fee'] + $queue['CsOrder']['uncleanness_fee']) - $paidData['rent']));
            $pendingTax    = sprintf('%0.2f', ($queue['CsOrder']['tax'] - $paidData['tax']));
            $pendingDiaFee = sprintf('%0.2f', ($queue['CsOrder']['dia_fee'] - $paidData['dia_fee']));

            if ($pendingRent > 0 || $pendingTax > 0) {
                // TODO: Replace with PaymentProcessor service – retryRental()
                $return = $this->paymentProcessorRetryRentalStub($pendingRent, $pendingTax, $pendingDiaFee, $queue['CsOrder']);
                $payStatus = ($return['status'] ?? '') == 'success' ? 1 : 2;
                if ($payStatus == 2) {
                    $error = true;
                }
            } else {
                $payStatus = 1;
            }
            DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['payment_status' => $payStatus]);
        }

        if (!$error && $queue['CsOrder']['dpa_status'] == 2) {
            $paidDeposit = $this->getTotalDepositFromDb($queue['CsOrder']['id']);
            $balanceDeposit = sprintf('%0.2f', ($queue['CsOrder']['deposit'] - $paidDeposit));

            if ($queue['CsOrder']['deposit_type'] == 'D') {
                $failedDeposits = DB::table('dynamic_deposits')
                    ->where('cs_order_id', $queue['CsOrder']['id'])
                    ->where('status', '!=', 1)
                    ->get();
                $dpaStatus = 1;
                foreach ($failedDeposits as $failedDeposit) {
                    $fd = (array) $failedDeposit;
                    // TODO: Replace with PaymentProcessor service – retryDeposit()
                    $return = $this->paymentProcessorRetryDepositStub($fd['amount'], $queue['CsOrder']['renter_id'], 'C', $queue['CsOrder']['id'], $queue['CsOrder']['cc_token_id']);
                    if (($return['status'] ?? '') == 'success') {
                        DB::table('dynamic_deposits')->where('id', $fd['id'])->update(['status' => 1]);
                    } else {
                        $dpaStatus = 2;
                        $error = true;
                    }
                }
                DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['dpa_status' => $dpaStatus]);
            } elseif ($balanceDeposit > 0) {
                // TODO: Replace with PaymentProcessor service – retryDeposit()
                $return = $this->paymentProcessorRetryDepositStub($balanceDeposit, $queue['CsOrder']['renter_id'], $queue['CsOrder']['deposit_type'], $queue['CsOrder']['id'], $queue['CsOrder']['cc_token_id']);
                if (($return['status'] ?? '') == 'success') {
                    DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['dpa_status' => 1]);
                }
            } else {
                DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['dpa_status' => 1]);
            }
        }

        if (!$error && in_array($queue['CsOrder']['insu_status'], [2, 0])) {
            $parentId = !empty($queue['CsOrder']['parent_id']) ? $queue['CsOrder']['parent_id'] : $queue['CsOrder']['id'];
            $OrderDepositRule = DB::table('order_deposit_rules')
                ->where('cs_order_id', $parentId)
                ->select('insurance_payer')
                ->first();
            $queue['CsOrder']['insurance_payer'] = $OrderDepositRule->insurance_payer ?? 0;

            $paidInsurance = $this->getTotalInsuranceFromDb($queue['CsOrder']['id']);
            $pendingInsurance = sprintf('%0.2f', ($queue['CsOrder']['insurance_amt'] - $paidInsurance));

            if ($pendingInsurance > 0) {
                // TODO: Replace with PaymentProcessor service – retryInsurance()
                $return = $this->paymentProcessorRetryInsuranceStub($pendingInsurance, $queue['CsOrder']);
                $insuStatus = ($return['status'] ?? '') == 'success' ? 1 : 2;
                if ($insuStatus == 2) {
                    $error = true;
                }
            } else {
                $insuStatus = 1;
            }
            DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['insu_status' => $insuStatus]);
        }

        if (!$error && in_array($queue['CsOrder']['infee_status'], [2, 0])) {
            $paidInitialfee = $this->getTotalInitialFeeFromDbMobile($queue['CsOrder']['id']);
            $pendings   = sprintf('%0.2f', ($queue['CsOrder']['initial_fee'] - $paidInitialfee['initial_fee']));
            $pendingTax = sprintf('%0.2f', ($queue['CsOrder']['initial_fee_tax'] - $paidInitialfee['initial_fee_tax']));

            if ($pendings > 0) {
                // TODO: Replace with PaymentProcessor service – retryInitialfee()
                $return = $this->paymentProcessorRetryInitialfeeStub($pendings, $queue['CsOrder'], $pendingTax);
                $infeeStatus = ($return['status'] ?? '') == 'success' ? 1 : 2;
                if ($infeeStatus == 2) {
                    $error = true;
                }
            } else {
                $infeeStatus = 1;
            }
            DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['infee_status' => $infeeStatus]);
        }

        if (!$error && ($queue['CsOrder']['pending_toll'] > 0 || $queue['CsOrder']['toll_status'] == 2)) {
            // TODO: Replace with PaymentProcessor service – retryTollfee()
            $return = $this->paymentProcessorRetryTollfeeStub($queue['CsOrder']['pending_toll'], $queue['CsOrder']);
            if (($return['status'] ?? '') == 'success') {
                $pending_toll = $queue['CsOrder']['pending_toll'];
                DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])
                    ->update([
                        'toll'         => DB::raw("toll + $pending_toll"),
                        'pending_toll' => DB::raw("pending_toll - $pending_toll"),
                        'toll_status'  => 1,
                    ]);
            } else {
                $error = true;
            }
        }

        if (!$error && $queue['CsOrder']['dia_insu_status'] == 2) {
            $parentId = !empty($queue['CsOrder']['parent_id']) ? $queue['CsOrder']['parent_id'] : $queue['CsOrder']['id'];
            $OrderDepositRule = DB::table('order_deposit_rules')
                ->where('cs_order_id', $parentId)
                ->select('insurance_payer')
                ->first();
            $queue['CsOrder']['insurance_payer'] = $OrderDepositRule->insurance_payer ?? 0;

            $paidDiaInsurance = $this->getTotalDiaInsuranceFromDb($queue['CsOrder']['id']);
            $pendingDiaInsurance = sprintf('%0.2f', ($queue['CsOrder']['dia_insu'] - $paidDiaInsurance));

            if ($pendingDiaInsurance > 0) {
                // TODO: Replace with PaymentProcessor service – retryDiaInsurance()
                $return = $this->paymentProcessorRetryDiaInsuranceStub($pendingDiaInsurance, $queue['CsOrder']);
                $diaInsuStatus = ($return['status'] ?? '') == 'success' ? 1 : 2;
                if ($diaInsuStatus == 2) {
                    $error = true;
                }
            } else {
                $diaInsuStatus = 1;
            }
            DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['dia_insu_status' => $diaInsuStatus]);
        }

        if (in_array($queue['CsOrder']['emf_status'], [2, 0])) {
            $paidData    = $this->getTotalEmfFromDb($queue['CsOrder']['id']);
            $pendingRent = sprintf('%0.2f', ($queue['CsOrder']['extra_mileage_fee'] - $paidData['emf']));
            $pendingTax  = sprintf('%0.2f', ($queue['CsOrder']['emf_tax'] - $paidData['tax']));

            if ($pendingRent > 0 || $pendingTax > 0) {
                // TODO: Replace with PaymentProcessor service – retryEmf()
                $return = $this->paymentProcessorRetryEmfStub($pendingRent, $pendingTax, $queue['CsOrder']);
                $emfStatus = ($return['status'] ?? '') == 'success' ? 1 : 2;
                if ($emfStatus == 2) {
                    $error = true;
                }
            } else {
                $emfStatus = 1;
            }
            DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['emf_status' => $emfStatus]);
        }

        if (in_array($queue['CsOrder']['lateness_fee_status'], [2, 0])) {
            $TotalPaidLateFee = $this->getTotalPaidLateFeeFromDb($queue['CsOrder']['id']);
            $pendingLateFee   = $queue['CsOrder']['lateness_fee'] - $TotalPaidLateFee;

            if ($pendingLateFee > 0) {
                // TODO: Replace with PaymentProcessor service – retryLatefee()
                $return = $this->paymentProcessorRetryLatefeeStub($pendingLateFee, $queue['CsOrder']);
                $lateFeeStatus = ($return['status'] ?? '') == 'success' ? 1 : 2;
            } else {
                $lateFeeStatus = 1;
            }
            DB::table('cs_orders')->where('id', $queue['CsOrder']['id'])->update(['lateness_fee_status' => $lateFeeStatus]);
            if (($lateFeeStatus ?? 1) == 2) {
                $error = true;
            }
        }

        return $error;
    }

    /**
     * Return US and Canadian state lists.
     */
    public function _getCountryCounty(): array
    {
        return [
            'US' => [
                'title' => 'USA',
                'state' => [
                    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                    'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                    'DC' => 'District Of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
                    'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
                    'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
                    'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
                    'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
                    'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
                    'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
                    'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
                    'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas',
                    'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington',
                    'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
                ],
            ],
            'CA' => [
                'title' => 'Canada',
                'state' => [
                    'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba',
                    'NB' => 'New Brunswick', 'NL' => 'Newfoundland and Labrador',
                    'NS' => 'Nova Scotia', 'NT' => 'Northwest Territories', 'NU' => 'Nunavut',
                    'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
                    'SK' => 'Saskatchewan', 'YT' => 'Yukon',
                ],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // DB query helpers (replacing CakePHP model methods)
    // ------------------------------------------------------------------

    private function getTotalRentalTaxFromDb($csOrderId): array
    {
        $row = DB::table('cs_order_payments')
            ->where('cs_order_id', $csOrderId)
            ->where('type', 2)
            ->where('status', 1)
            ->selectRaw('COALESCE(SUM(rent), 0) as rent, COALESCE(SUM(tax), 0) as tax, COALESCE(SUM(dia_fee), 0) as dia_fee')
            ->first();
        return ['rent' => $row->rent ?? 0, 'tax' => $row->tax ?? 0, 'dia_fee' => $row->dia_fee ?? 0];
    }

    private function getTotalInitialFeeFromDbMobile($csOrderId): array
    {
        $row = DB::table('cs_order_payments')
            ->where('cs_order_id', $csOrderId)
            ->where('type', 3)
            ->where('status', 1)
            ->selectRaw('COALESCE(SUM(amount - tax), 0) as initial_fee, COALESCE(SUM(tax), 0) as initial_fee_tax')
            ->first();
        return ['initial_fee' => $row->initial_fee ?? 0, 'initial_fee_tax' => $row->initial_fee_tax ?? 0];
    }

    private function getTotalDiaInsuranceFromDb($csOrderId): float
    {
        return (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $csOrderId)
            ->where('type', 14)
            ->where('status', 1)
            ->sum('amount');
    }

    private function getTotalEmfFromDb($csOrderId): array
    {
        $row = DB::table('cs_order_payments')
            ->where('cs_order_id', $csOrderId)
            ->where('type', 16)
            ->where('status', 1)
            ->selectRaw('COALESCE(SUM(rent), 0) as emf, COALESCE(SUM(tax), 0) as tax')
            ->first();
        return ['emf' => $row->emf ?? 0, 'tax' => $row->tax ?? 0];
    }

    private function getTotalInsuranceFromDb($csOrderId): float
    {
        return (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $csOrderId)
            ->where('type', 4)
            ->where('status', 1)
            ->sum('amount');
    }

    private function getTotalDepositFromDb($csOrderId): float
    {
        return (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $csOrderId)
            ->where('type', 1)
            ->where('status', 1)
            ->sum('amount');
    }

    private function getTotalPaidLateFeeFromDb($csOrderId): float
    {
        return (float) DB::table('cs_order_payments')
            ->where('cs_order_id', $csOrderId)
            ->where('type', 19)
            ->where('status', 1)
            ->sum('amount');
    }

    private function saveRentalPayment($csOrderId, $amount, $transactionId, $tax, $diaFee, $renterId, $currency): void
    {
        DB::table('cs_order_payments')->insert([
            'cs_order_id'    => $csOrderId,
            'amount'         => $amount,
            'transaction_id' => $transactionId,
            'tax'            => $tax,
            'dia_fee'        => $diaFee,
            'type'           => 2,
            'status'         => 1,
            'renter_id'      => $renterId,
            'currency'       => $currency,
            'created'        => now(),
            'modified'       => now(),
        ]);
    }

    // ------------------------------------------------------------------
    // Stub methods for wallet and payment processor services
    // ------------------------------------------------------------------

    /** TODO: Replace with CsWallet service – chargePartialFromWallet() */
    private function chargePartialFromWalletStub($renterId, $amount, $description, $csOrderId, $type): array
    {
        return ['status' => false, 'pending' => $amount, 'transactions' => ''];
    }

    /** TODO: Replace with CsWallet service – chargeFromWallet() */
    private function chargeFromWalletStub($renterId, $amount, $description, $walletType, $csOrderId): array
    {
        return ['status' => false, 'transactions' => ''];
    }

    /** TODO: Replace with PaymentProcessor service – retryRental() */
    private function paymentProcessorRetryRentalStub($pendingRent, $pendingTax, $pendingDiaFee, array $csOrder): array
    {
        return ['status' => 'failed', 'message' => 'PaymentProcessor not yet connected.'];
    }

    /** TODO: Replace with PaymentProcessor service – retryDeposit() */
    private function paymentProcessorRetryDepositStub($amount, $renterId, $type, $csOrderId, $ccTokenId): array
    {
        return ['status' => 'failed'];
    }

    /** TODO: Replace with PaymentProcessor service – retryInsurance() */
    private function paymentProcessorRetryInsuranceStub($amount, array $csOrder): array
    {
        return ['status' => 'failed'];
    }

    /** TODO: Replace with PaymentProcessor service – retryInitialfee() */
    private function paymentProcessorRetryInitialfeeStub($amount, array $csOrder, $tax): array
    {
        return ['status' => 'failed'];
    }

    /** TODO: Replace with PaymentProcessor service – retryTollfee() */
    private function paymentProcessorRetryTollfeeStub($amount, array $csOrder): array
    {
        return ['status' => 'failed'];
    }

    /** TODO: Replace with PaymentProcessor service – retryDiaInsurance() */
    private function paymentProcessorRetryDiaInsuranceStub($amount, array $csOrder): array
    {
        return ['status' => 'failed'];
    }

    /** TODO: Replace with PaymentProcessor service – retryEmf() */
    private function paymentProcessorRetryEmfStub($pendingRent, $pendingTax, array $csOrder): array
    {
        return ['status' => 'failed'];
    }

    /** TODO: Replace with PaymentProcessor service – retryLatefee() */
    private function paymentProcessorRetryLatefeeStub($amount, array $csOrder): array
    {
        return ['status' => 'failed'];
    }
}
