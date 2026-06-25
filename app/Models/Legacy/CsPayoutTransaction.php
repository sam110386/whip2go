<?php

namespace App\Models\Legacy;

use Exception;

class CsPayoutTransaction extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_payout_transactions';

    protected $fillable = [
        'cs_order_id',
        'cs_payment_id',
        'user_id',
        'type',
        'amount',
        'refund',
        'currency',
        'base_amt',
        'base_currency',
        'stripe_amt',
        'stripe_fee',
        'transaction_id',
        'transfer_id',
        'balance_transaction',
        'destination_payment',
        'cs_payout_id',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    public function csOrder()
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id', 'id');
    }


    public static function getActivePayoutTransactions($orderId, $csPaymentId)
    {
        return static::where('cs_order_id', $orderId)
            ->where('cs_payment_id', $csPaymentId)
            ->where('amount', '>', 0)
            ->where('status', 1)
            ->first();
    }

    public static function saveRefundPayoutTransactions($obj, $amount, $responseObj, $type = 11)
    {
        if (empty($amount)) {
            return null;
        }

        try {
            $dataToSave = [
                'cs_order_id' => $obj->cs_order_id ?? null,
                'cs_payment_id' => $obj->cs_payment_id ?? null,
                'user_id' => $obj->user_id ?? null,
                'type' => $obj->type ?? $type,
                'refund' => $amount,
                'transaction_id' => $obj->transaction_id ?? null,
                'transfer_id' => $responseObj['id'] ?? null,
                'balance_transaction' => $responseObj['balance_transaction'] ?? null,
                'destination_payment' => $responseObj['destination_payment_refund'] ?? null,
                'status' => 1,
                'currency' => $obj->currency ?? 'USD',
                'base_currency' => $obj->base_currency ?? 'USD',
                'base_amt' => isset($responseObj['amount']) ? ($responseObj['amount'] / 100) : $amount
            ];

            return static::create($dataToSave);

        } catch (Exception $e) {
            return null;
        }
    }

    public static function getAllActivePayoutTransactions($orderId, $type)
    {
        return static::where('cs_order_id', $orderId)
            ->where('type', $type)
            ->where('amount', '>', 0)
            ->where('status', 1)
            ->get();
    }
    public static function savePayoutTransactions($obj, $amount, $responseObj, $type = 11)
    {
        if (empty($amount)) {
            return null;
        }

        try {
            $dataToSave = [
                'cs_order_id' => $obj->cs_order_id ?? 0,
                'cs_payment_id' => 0,
                'amount' => 0,
                'user_id' => $obj->user_id ?? null,
                'type' => $type,
                'refund' => $amount,
                'transaction_id' => $responseObj['stripe_id'] ?? null,
                'transfer_id' => $responseObj['source_transfer'] ?? null,
                'balance_transaction' => $responseObj['balance_transaction'] ?? null,
                'destination_payment' => $responseObj['source_transfer'] ?? null,
                'status' => 1,
                'currency' => $obj->currency ?? 'USD',
            ];

            return static::create($dataToSave);

        } catch (Exception $e) {
            return null;
        }
    }

}
