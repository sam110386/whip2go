<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parity with CakePHP `ReportsController::_details` / `_autorenewddetails`.
 */
class BookingReportDetailPresenter
{
    public static function payoutTypeLabels(): array
    {
        return [
            '1' => 'Deposit',
            '2' => 'Usage Transaction',
            '3' => 'Initial Fee',
            '4' => 'Insurance Fee',
            '5' => 'Cancelation fee',
            '6' => 'Toll Fee',
            '7' => 'Customer Balance Charge',
            '8' => 'Toll Violation',
            '9' => 'Red Light Violation',
            '10' => 'Parking Violation',
            '11' => 'Refund Balance',
            '12' => 'Driver Credit,',
            '13' => 'Geotab Monthly Fee',
            '14' => 'DIA Insurance Fee',
            '15' => 'Credit Card Chargebacks',
            '16' => 'Extra Usage Fee',
            '17' => 'Car Damage Fee',
            '18' => 'Hazardous Driving Fee',
            '19' => 'Ext/Late Fee',
            '20' => 'Vehicle Insurance Penalty',
            '21' => 'Credit Deposit to Virtual Card',
        ];
    }

    public static function insurancePayerLabel(?int $payer): string
    {
        $map = [
            0 => 'Driveitaway Fleet',
            1 => 'Dealer Direct',
            2 => 'Dealer Fleet',
            3 => 'BYOI via Driver',
            4 => 'BYOI via DIA',
            5 => 'BYOI via Driver Financed',
            6 => 'BYOI via broker DIA financed',
            7 => 'DIA Fleet Back Up',
        ];
        if ($payer === null) {
            return '';
        }

        return $map[$payer] ?? '';
    }

