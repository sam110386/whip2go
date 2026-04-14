<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class Reportlib
{
    public function getPaymentType($all = false, $key = false, $val = false): mixed
    {
        $return = [
            1  => 'Deposit',
            2  => 'Rental Transaction',
            3  => 'Initial Fee',
            4  => 'Insurance Fee',
            5  => 'Cancelation fee',
            6  => 'Toll Fee',
            7  => 'Customer Balance Charge',
            8  => 'Toll Violation',
            9  => 'Red Light Violation',
            10 => 'Parking Violation',
            11 => 'Paid From Wallet',
            12 => 'Payout Created',
            13 => 'Fund Added to Wallet',
            14 => 'Wallet Fund Refunded to Stripe',
            15 => 'Bad Debt Added',
            16 => 'Credit Given',
            17 => 'Geotab Fee',
            18 => 'Credit Card Chargebacks',
        ];

        if ($all) {
            return $return;
        }
        if ($key) {
            return $return[$key] ?? null;
        }
        if ($val) {
            $flipped = array_flip($return);
            return $flipped[$val] ?? null;
        }

        return null;
    }

    public function getPaymentTypeAction($type = '', $rtype = '', $source = 'card'): ?string
    {
        $cat = [
            1 => 'Order creation',
            2 => 'Credit Card Payment',
            3 => 'Wallet Transfer',
            4 => 'Wallet Debit',
            5 => 'Deduction',
            6 => 'Refund',
            7 => 'Order Closed',
            8 => 'Payout',
            9 => 'Violation',
        ];

        $key = 0;
        if ($type == 13) { $key = 3; }
        if ($type == 12) { $key = 8; }
        if ($type == 11) { $key = 4; }

        if ($rtype == 'C' && $source == 'card') {
            if (in_array($type, [1, 2, 3, 4, 5, 6, 7])) { $key = 2; }
            if (in_array($type, [8, 9, 10])) { $key = 9; }
        }
        if ($rtype == 'C' && $source == 'wallet') {
            if (in_array($type, [1, 2, 3, 4, 5, 6, 7])) { $key = 4; }
            if (in_array($type, [8, 9, 10])) { $key = 9; }
        }
        if ($rtype == 'D' && $source == 'card') {
            if (in_array($type, [1, 2, 3, 4, 5, 6, 7])) { $key = 2; }
            if (in_array($type, [8, 9, 10])) { $key = 9; }
        }
        if ($rtype == 'D' && $source == 'wallet') {
            if (in_array($type, [1, 2, 3, 4, 5, 6, 7])) { $key = 4; }
            if (in_array($type, [8, 9, 10])) { $key = 9; }
        }

        return $key ? ($cat[$key] ?? null) : null;
    }

    public function saveAccountReportData(array $obj): void
    {
        if (!isset($obj['user_id']) || empty($obj['user_id'])) {
            return;
        }

        $dataToSave = [
            'user_id'  => $obj['user_id'],
            'rtype'    => $obj['rtype'] ?? 'D',
            'type'     => $obj['type'] ?? 1,
            'cs_order_id'    => $obj['cs_order_id'] ?? null,
            'amt'            => $obj['amt'] ?? 0,
            'note'           => $obj['note'] ?? null,
            'source'         => $obj['source'] ?? 'card',
        ];

        if (isset($obj['created'])) {
            $dataToSave['created'] = $obj['created'];
        }

        if (is_array($obj['transaction_id'])) {
            foreach ($obj['transaction_id'] as $obc) {
                $dataToSave['transaction_id'] = $obc['transaction_id'] ?? null;
                $dataToSave['amt'] = $obc['amt'] ?? 0;
                $dataToSave['source'] = $obc['source'] ?? 'card';
                DB::table('reports')->insert($dataToSave);
            }
        } else {
            $dataToSave['transaction_id'] = $obj['transaction_id'] ?? null;
            DB::table('reports')->insert($dataToSave);
        }
    }
}
