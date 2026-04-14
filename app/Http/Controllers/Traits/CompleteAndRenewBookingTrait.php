<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Ported from CakePHP app/Controller/Traits/CompleteAndRenewBooking.php
 *
 * Handles completing a booking cycle, processing payments, and auto-renewing.
 */
trait CompleteAndRenewBookingTrait
{
    /**
     * Complete a previous booking, process payments, and mark status.
     */
    public function _completePreviousBooking($orderid): array
    {
        $extra_mileage_fee = 0;
        $damage_fee = 0;
        $uncleanness_fee = 0;
        $return = ['status' => false, 'message' => 'sorry, you are not authorize user.', 'result' => []];

        $CsOrderTemp = $CsOrder = DB::table('cs_orders')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'cs_orders.vehicle_id')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'cs_orders.user_id')
            ->where('cs_orders.id', $orderid)
            ->where('cs_orders.status', 1)
            ->select(
                'cs_orders.*',
                'Vehicle.id as vehicle_table_id', 'Vehicle.make', 'Vehicle.model', 'Vehicle.year',
                'Vehicle.vin_no', 'Vehicle.user_id as vehicle_user_id', 'Vehicle.allowed_miles',
                'Vehicle.msrp', 'Vehicle.vehicleCostInclRecon', 'Vehicle.plate_number',
                'Vehicle.passtime_status', 'Vehicle.disclosure',
                'Owner.id as owner_id', 'Owner.first_name as owner_first_name', 'Owner.last_name as owner_last_name',
                'Owner.company_address as owner_address', 'Owner.company_city as owner_city',
                'Owner.company_state as owner_state', 'Owner.company_zip as owner_zip',
                'Owner.timezone', 'Owner.distance_unit', 'Owner.company_name',
                'Owner.representative_name', 'Owner.representative_role',
                'Owner.representative_sign', 'Owner.contact_number'
            )
            ->first();

        if (empty($CsOrder)) {
            return $return;
        }

        $CsOrderArr = (array) $CsOrder;
        $CsOrderTempArr = $CsOrderArr;

        $return = ['status' => true, 'message' => 'sorry, booking already completed.', 'result' => $CsOrderTempArr, 'deposit_auth' => ''];
        if (!in_array($CsOrderArr['status'], [0, 1])) {
            return $return;
        }

        $return = ['status' => true, 'message' => 'Your request processed successfully.', 'orderid' => $orderid, 'result' => $CsOrderTempArr, 'deposit_auth' => ''];

        // TODO: Replace with DepositRule service – calculateRentTaxOnComplete()
        $rentTax = $this->calculateRentTaxOnCompleteStub($CsOrderArr);

        $rent     = $rentTax['rent'];
        $tax      = $rentTax['tax'];
        $dia_fee  = $rentTax['dia_fee'];
        $extra_mileage_fee = $rentTax['extra_mileage_fee'];
        $emftax   = $rentTax['emf_tax'];

        $CsOrderArr['extra_mileage_fee'] = $extra_mileage_fee;
        $CsOrderArr['emf_tax']           = $emftax;
        $CsOrderArr['dia_insu']          = $rentTax['dia_insu'];
        $CsOrderArr['end_odometer']      = $rentTax['end_odometer'];
        $CsOrderArr['insurance_amt']     = $rentTax['insurance_amt'];
        $CsOrderTempArr['end_odometer']  = $rentTax['end_odometer'] > 0 ? $rentTax['end_odometer'] : $CsOrderTempArr['end_odometer'];
        $CsOrderArr['damage_fee']        = $damage_fee;
        $CsOrderArr['uncleanness_fee']   = $uncleanness_fee;
        $CsOrderArr['rent']              = $rent;
        $CsOrderArr['tax']               = $tax;
        $CsOrderArr['dia_fee']           = $dia_fee;
        $CsOrderArr['discount']          = $rentTax['discount'];
        $CsOrderArr['end_timing']        = $CsOrderTempArr['end_datetime'];
        $CsOrderArr['auto_renew']        = 1;
        $CsOrderArr['insurance_payer']   = $rentTax['insurance_payer'];
        $CsOrderArr['order_rule_id']     = $rentTax['order_rule_id'];

        // TODO: Replace with PaymentProcessor service – ChargeAmountOnCompleteForRenew()
        $payreturn = $this->chargeAmountOnCompleteForRenewStub($CsOrderArr, $CsOrderTempArr);

        $CsOrderArr['insurance_amt']   = !empty($payreturn['insurance_amt']) && ($CsOrderArr['insurance_amt'] <= $payreturn['insurance_amt']) ? $payreturn['insurance_amt'] : $CsOrderArr['insurance_amt'];
        $CsOrderArr['initial_fee']     = !empty($payreturn['initial_fee']) ? $payreturn['initial_fee'] : $CsOrderArr['initial_fee'];
        $CsOrderArr['initial_fee_tax'] = !empty($payreturn['initial_fee_tax']) ? $payreturn['initial_fee_tax'] : $CsOrderArr['initial_fee_tax'];
        $CsOrderArr['paid_amount']     = $rent + $tax + $uncleanness_fee + $damage_fee + $dia_fee;

        // Save payment transactions via DB
        $this->savePaymentTransactions($orderid, $CsOrderArr, $CsOrderTempArr, $payreturn, $emftax, $extra_mileage_fee, $rent, $tax, $dia_fee);

        if (($payreturn['status'] ?? '') == 'success') {
            $CsOrderArr['status'] = 3;
            $return = ['status' => true, 'message' => 'Your request processed successfully.', 'orderid' => $orderid, 'result' => $CsOrderTempArr, 'deposit_auth' => ''];
        } else {
            $CsOrderArr['details'] = $payreturn['message'] ?? '';
            $return = ['status' => false, 'message' => $payreturn['message'] ?? '', 'result' => $CsOrderTempArr, 'deposit_auth' => $payreturn['deposit_auth'] ?? ''];
        }

        $CsOrderArr['deposit']    = 0;
        $CsOrderArr['dpa_status'] = $payreturn['dpa_status'] ?? $CsOrderArr['dpa_status'];
        $CsOrderArr['insu_status']          = $payreturn['insu_status'] ?? $CsOrderArr['insu_status'];
        $CsOrderArr['emf_status']           = $payreturn['emf_status'] ?? $CsOrderArr['emf_status'];
        $CsOrderArr['infee_status']         = $payreturn['infee_status'] ?? $CsOrderArr['infee_status'];
        $CsOrderArr['payment_status']       = $payreturn['payment_status'] ?? $CsOrderArr['payment_status'];
        $CsOrderArr['dia_insu_status']      = $payreturn['dia_insu_status'] ?? $CsOrderArr['dia_insu_status'];
        $CsOrderArr['lateness_fee_status']  = $payreturn['latefee_status'] ?? $CsOrderArr['lateness_fee_status'];

        DB::table('cs_orders')->where('id', $orderid)->update([
            'status'             => $CsOrderArr['status'],
            'rent'               => $CsOrderArr['rent'],
            'tax'                => $CsOrderArr['tax'],
            'dia_fee'            => $CsOrderArr['dia_fee'],
            'extra_mileage_fee'  => $CsOrderArr['extra_mileage_fee'],
            'emf_tax'            => $CsOrderArr['emf_tax'],
            'dia_insu'           => $CsOrderArr['dia_insu'],
            'end_odometer'       => $CsOrderArr['end_odometer'],
            'insurance_amt'      => $CsOrderArr['insurance_amt'],
            'damage_fee'         => $CsOrderArr['damage_fee'],
            'uncleanness_fee'    => $CsOrderArr['uncleanness_fee'],
            'discount'           => $CsOrderArr['discount'],
            'end_timing'         => $CsOrderArr['end_timing'],
            'auto_renew'         => $CsOrderArr['auto_renew'],
            'insurance_payer'    => $CsOrderArr['insurance_payer'],
            'paid_amount'        => $CsOrderArr['paid_amount'],
            'deposit'            => 0,
            'dpa_status'         => $CsOrderArr['dpa_status'],
            'insu_status'        => $CsOrderArr['insu_status'],
            'emf_status'         => $CsOrderArr['emf_status'],
            'infee_status'       => $CsOrderArr['infee_status'],
            'payment_status'     => $CsOrderArr['payment_status'],
            'dia_insu_status'    => $CsOrderArr['dia_insu_status'],
            'lateness_fee_status' => $CsOrderArr['lateness_fee_status'],
            'initial_fee'        => $CsOrderArr['initial_fee'],
            'initial_fee_tax'    => $CsOrderArr['initial_fee_tax'],
        ]);

        if ($return['status']) {
            // TODO: Replace with Notifier service – updateIntercomeUserAttrbute()
            // Notifier::updateIntercomeUserAttrbute($CsOrderArr['renter_id'], ['Rental_Status' => 'Paid']);
            if (method_exists($this, '_getagreementForCompletedBooking')) {
                $this->_getagreementForCompletedBooking($CsOrderTempArr);
            }
        }

        Log::channel('daily')->info('completePreviousBooking', [
            'order' => $CsOrderArr, 'payreturn' => $payreturn, 'rentTax' => $rentTax,
        ]);

        return $return;
    }

    /**
     * Auto-renew a booking.
     */
    public function _OrderAutoRenew($OrderId, $extendDate = ''): array
    {
        // TODO: Replace with PaymentProcessor service instantiation
        // $this->PaymentProcessor = new PaymentProcessor();

        $result = $this->_completePreviousBooking($OrderId);

        Log::channel('daily')->info('completePreviousBookingResult', ['orderId' => $OrderId, 'result' => $result]);

        if (!$result['status']) {
            return $result;
        }

        $tempCsOrder = $result['result'];
        $start_datetime = $tempCsOrder['end_datetime'];
        $daysGap = abs(strtotime($tempCsOrder['end_datetime']) - strtotime($tempCsOrder['start_datetime']));
        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + $daysGap);

        $parentId = !empty($tempCsOrder['parent_id']) ? $tempCsOrder['parent_id'] : $tempCsOrder['id'];
        $duration = DB::table('order_deposit_rules')
            ->where('cs_order_id', $parentId)
            ->value('duration_opt');
        // TODO: Replace with OrderDepositRule service – nextDuration()
        if ($duration) {
            $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . ' ' . $duration . ' days'));
        }

        if (!empty($extendDate) && strtotime($extendDate) > strtotime($start_datetime)) {
            $end_datetime = date('Y-m-d H:i:s', strtotime($extendDate));
        }

        $hoursNeeded = intval((strtotime($end_datetime) - strtotime($start_datetime)) / 3600);
        if ($hoursNeeded >= 24) {
            $end_datetime = date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($end_datetime)) . ' ' . date('H:i:s', strtotime($start_datetime))));
        }

        $CsOrder = [
            'status'         => 1,
            'pickup_address' => $tempCsOrder['pickup_address'],
            'lat'            => $tempCsOrder['lat'],
            'lng'            => $tempCsOrder['lng'],
            'vehicle_name'   => $tempCsOrder['vehicle_name'],
            'user_id'        => $tempCsOrder['user_id'],
            'auto_renew'     => 0,
            'pto'            => $tempCsOrder['pto'],
            'currency'       => $tempCsOrder['currency'],
            'cc_token_id'    => $tempCsOrder['cc_token_id'],
            'start_datetime' => $start_datetime,
            'end_datetime'   => $end_datetime,
            'renter_id'      => $tempCsOrder['renter_id'],
            'vehicle_id'     => $tempCsOrder['vehicle_id'],
            'accepted_time'  => date('Y-m-d H:i:s'),
            'details'        => $tempCsOrder['details'],
            'start_timing'   => $start_datetime,
            'timezone'       => $tempCsOrder['timezone'],
            'parent_id'      => $tempCsOrder['parent_id'] ?: $tempCsOrder['id'],
            'deposit'        => $tempCsOrder['deposit'],
            'start_odometer' => ($tempCsOrder['end_odometer'] ?? 0) > 0 ? $tempCsOrder['end_odometer'] : 0,
        ];

        // TODO: Replace with DepositRule service – getFeeRenewBooking()
        $priceRulesAmt = $this->getFeeRenewBookingStub($CsOrder, $CsOrder['parent_id']);

        $CsOrder['rent']              = $priceRulesAmt['time_fee'] ?? 0;
        $CsOrder['tax']               = $priceRulesAmt['tax'] ?? 0;
        $CsOrder['dia_fee']           = $priceRulesAmt['dia_fee'] ?? 0;
        $CsOrder['extra_mileage_fee'] = $priceRulesAmt['extra_mileage_fee'] ?? 0;
        $CsOrder['emf_tax']           = $priceRulesAmt['emf_tax'] ?? 0;
        $CsOrder['insurance_amt']     = $priceRulesAmt['insurance_amt'] ?? 0;
        $CsOrder['discount']          = $priceRulesAmt['discount'] ?? 0;

        $increment_id = $tempCsOrder['increment_id'] . '-1';
        if ($tempCsOrder['parent_id']) {
            $increment_ids = explode('-', $tempCsOrder['increment_id']);
            $increment_id = $increment_ids[0] . '-' . (int)($increment_ids[1] + 1);
        }
        $CsOrder['increment_id'] = $increment_id;

        $CsOrderId = DB::table('cs_orders')->insertGetId($CsOrder);
        $result['renewed_order_id'] = $CsOrderId;

        // TODO: Replace with Notifier service – createIntercomeUserEvent()
        Log::channel('daily')->info('OrderAutoRenew - booking_autorenew event', [
            'booking_id' => $increment_id,
            'id'         => $CsOrderId,
            'start_date' => Carbon::parse($start_datetime)->timezone($tempCsOrder['timezone'])->format('Y-m-d H:i:s'),
            'end_date'   => Carbon::parse($end_datetime)->timezone($tempCsOrder['timezone'])->format('Y-m-d H:i:s'),
        ]);

        // TODO: Replace with PaymentProcessor service – checkAndProcessRenew()
        // $this->PaymentProcessor->checkAndProcessRenew(...)

        if ($CsOrder['start_odometer'] == 0) {
            // TODO: Replace with Passtime service – startPasstime()
        }

        if (in_array($tempCsOrder['passtime_status'] ?? -1, [0, 2])) {
            // TODO: Replace with Passtime service – ActivateVehicle()
            // TODO: Replace with Notifier service for starter_enabled event
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Stub methods for dependencies
    // ------------------------------------------------------------------

    /** TODO: Migrate DepositRule->calculateRentTaxOnComplete() */
    private function calculateRentTaxOnCompleteStub(array $csOrder): array
    {
        return [
            'rent' => $csOrder['rent'] ?? 0, 'tax' => $csOrder['tax'] ?? 0,
            'dia_fee' => $csOrder['dia_fee'] ?? 0, 'extra_mileage_fee' => 0,
            'emf_tax' => 0, 'dia_insu' => 0, 'end_odometer' => $csOrder['end_odometer'] ?? 0,
            'insurance_amt' => $csOrder['insurance_amt'] ?? 0, 'discount' => 0,
            'insurance_payer' => $csOrder['insurance_payer'] ?? 0,
            'order_rule_id' => $csOrder['order_rule_id'] ?? 0,
        ];
    }

    /** TODO: Migrate PaymentProcessor->ChargeAmountOnCompleteForRenew() */
    private function chargeAmountOnCompleteForRenewStub(array $csOrder, array $csOrderTemp): array
    {
        return ['status' => 'success', 'message' => '', 'deposit_auth' => '', 'currency' => $csOrder['currency'] ?? 'USD'];
    }

    /** TODO: Migrate DepositRule->getFeeRenewBooking() */
    private function getFeeRenewBookingStub(array $csOrder, $parentId): array
    {
        return [
            'time_fee' => 0, 'tax' => 0, 'dia_fee' => 0,
            'extra_mileage_fee' => 0, 'emf_tax' => 0,
            'insurance_amt' => 0, 'discount' => 0,
        ];
    }

    /**
     * Save all payment transaction records after completion.
     */
    private function savePaymentTransactions(int $orderid, array $CsOrderArr, array $CsOrderTempArr, array $payreturn, $emftax, $extra_mileage_fee, $rent, $tax, $dia_fee): void
    {
        $basePayment = [
            'cs_order_id' => $orderid,
            'currency'    => $payreturn['currency'] ?? ($CsOrderArr['currency'] ?? 'USD'),
            'renter_id'   => $CsOrderArr['renter_id'],
            'status'      => 1,
            'created'     => now(),
            'modified'    => now(),
        ];

        if (!empty($payreturn['insurance_transaction_id'])) {
            DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                'amount'         => $payreturn['insurance_amt'] ?? 0,
                'transaction_id' => $payreturn['insurance_transaction_id'],
                'payer_id'       => $payreturn['insu_payerid'] ?? null,
                'type'           => 4,
            ]));
        }

        if (!empty($payreturn['transaction_id'])) {
            if (($payreturn['new_payment'] ?? 0) == 1) {
                DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                    'amount'         => $CsOrderArr['paid_amount'],
                    'transaction_id' => $payreturn['transaction_id'],
                    'tax'            => ($tax - ($CsOrderTempArr['tax'] ?? 0)),
                    'dia_fee'        => $dia_fee,
                    'type'           => 2,
                ]));
            }
            if (($payreturn['new_payment'] ?? 0) == 3) {
                DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                    'amount'         => ($payreturn['balance_rent'] + $payreturn['balance_tax'] + $payreturn['balance_dia_fee']),
                    'transaction_id' => $payreturn['transaction_id'],
                    'tax'            => $payreturn['balance_tax'],
                    'dia_fee'        => $payreturn['balance_dia_fee'],
                    'type'           => 2,
                ]));
            }
            if (($payreturn['new_payment'] ?? 0) == 4) {
                DB::table('cs_order_payments')
                    ->where('cs_order_id', $orderid)
                    ->where('transaction_id', $payreturn['transaction_id'])
                    ->where('type', 2)
                    ->update(['amount' => $CsOrderArr['paid_amount'], 'rent' => $rent, 'tax' => $tax, 'dia_fee' => $dia_fee]);
            }
        }

        if (!empty($payreturn['emf_transaction_id'])) {
            if (($payreturn['new_emf_payment'] ?? 0) == 1) {
                DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                    'amount'         => ($CsOrderArr['extra_mileage_fee'] + $CsOrderArr['emf_tax']),
                    'transaction_id' => $payreturn['emf_transaction_id'],
                    'tax'            => $emftax,
                    'type'           => 16,
                ]));
            }
            if (($payreturn['new_emf_payment'] ?? 0) == 3) {
                DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                    'amount'         => ($payreturn['balance_emf'] + $payreturn['balance_emf_tax']),
                    'transaction_id' => $payreturn['emf_transaction_id'],
                    'tax'            => $payreturn['balance_emf_tax'],
                    'type'           => 16,
                ]));
            }
            if (($payreturn['new_emf_payment'] ?? 0) == 4) {
                DB::table('cs_order_payments')
                    ->where('cs_order_id', $orderid)
                    ->where('transaction_id', $payreturn['emf_transaction_id'])
                    ->where('type', 16)
                    ->update(['amount' => ($CsOrderArr['extra_mileage_fee'] + $CsOrderArr['emf_tax']), 'rent' => $extra_mileage_fee, 'tax' => $emftax]);
            }
        }

        if (!empty($payreturn['initial_fee_id'])) {
            DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                'amount'         => $CsOrderArr['initial_fee'] + $CsOrderArr['initial_fee_tax'],
                'transaction_id' => $payreturn['initial_fee_id'],
                'tax'            => $CsOrderArr['initial_fee_tax'],
                'type'           => 3,
            ]));
        }

        if (!empty($payreturn['dia_insu_transaction_id'])) {
            DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                'amount'         => $payreturn['dia_insu'] ?? 0,
                'transaction_id' => $payreturn['dia_insu_transaction_id'],
                'payer_id'       => $payreturn['insu_payerid'] ?? null,
                'type'           => 14,
            ]));
        }

        if (!empty($payreturn['latefee_transaction_id'])) {
            DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                'amount'         => $payreturn['latefee'] ?? 0,
                'transaction_id' => $payreturn['latefee_transaction_id'],
                'type'           => 19,
            ]));
        }

        if (!empty($payreturn['toll_transaction_id'])) {
            DB::table('cs_order_payments')->insert(array_merge($basePayment, [
                'amount'         => $payreturn['pending_toll'] ?? 0,
                'transaction_id' => $payreturn['toll_transaction_id'],
                'type'           => 6,
            ]));
            DB::table('cs_orders')->where('id', $orderid)->update([
                'toll'         => DB::raw('toll + ' . ($payreturn['pending_toll'] ?? 0)),
                'pending_toll' => DB::raw('pending_toll - ' . ($payreturn['pending_toll'] ?? 0)),
            ]);
        }
    }
}
