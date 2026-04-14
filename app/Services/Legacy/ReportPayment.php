<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

/**
 * Port of CakePHP app/Lib/ReportPayment.php
 * Writes payment charge/refund records to the `payment_reports` table.
 */
class ReportPayment
{
    public static function saveCharge(array $data = []): void
    {
        if (empty($data) || !isset($data['orderid'])) {
            return;
        }
        DB::table('payment_reports')->insert([
            'cs_order_id'    => $data['orderid'],
            'type'           => $data['type'] ?? null,
            'amount'         => $data['amount'] ?? 0,
            'transaction_id' => $data['transaction_id'] ?? null,
            'payer_id'       => $data['payer_id'] ?? null,
            'source'         => $data['source'] ?? 'stripe',
            'txn_type'       => 1,
            'charged_at'     => (!empty($data['charged_at'])) ? $data['charged_at'] : now(),
            'description'    => $data['description'] ?? '',
        ]);
    }

    public static function saveWalletRefund(array $data = []): void
    {
        if (empty($data) || !isset($data['orderid'])) {
            return;
        }
        DB::table('payment_reports')->insert([
            'cs_order_id'    => $data['orderid'],
            'type'           => $data['type'] ?? null,
            'amount'         => -abs($data['amount'] ?? 0),
            'transaction_id' => $data['transaction_id'] ?? null,
            'payer_id'       => $data['payer_id'] ?? null,
            'source'         => $data['source'] ?? 'wallet',
            'txn_type'       => 2,
            'charged_at'     => (!empty($data['charged_at'])) ? $data['charged_at'] : now(),
            'description'    => $data['description'] ?? '',
        ]);
    }
}
