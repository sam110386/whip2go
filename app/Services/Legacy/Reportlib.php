<?php

namespace App\Services\Legacy;

use App\Models\Legacy\Report;
use function in_array;
use function is_array;


class Reportlib
{
    public static function getPaymentType($all = false, $key = false, $val = false)
    {
        $return = [
            1 => 'Deposit',
            2 => 'Rental Transaction',
            3 => 'Initial Fee',
            4 => 'Insurance Fee',
            5 => 'Cancelation fee',
            6 => 'Toll Fee',
            7 => 'Customer Balance Charge',
            8 => 'Toll Violation',
            9 => 'Red Light Violation',
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

    public static function getPaymentTypeAction($type = '', $rtype = '', $source = 'card')
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

        if ($type == 13) {
            $key = 3;
        }

        if ($type == 12) {
            $key = 8;
        }

        if ($type == 11) {
            $key = 4;
        }

        if ($rtype == 'C' && $source == 'card') {
            if (in_array($type, [1, 2, 3, 4, 5, 6, 7])) {
                $key = 2;
            }
            if (in_array($type, [8, 9, 10])) {
                $key = 9;
            }
        }

        if ($rtype == 'C' && $source == 'wallet') {
            if (in_array($type, [1, 2, 3, 4, 5, 6, 7])) {
                $key = 4;
            }
            if (in_array($type, [8, 9, 10])) {
                $key = 9;
            }
        }

        if ($rtype == 'D' && $source == 'card') {
            if (in_array($type, [1, 2, 3, 4, 5, 6, 7])) {
                $key = 2;
            }
            if (in_array($type, [8, 9, 10])) {
                $key = 9;
            }
        }

        if ($rtype == 'D' && $source == 'wallet') {
            if (in_array($type, [1, 2, 3, 4, 5, 6, 7])) {
                $key = 4;
            }
            if (in_array($type, [8, 9, 10])) {
                $key = 9;
            }
        }

        return $key ? ($cat[$key] ?? null) : null;
    }

    public static function saveAccountReportData(array $data): void
    {
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            return;
        }

        $dataToSave = [
            'user_id' => $data['user_id'],
            'rtype' => $data['rtype'] ?? 'D',
            'type' => $data['type'] ?? 1,
            'cs_order_id' => $data['cs_order_id'] ?? null,
            'amt' => $data['amt'] ?? 0,
            'note' => $data['note'] ?? null,
            'source' => $data['source'] ?? 'card',
        ];

        if (isset($data['created'])) {
            $dataToSave['created'] = $data['created'];
        }

        if (isset($data['transaction_id']) && is_array($data['transaction_id'])) {
            foreach ($data['transaction_id'] as $sd) {
                $dataToSave['transaction_id'] = $sd['transaction_id'] ?? null;
                $dataToSave['amt'] = $sd['amt'] ?? 0;
                $dataToSave['source'] = $sd['source'] ?? 'card';
                Report::create($dataToSave);
            }
        } else {
            $dataToSave['transaction_id'] = $data['transaction_id'] ?? null;
            Report::create($dataToSave);
        }

        return;
    }
}