    public static function formatDateTime(?string $value, ?string $orderTimezone): string
    {
        if ($value === null || $value === '' || strpos($value, '0000-00-00') === 0) {
            return '—';
        }
        $tz = $orderTimezone ?: config('app.timezone');

        try {
            return Carbon::parse($value)->timezone($tz)->format('m/d/Y h:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    public static function formatExtLogDateTime(?string $value, ?string $sessionTz): string
    {
        if ($value === null || $value === '' || strpos($value, '0000-00-00') === 0) {
            return '--';
        }
        $tz = $sessionTz ?: config('app.timezone');

        try {
            return Carbon::parse($value)->timezone($tz)->format('m/d/Y h:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buildSingle(int $orderId): ?array
    {
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return null;
        }
        $renter = DB::table('users')->where('id', (int) $order->renter_id)->first();

        $realBookingId = (int) ($order->parent_id ?: $order->id);
        $ruleRow = DB::table('cs_order_deposit_rules')->where('cs_order_id', $realBookingId)->first();
        $OrderDepositRule = $ruleRow ? ['OrderDepositRule' => (array) $ruleRow] : null;

        $revshare = self::resolveRevShare((int) $order->user_id);
        $diAFee = 100 - $revshare;

        $paymentRows = DB::table('cs_order_payments')
            ->where('cs_order_id', $orderId)
            ->where('status', 1)
            ->where('type', '!=', 1)
            ->orderByDesc('id')
            ->get();

        $sums = self::summarizePayments($paymentRows, (int) $order->renter_id, $diAFee);
        $payments = self::groupPaymentsByType($paymentRows);

        $calculation = self::decodeCalculation($ruleRow !== null ? ($ruleRow->calculation ?? null) : null);
        $insurancePayer = self::insurancePayerLabel(isset($ruleRow->insurance_payer) ? (int) $ruleRow->insurance_payer : null);

        $extlogs = self::loadExtLogs([$orderId]);
        $Siblingbooking = [(int) $order->id => (string) ($order->increment_id ?? '')];

        $Promo = self::loadPromo((int) $order->renter_id);
        $extLogTz = (string) (session('default_timezone') ?: config('app.timezone'));
        $csorderArr = self::csorderArray($order, $renter);
        $feeTotals = self::feeTotalsFromOrderRow($csorderArr['CsOrder']);

        return array_merge([
            'csorder' => $csorderArr,
            'OrderDepositRule' => $OrderDepositRule,
            'calculation' => $calculation,
            'payments' => $payments,
            'paymentTypeValue' => self::payoutTypeLabels(),
            'insurance_payer' => $insurancePayer,
            'extlogs' => $extlogs,
            'Siblingbooking' => $Siblingbooking,
            'Promo' => $Promo,
            'extLogTz' => $extLogTz,
            'isAutorenew' => false,
        ], $sums, $feeTotals);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buildAutoRenew(int $orderId): ?array
    {
        $order = DB::table('cs_orders')->where('id', $orderId)->first();
        if (!$order) {
            return null;
        }
        $renter = DB::table('users')->where('id', (int) $order->renter_id)->first();

        $agg = DB::table('cs_orders')
            ->where(function ($q) use ($orderId) {
                $q->where('parent_id', $orderId)->orWhere('id', $orderId);
            })
            ->whereIn('status', [2, 3])
            ->selectRaw(
                'COALESCE(SUM(rent),0) as rent, COALESCE(SUM(dia_fee),0) as dia_fee, ' .
                'COALESCE(SUM(tax + emf_tax),0) as tax, COALESCE(SUM(initial_fee),0) as initial_fee, ' .
                'COALESCE(SUM(initial_fee_tax),0) as initial_fee_tax, COALESCE(SUM(extra_mileage_fee),0) as extra_mileage_fee, ' .
                'COALESCE(SUM(lateness_fee),0) as lateness_fee, COALESCE(SUM(discount),0) as discount, ' .
                'COALESCE(SUM(damage_fee),0) as damage_fee, COALESCE(SUM(uncleanness_fee),0) as uncleanness_fee, ' .
                'COALESCE(SUM(insurance_amt),0) as insurance_amt, COALESCE(SUM(dia_insu),0) as dia_insu, ' .
                'COALESCE(SUM(toll),0) as toll, COALESCE(SUM(pending_toll),0) as pending_toll, ' .
                'COALESCE(SUM(end_odometer),0) as end_odometer, COALESCE(SUM(initial_discount),0) as initial_discount'
            )
            ->first();

        $subOrders = [self::numericAggRow($agg)];

        $lastOrderRow = $order;
        if ((int) $order->status === 3) {
            $child = DB::table('cs_orders')->where('parent_id', $orderId)->orderByDesc('id')->first();
            if ($child) {
                $lastOrderRow = $child;
            }
        }

        $realBookingId = (int) ($order->parent_id ?: $order->id);
        $ruleRow = DB::table('cs_order_deposit_rules')->where('cs_order_id', $realBookingId)->first();
        $OrderDepositRule = $ruleRow ? ['OrderDepositRule' => (array) $ruleRow] : null;

        $Siblingbooking = DB::table('cs_orders')
            ->where(function ($q) use ($order) {
                $q->where('parent_id', (int) $order->id)->orWhere('id', (int) $order->id);
            })
            ->pluck('increment_id', 'id')
            ->map(function ($v) {
                return (string) $v;
            })
            ->all();

        $siblingIds = array_map('intval', array_keys($Siblingbooking));

        $revshare = self::resolveRevShare((int) $lastOrderRow->user_id);
        $diAFee = 100 - $revshare;

        $paymentRows = DB::table('cs_order_payments')
            ->whereIn('cs_order_id', $siblingIds === [] ? [0] : $siblingIds)
            ->where('status', 1)
            ->where('type', '!=', 1)
            ->orderByDesc('id')
            ->get();

        $sums = self::summarizePayments($paymentRows, (int) $order->renter_id, $diAFee);
        $payments = self::groupPaymentsByType($paymentRows);

        $calculation = self::decodeCalculation($ruleRow !== null ? ($ruleRow->calculation ?? null) : null);
        $insurancePayer = self::insurancePayerLabel(isset($ruleRow->insurance_payer) ? (int) $ruleRow->insurance_payer : null);

        $extlogs = self::loadExtLogs($siblingIds);
        $Promo = self::loadPromo((int) $order->renter_id);
        $extLogTz = (string) (session('default_timezone') ?: config('app.timezone'));

        $s0 = $subOrders[0];
        $initlafee = sprintf('%0.2f', (float) $s0['initial_fee'] + (float) $s0['initial_fee_tax']);
        $insufee = sprintf('%0.2f', (float) $s0['insurance_amt'] + (float) $s0['dia_insu']);
        $calcualted = [
            2 => $s0['rent'],
            3 => $initlafee,
            4 => $insufee,
            6 => (float) $s0['toll'] + (float) $s0['pending_toll'],
            16 => $s0['extra_mileage_fee'],
            19 => $s0['lateness_fee'],
        ];

        $feeTotals = self::feeTotalsFromAggRow($s0);

        return array_merge([
            'csorder' => self::csorderArray($order, $renter),
            'subOrders' => $subOrders,
            'lastOrder' => ['CsOrder' => (array) $lastOrderRow],
            'OrderDepositRule' => $OrderDepositRule,
            'calculation' => $calculation,
            'payments' => $payments,
            'paymentTypeValue' => self::payoutTypeLabels(),
            'insurance_payer' => $insurancePayer,
            'extlogs' => $extlogs,
            'Siblingbooking' => $Siblingbooking,
            'Promo' => $Promo,
            'extLogTz' => $extLogTz,
            'calcualted' => $calcualted,
            'isAutorenew' => true,
        ], $sums, $feeTotals);
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array{usageRent: float, usageTax: float, insufee: string, tolls: string, totalDues: string}
     */
    private static function feeTotalsFromOrderRow(array $c): array
    {
        $rent = (float) ($c['rent'] ?? 0) + (float) ($c['extra_mileage_fee'] ?? 0) + (float) ($c['dia_fee'] ?? 0) + (float) ($c['initial_fee'] ?? 0);
        $tax = (float) ($c['tax'] ?? 0) + (float) ($c['emf_tax'] ?? 0) + (float) ($c['initial_fee_tax'] ?? 0);
        $insufee = sprintf('%0.2f', (float) ($c['insurance_amt'] ?? 0) + (float) ($c['dia_insu'] ?? 0));
        $tolls = sprintf(
            '%0.2f',
            (float) ($c['toll'] ?? 0) + (float) ($c['pending_toll'] ?? 0) + (float) ($c['lateness_fee'] ?? 0)
            + (float) ($c['damage_fee'] ?? 0) + (float) ($c['uncleanness_fee'] ?? 0)
        );
        $totalDues = sprintf('%0.2f', (float) $tolls + (float) $insufee + $rent + $tax);

        return [
            'usageRent' => $rent,
            'usageTax' => $tax,
            'insufee' => $insufee,
            'tolls' => $tolls,
            'totalDues' => $totalDues,
        ];
    }

    /**
     * @param  array<string, mixed>  $s0
     * @return array{usageRent: float, usageTax: float, insufee: string, tolls: string, totalDues: string}
     */
    private static function feeTotalsFromAggRow(array $s0): array
    {
        $rent = (float) ($s0['rent'] ?? 0) + (float) ($s0['extra_mileage_fee'] ?? 0) + (float) ($s0['dia_fee'] ?? 0) + (float) ($s0['initial_fee'] ?? 0);
        $tax = (float) ($s0['tax'] ?? 0) + (float) ($s0['initial_fee_tax'] ?? 0);
        $insufee = sprintf('%0.2f', (float) ($s0['insurance_amt'] ?? 0) + (float) ($s0['dia_insu'] ?? 0));
        $tolls = sprintf(
            '%0.2f',
            (float) ($s0['toll'] ?? 0) + (float) ($s0['pending_toll'] ?? 0) + (float) ($s0['lateness_fee'] ?? 0)
            + (float) ($s0['damage_fee'] ?? 0) + (float) ($s0['uncleanness_fee'] ?? 0)
        );
        $totalDues = sprintf('%0.2f', (float) $tolls + (float) $insufee + $rent + $tax);

        return [
            'usageRent' => $rent,
            'usageTax' => $tax,
            'insufee' => $insufee,
            'tolls' => $tolls,
            'totalDues' => $totalDues,
        ];
    }

    private static function numericAggRow(?object $agg): array
    {
        if (!$agg) {
            return self::emptyAggRow();
        }
        $a = (array) $agg;
        foreach ($a as $k => $v) {
            $a[$k] = $v === null ? 0 : $v;
        }

        return $a;
    }

    /**
     * @return array<string, int|float|string>
     */
    private static function emptyAggRow(): array
    {
        return [
            'rent' => 0,
            'dia_fee' => 0,
            'tax' => 0,
            'initial_fee' => 0,
            'initial_fee_tax' => 0,
            'extra_mileage_fee' => 0,
            'lateness_fee' => 0,
            'discount' => 0,
            'damage_fee' => 0,
            'uncleanness_fee' => 0,
            'insurance_amt' => 0,
            'dia_insu' => 0,
            'toll' => 0,
            'pending_toll' => 0,
            'end_odometer' => 0,
            'initial_discount' => 0,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>|iterable<int, object>  $paymentRows
     * @return array{downpaymentPaid: float, totalGrandPaid: float, totalDiaFee: float, dealerPaidInsurance: float}
     */
    private static function summarizePayments(iterable $paymentRows, int $renterId, float $diAFee): array
    {
        $totalPaid = 0.0;
        $paidInitialFee = 0.0;
        $totalGrandPaid = 0.0;
        $totalDiaFee = 0.0;
        $dealerPaidInsurance = 0.0;

        foreach ($paymentRows as $pay) {
            $p = (array) $pay;
            $amount = (float) ($p['amount'] ?? 0);
            $tax = (float) ($p['tax'] ?? 0);
            $dia_fee = (float) ($p['dia_fee'] ?? 0);
            $type = (int) ($p['type'] ?? 0);
            $payerRaw = $p['payer_id'] ?? null;
            $payerId = ($payerRaw === null || $payerRaw === '') ? 0 : (int) $payerRaw;

            if ($payerId === 0 || $payerId === $renterId) {
                $totalGrandPaid += $amount;
            } else {
                $dealerPaidInsurance += $amount;
            }

            if (in_array($type, [2, 16], true)) {
                $totalPaid += ($amount - $tax - $dia_fee);
                $totalDiaFee += (($amount - $tax - $dia_fee) * $diAFee / 100);
            }
            if ($type === 3) {
                $paidInitialFee += ($amount - $tax);
                $totalDiaFee += (($amount - $tax - $dia_fee) * $diAFee / 100);
            }
        }

        return [
            'downpaymentPaid' => $totalPaid + $paidInitialFee,
            'totalGrandPaid' => $totalGrandPaid,
            'totalDiaFee' => $totalDiaFee,
            'dealerPaidInsurance' => $dealerPaidInsurance,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>|iterable<int, object>  $paymentRows
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function groupPaymentsByType(iterable $paymentRows): array
    {
        $out = [];
        foreach ($paymentRows as $pay) {
            $p = (array) $pay;
            $type = (string) ($p['type'] ?? '');
            $id = (string) ($p['id'] ?? '');
            $out[$type][$id] = $p;
        }

        return $out;
    }

    private static function resolveRevShare(int $ownerUserId): float
    {
        $rev = DB::table('rev_settings')->where('user_id', $ownerUserId)->value('rental_rev');
        if ($rev !== null && $rev !== '') {
            return (float) $rev;
        }

        return (float) config('legacy.owner_part', 85);
    }

    private static function decodeCalculation(?string $json): array
    {
        $defaults = [
            'write_down_allocation' => 0.0,
            'finance_allocation' => 0.0,
            'maintenance_allocation' => 0.0,
            'disposition_fee' => 0.0,
            'total_program_cost' => 0.0,
            'total_program_fee_with_dia' => 0.0,
            'total_program_fee_without_dia' => 0.0,
        ];
        if ($json === null || $json === '') {
            return $defaults;
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }

    /**
     * @return list<array{OrderExtlog: array<string, mixed>, Owner: array{first_name: string, last_name: string}}>
     */
    private static function loadExtLogs(array $orderIds): array
    {
        if ($orderIds === [] || !Schema::hasTable('cs_order_extlogs')) {
            return [];
        }

        $rows = DB::table('cs_order_extlogs as el')
            ->leftJoin('users as ow', 'ow.id', '=', 'el.owner')
            ->whereIn('el.cs_order_id', $orderIds)
            ->orderByDesc('el.id')
            ->select('el.*', 'ow.first_name as owner_first_name', 'ow.last_name as owner_last_name')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $a = (array) $r;
            $ownerFn = (string) ($a['owner_first_name'] ?? '');
            $ownerLn = (string) ($a['owner_last_name'] ?? '');
            unset($a['owner_first_name'], $a['owner_last_name']);
            $out[] = [
                'OrderExtlog' => $a,
                'Owner' => ['first_name' => $ownerFn, 'last_name' => $ownerLn],
            ];
        }

        return $out;
    }

    /**
     * @return array{PromotionRule: array<string, mixed>}|null
     */
    private static function loadPromo(int $renterId): ?array
    {
        if ($renterId <= 0 || !Schema::hasTable('promo_terms') || !Schema::hasTable('promotion_rules')) {
            return null;
        }

        $row = DB::table('promo_terms as pt')
            ->join('promotion_rules as pr', 'pr.id', '=', 'pt.promo_rule_id')
            ->where('pt.user_id', $renterId)
            ->where('pr.status', 1)
            ->select(['pr.*', 'pt.id as promo_term_id'])
            ->first();

        if (!$row) {
            return null;
        }

        return ['PromotionRule' => (array) $row];
    }

    /**
     * @return array{CsOrder: array<string, mixed>, User: array{first_name: string, last_name: string, contact_number: string}}
     */
    private static function csorderArray(object $order, ?object $renter): array
    {
        return [
            'CsOrder' => (array) $order,
            'User' => [
                'first_name' => (string) ($renter->first_name ?? ''),
                'last_name' => (string) ($renter->last_name ?? ''),
                'contact_number' => (string) ($renter->contact_number ?? ''),
            ],
        ];
    }
}
