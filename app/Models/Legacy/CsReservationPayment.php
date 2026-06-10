<?php

namespace App\Models\Legacy;

class CsReservationPayment extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_reservation_payments';

    protected $fillable = [
        'reservation_id',
        'type',
        'amount',
        'rent',
        'tax',
        'dia_fee',
        'currency',
        'transaction_id',
        'payer_id',
        'txntype',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    // Legacy state variables (matching CakePHP)
    public $orderid = '';
    public $amount = 0;
    public $transactionid = '';
    public $type = '';
    public $currency = 'USD';
    public $tax = 0;
    public $dia_fee = 0;
    public $payerid = null;

    // Legacy setters
    public function setOrderId($orderid)
    {
        $this->orderid = $orderid;
    }
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }
    public function setTransactionidId($transactionid)
    {
        $this->transactionid = $transactionid;
    }
    public function setType($type)
    {
        $this->type = $type;
    }
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
    public function setTax($tax)
    {
        $this->tax = $tax;
    }
    public function setDiaFee($dia_fee)
    {
        $this->dia_fee = $dia_fee;
    }
    public function setPayerId($payerid)
    {
        $this->payerid = $payerid;
    }

    // Save transaction methods
    public function saveDepositTransaction()
    {
        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {

                if (($transaction['amt'] ?? 0) <= 0) {
                    continue;
                }

                self::create([
                    'reservation_id' => $this->orderid,
                    'type' => 1,
                    'amount' => $transaction['amt'],
                    'transaction_id' => $transaction['transaction_id'] ?? null,
                    'txntype' => $this->type,
                    'currency' => $this->currency,
                    'created' => $transaction['charged_at'] ?? null,
                ]);

            }

            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid)) {
            self::create([
                'reservation_id' => $this->orderid,
                'type' => 1,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'txntype' => $this->type,
                'currency' => $this->currency,
            ]);
        }
    }
    public function saveInitialFeeTransaction()
    {
        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {

                if (($transaction['amt'] ?? 0) <= 0) {
                    continue;
                }

                $tTax = $transaction['tax'] ?? 0;
                $tRent = $transaction['amt'] - $tTax;

                self::create([
                    'reservation_id' => $this->orderid,
                    'type' => 3,
                    'amount' => $transaction['amt'],
                    'rent' => $tRent,
                    'tax' => $tTax,
                    'transaction_id' => $transaction['transaction_id'] ?? null,
                    'txntype' => $this->type,
                    'currency' => $this->currency,
                    'created' => $transaction['charged_at'] ?? null,
                ]);

            }

            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid)) {
            self::create([
                'reservation_id' => $this->orderid,
                'type' => 3,
                'amount' => $this->amount,
                'tax' => $this->tax,
                'rent' => ($this->amount - $this->tax),
                'transaction_id' => $this->transactionid,
                'txntype' => $this->type,
                'currency' => $this->currency,
            ]);
        }
    }
    public function saveRentalTransaction()
    {
        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {

                if (($transaction['amt'] ?? 0) <= 0) {
                    continue;
                }

                self::create([
                    'reservation_id' => $this->orderid,
                    'type' => 2,
                    'amount' => $transaction['amt'],
                    'rent' => $transaction['rent'] ?? 0,
                    'tax' => $this->tax,
                    'dia_fee' => $this->dia_fee,
                    'transaction_id' => $transaction['transaction_id'] ?? null,
                    'txntype' => $this->type,
                    'currency' => $this->currency,
                    'created' => $transaction['charged_at'] ?? null,
                ]);

            }

            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid)) {
            self::create([
                'reservation_id' => $this->orderid,
                'type' => 2,
                'amount' => $this->amount,
                'rent' => ($this->amount - $this->tax - $this->dia_fee),
                'tax' => $this->tax,
                'dia_fee' => $this->dia_fee,
                'transaction_id' => $this->transactionid,
                'txntype' => $this->type,
                'currency' => $this->currency,
            ]);
        }
    }
    public function saveInsuranceTransaction()
    {
        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {

                if (($transaction['amt'] ?? 0) <= 0) {
                    continue;
                }

                self::create([
                    'reservation_id' => $this->orderid,
                    'type' => 4,
                    'amount' => $transaction['amt'],
                    'transaction_id' => $transaction['transaction_id'] ?? null,
                    'txntype' => 'C',
                    'payer_id' => $transaction['payer_id'] ?? null,
                    'currency' => $this->currency,
                    'created' => $transaction['charged_at'] ?? null,
                ]);

            }

            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid)) {
            self::create([
                'reservation_id' => $this->orderid,
                'type' => 4,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'txntype' => $this->type,
                'payer_id' => $this->payerid,
                'currency' => $this->currency,
            ]);
        }
    }

    // Static query helper methods
    public static function getDepositTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'tax',
            'transaction_id',
            'txntype',
            'type',
            'created',
            'currency'
        ])
            ->where('reservation_id', $orderid)
            ->where('type', 1)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }

    public static function getInitialFeeTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'rent',
            'tax',
            'transaction_id',
            'txntype',
            'type',
            'created',
            'currency'
        ])
            ->where('reservation_id', $orderid)
            ->where('type', 3)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }

    public static function getRentalTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'rent',
            'tax',
            'dia_fee',
            'transaction_id',
            'txntype',
            'type',
            'created',
            'currency'
        ])
            ->where('reservation_id', $orderid)
            ->where('type', 2)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }

    public static function getInsuranceTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'tax',
            'transaction_id',
            'txntype',
            'type',
            'payer_id',
            'created',
            'currency'
        ])
            ->where('reservation_id', $orderid)
            ->where('type', 4)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }

    public static function getTotalRentalTax($orderid)
    {
        $totals = self::selectRaw('SUM(rent) as total_rent, SUM(tax) as total_tax, SUM(dia_fee) as total_dia_fee')
            ->where('reservation_id', $orderid)
            ->where('type', 2)
            ->where('status', 1)
            ->first();

        return [
            'rent' => sprintf('%0.2f', $totals->total_rent ?? 0),
            'tax' => sprintf('%0.2f', $totals->total_tax ?? 0),
            'dia_fee' => sprintf('%0.2f', $totals->total_dia_fee ?? 0),
        ];
    }

    public static function getTotalInsurance($orderid)
    {
        $sum = self::where('reservation_id', $orderid)
            ->where('type', 4)
            ->where('status', 1)
            ->sum('amount');

        return sprintf('%0.2f', $sum ?? 0);
    }
}
