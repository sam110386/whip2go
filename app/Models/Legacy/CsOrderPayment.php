<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\Legacy\Common;
use App\Services\Legacy\Reportlib;
use App\Services\Legacy\ReportPayment;
use App\Services\Legacy\PaymentProcessor;
use App\Services\Legacy\EmailQueueService;
use App\Models\Legacy\CsPayoutTransaction;
use App\Models\Legacy\CsOrder;

class CsOrderPayment extends LegacyModel
{
    private $_type = [];
    private $orderid = '';
    private $transactionid = '';
    private $amount = 0;
    private $type = '';
    private $currency = 'USD';
    private $tax = 0;
    private $dia_fee = 0;
    private $payerid;
    private $renterid;
    private $chargedat;
    private $Reportlib;

    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_order_payments';
    protected $fillable = [
        'cs_order_id',
        'type',
        'amount',
        'rent',
        'tax',
        'dia_fee',
        'currency',
        'dealer_amt',
        'transaction_id',
        'payer_id',
        'txntype',
        'cs_transfer',
        'owner_payout_id',
        'status',
        'charged_at',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];
    protected $casts = [
        'id' => 'integer',
        'cs_order_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'amount' => 'float',
    ];

    public function csOrder(): BelongsTo
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id');
    }
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
    public function setRenterId($renterid)
    {
        $this->renterid = $renterid;
    }
    public function setChargedAt($chargedat)
    {
        $this->chargedat = $chargedat;
    }
    public function reset()
    {
        $this->orderid = null;
        $this->amount = null;
        $this->transactionid = null;
        $this->type = null;
        $this->currency = null;
        $this->tax = null;
        $this->dia_fee = null;
        $this->payerid = null;
        $this->renterid = null;
    }

    public static function getDepositTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 1)
            ->pluck('amount', 'transaction_id');
    }
    public static function getActiveDepositTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'transaction_id',
            'txntype',
            'charged_at',
            'currency',
            'dealer_amt'
        ])
            ->where('cs_order_id', $orderid)
            ->where('type', 1)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }
    public static function getRentalTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 2)
            ->pluck('amount', 'transaction_id');
    }
    public static function getInitialFeeTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 3)
            ->get([
                'id',
                'amount',
                'tax',
                'transaction_id'
            ]);
    }
    public static function getInsuranceTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 4)
            ->pluck('amount', 'transaction_id');
    }
    public static function getActiveDiaInsuranceTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'transaction_id',
            'payer_id',
            'charged_at',
            'currency',
            'dealer_amt'
        ])
            ->where('cs_order_id', $orderid)
            ->where('type', 14)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }
    public static function getActiveEmfTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'tax',
            'transaction_id',
            'payer_id',
            'charged_at',
            'currency',
            'dealer_amt'
        ])
            ->where('cs_order_id', $orderid)
            ->where('type', 16)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }
    public static function getActiveTollTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'transaction_id',
            'charged_at',
            'currency',
            'dealer_amt'
        ])
            ->where('cs_order_id', $orderid)
            ->where('type', 6)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }
    public static function getActiveLateFeeTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'transaction_id',
            'rent',
            'tax',
            'charged_at',
            'currency',
            'dealer_amt'
        ])
            ->where('cs_order_id', $orderid)
            ->where('type', 19)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }
    public static function getNoneTransferredDepositTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 1)
            ->where('cs_transfer', 0)
            ->where('txntype', 'C')
            ->get()
            ->keyBy('id');
    }
    public static function getTotalDeposit($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 1)
            ->where('status', 1)
            ->sum('amount');
    }
    public static function getTotalRentalTax($orderid)
    {
        $rows = self::where('cs_order_id', $orderid)
            ->where('type', 2)
            ->where('status', 1)
            ->get([
                'amount',
                'rent',
                'tax',
                'dia_fee'
            ]);


        if ($rows->isEmpty()) {
            return [
                'rent' => 0,
                'tax' => 0,
                'dia_fee' => 0
            ];
        }

        $amount = (float) $rows->sum('amount');
        $rent = (float) $rows->sum('rent');
        $tax = (float) $rows->sum('tax');
        $dia_fee = (float) $rows->sum('dia_fee');

        return [
            'rent' => min($rent, $amount),
            'tax' => $tax,
            'dia_fee' => $dia_fee
        ];
    }
    public static function getTotalInsurance($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 4)
            ->where('status', 1)
            ->sum('amount');
    }
    public static function getTotalDiaInsurance($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 14)
            ->where('status', 1)
            ->sum('amount');
    }
    public static function getTotalEmf($orderid)
    {
        $rows = self::where('cs_order_id', $orderid)
            ->where('type', 16)
            ->where('status', 1)
            ->get(['amount', 'tax']);

        if ($rows->isEmpty()) {
            return [
                'emf' => 0,
                'tax' => 0
            ];
        }

        $amount = (float) $rows->sum('amount');
        $tax = (float) $rows->sum('tax');

        return [
            'emf' => ($amount - $tax),
            'tax' => $tax
        ];
    }
    public static function getTotalInitialFee($orderid)
    {
        $rows = self::where('cs_order_id', $orderid)
            ->where('type', 3)
            ->where('status', 1)
            ->get(['amount', 'tax']);

        if ($rows->isEmpty()) {
            return [
                'initial_fee' => 0,
                'initial_fee_tax' => 0
            ];
        }

        $amount = (float) $rows->sum('amount');
        $tax = (float) $rows->sum('tax');

        return [
            'initial_fee' => ($amount - ($tax < $amount ? $tax : 0)),
            'initial_fee_tax' => $tax,
        ];
    }
    public static function getTotalToll($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 6)
            ->where('status', 1)
            ->sum('amount');
    }
    public static function getTotalPaidRental($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->whereIn('type', [2, 16])
            ->where('status', 1)
            ->sum('amount');
    }
    public static function getTotalPaidLateFee($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 19)
            ->where('status', 1)
            ->sum('amount');
    }
    public static function getActiveRentalTransaction($orderId)
    {
        return self::select([
            'id',
            'amount',
            'transaction_id',
            'rent',
            'tax',
            'dia_fee',
            'charged_at',
            'currency',
            'dealer_amt'
        ])
            ->where('cs_order_id', $orderId)
            ->where('type', 2)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }
    public static function getActiveInsuranceTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'transaction_id',
            'payer_id',
            'charged_at',
            'currency',
            'dealer_amt'
        ])
            ->where('cs_order_id', $orderid)
            ->where('type', 4)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }
    public static function getActiveInitialFeeTransaction($orderid)
    {
        return self::select([
            'id',
            'amount',
            'rent',
            'tax',
            'transaction_id',
            'charged_at',
            'currency',
            'dealer_amt'
        ])
            ->where('cs_order_id', $orderid)
            ->where('type', 3)
            ->where('status', 1)
            ->get()
            ->keyBy('id');
    }
    public static function copyDeposits($oldorderid, $neworderid)
    {
        $deposits = self::getActiveDepositTransaction($oldorderid);
        foreach ($deposits as $deposit) {
            self::create([
                'cs_order_id' => $neworderid,
                'type' => 1,
                'status' => 1,
                'amount' => $deposit->amount,
                'transaction_id' => $deposit->transaction_id,
                'currency' => $deposit->currency,
                'dealer_amt' => $deposit->dealer_amt,
                'charged_at' => $deposit->charged_at,
            ]);
        }

        self::where('cs_order_id', $oldorderid)
            ->where('type', 1)
            ->update(['status' => 3]);
    }
    public static function saveInPayoutTransaction($transaction)
    {
        $txnid = $transaction['transaction_id'];
        $orderid = $transaction['cs_order_id'] ?? ($transaction['order_id'] ?? 0);
        $type = $transaction['type'] ?? 1;
        $hitch = config('legacy.HITCH');
        $paymentProcessorObj = new PaymentProcessor();
        $result = $paymentProcessorObj->chargeRetrieve(['auth_token' => $txnid]);

        if (isset($result['metadata']) && isset($result['metadata']['payer_id']) && $result['metadata']['payer_id'] == $hitch) {
            $amt = ($result['amount_captured'] > 0) ? ($result['amount_captured'] / 100) : 0;
            CsPayoutTransaction::create([
                "cs_order_id" => $orderid,
                "type" => $type,
                "cs_payment_id" => 0,
                "user_id" => $result['metadata']['payer_id'],
                "amount" => 0,
                "refund" => $amt,
                'transaction_id' => $txnid,
                'transfer_id' => $result['source_transfer'] ?? '',
                'balance_transaction' => $result['balance_transaction'] ?? '',
                'destination_payment' => $result['source_transfer'] ?? ''
            ]);
        }
    }
    public static function assignBookingIdToPayoutTransaction($transaction)
    {
        $txnid = $transaction['transaction_id'];
        $orderid = $transaction['order_id'];
        $hitch = config('legacy.HITCH');
        $paymentProcessorObj = new PaymentProcessor();
        $result = $paymentProcessorObj->chargeRetrieve(['auth_token' => $txnid]);

        if (!isset($result['metadata']) || !isset($result['metadata']['payer_id']) || $result['metadata']['payer_id'] != $hitch) {
            return;
        }

        $findObj = CsPayoutTransaction::where('transaction_id', $txnid)
            ->where('cs_order_id', 0)
            ->where('user_id', $result['metadata']['payer_id'])
            ->first();

        if (empty($findObj)) {
            return;
        }

        $findObj->update(['cs_order_id' => $orderid]);
    }

    public function saveDepositTransaction()
    {
        if (empty($this->orderid) || empty($this->transactionid)) {
            return;
        }

        $emailQueue = new EmailQueueService();

        Reportlib::saveAccountReportData([
            "user_id" => $this->renterid,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 1,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {

                if ($transaction['amt'] <= 0) {
                    continue;
                }

                $chargedAtValue = $transaction['charged_at'] ?? now();
                $source = $transaction['source'] ?? 'card';
                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 1,
                    'amount' => $transaction['amt'],
                    'transaction_id' => $transaction['transaction_id'],
                    'txntype' => $this->type,
                    'currency' => $this->currency,
                    'charged_at' => $chargedAtValue
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $this->renterid,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 1,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 1
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 1,
                    'charged_at' => $chargedAtValue
                ]);

                if ($transaction['amt'] > 0) {
                    $msg = "Payment was successful for the deposit charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }
            return;
        }

        $chargedAtValue = $this->chargedat ?: now();
        $source = "card";

        $orderPayment = self::create([
            'cs_order_id' => $this->orderid,
            'type' => 1,
            'amount' => $this->amount,
            'transaction_id' => $this->transactionid,
            'txntype' => $this->type,
            'currency' => $this->currency,
            'charged_at' => $chargedAtValue
        ]);

        self::saveInPayoutTransaction([
            'transaction_id' => $this->transactionid,
            "order_id" => $this->orderid,
            "type" => 1
        ]);

        Reportlib::saveAccountReportData([
            "user_id" => $this->renterid,
            "cs_order_id" => $this->orderid,
            "rtype" => "C",
            "type" => 1,
            "transaction_id" => $this->transactionid,
            "amt" => $this->amount,
            "source" => $source
        ]);

        $msg = "Payment was successful for the deposit charges of your DriveItAway order ";
        $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

        ReportPayment::saveCharge([
            "orderid" => $this->orderid,
            "amount" => $this->amount,
            "transaction_id" => $this->transactionid,
            "source" => 'stripe',
            'type' => 1,
            'charged_at' => $chargedAtValue
        ]);
    }
    public function saveRentalTransaction()
    {
        $emailQueue = new EmailQueueService();

        Reportlib::saveAccountReportData([
            "user_id" => $this->renterid,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 2,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            $step = 1;

            foreach ($this->transactionid as $transaction) {
                if ($step++ == 2) {
                    $this->tax = 0;
                    $this->dia_fee = 0;
                }

                if ($transaction['amt'] <= 0) {
                    continue;
                }

                $rent = ($transaction['amt'] - $this->tax - $this->dia_fee) > 0 ? ($transaction['amt'] - $this->tax - $this->dia_fee) : 0;
                $chargedAtValue = $transaction['charged_at'] ?? now();
                $source = $transaction['source'] ?? 'card';

                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 2,
                    'amount' => $transaction['amt'],
                    'rent' => $rent,
                    'tax' => $this->tax,
                    'dia_fee' => $this->dia_fee,
                    'transaction_id' => $transaction['transaction_id'],
                    'charged_at' => $chargedAtValue,
                    'currency' => $this->currency
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $this->renterid,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 2,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 2
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 2,
                    'charged_at' => $chargedAtValue
                ]);

                if ($transaction['amt'] > 0) {
                    $msg = "Payment was successful for the usage charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }

            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid) && $this->amount > 0) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => 2,
                'amount' => $this->amount,
                'rent' => ($this->amount - $this->tax - $this->dia_fee),
                'tax' => $this->tax,
                'dia_fee' => $this->dia_fee,
                'transaction_id' => $this->transactionid,
                'charged_at' => $chargedAtValue,
                'currency' => $this->currency
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $this->renterid,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 2,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 2
            ]);

            $msg = "Payment was successful for the usage charges of your DriveItAway order ";
            $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 2,
                'charged_at' => $chargedAtValue
            ]);
        }
    }
    public function saveInitialFeeTransaction()
    {
        $emailQueue = new EmailQueueService();

        Reportlib::saveAccountReportData([
            "user_id" => $this->renterid,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 3,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {

            foreach ($this->transactionid as $transaction) {
                if ($transaction['amt'] <= 0) {
                    continue;
                }

                $taxVal = $transaction['tax'] ?? 0;
                $rentVal = ($transaction['amt'] > $taxVal) ? ($transaction['amt'] - $taxVal) : 0;
                $chargedAtValue = $transaction['charged_at'] ?? $this->chargedat ?? now();
                $source = $transaction['source'] ?? 'card';

                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 3,
                    'amount' => $transaction['amt'],
                    'tax' => $taxVal,
                    'rent' => $rentVal,
                    'transaction_id' => $transaction['transaction_id'],
                    'charged_at' => $chargedAtValue,
                    'currency' => $this->currency
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $this->renterid,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 3,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 3
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 3,
                    'charged_at' => $chargedAtValue
                ]);

                if ($transaction['amt'] > 0) {
                    $msg = "Payment was successful for the initial fee charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }

            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid) && $this->amount > 0) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';
            $rentVal = ($this->amount - $this->tax) > 0 ? ($this->amount - $this->tax) : 0;

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => 3,
                'amount' => $this->amount,
                'tax' => $this->tax,
                'rent' => $rentVal,
                'transaction_id' => $this->transactionid,
                'charged_at' => $chargedAtValue,
                'currency' => $this->currency
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $this->renterid,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 3,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 3
            ]);

            $msg = "Payment was successful for the initial fee charges of your DriveItAway order ";
            $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 3,
                'charged_at' => $chargedAtValue
            ]);
        }
    }
    public function saveInsuranceTransaction()
    {
        $renter_id = !empty($this->payerid) ? $this->payerid : $this->renterid;
        $emailQueue = new EmailQueueService();

        Reportlib::saveAccountReportData([
            "user_id" => $renter_id,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 4,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {
                if ($transaction['amt'] <= 0) {
                    continue;
                }

                $chargedAtValue = $transaction['charged_at'] ?? $this->chargedat ?? now();
                $source = $transaction['source'] ?? 'card';

                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 4,
                    'amount' => $transaction['amt'],
                    'transaction_id' => $transaction['transaction_id'],
                    'payer_id' => $this->payerid,
                    'charged_at' => $chargedAtValue,
                    'currency' => $this->currency
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $renter_id,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 4,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 4
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 4,
                    'charged_at' => $chargedAtValue,
                    "payer_id" => $this->payerid
                ]);

                if ($renter_id == $this->renterid && $transaction['amt'] > 0) {
                    $msg = "Payment was successful for the insurance charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }
            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid) && $this->amount > 0) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => 4,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'payer_id' => $this->payerid,
                'charged_at' => $chargedAtValue,
                'currency' => $this->currency
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $renter_id,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 4,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 4
            ]);

            if ($renter_id == $this->renterid) {
                $msg = "Payment was successful for the insurance charges of your DriveItAway order ";
                $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);
            }

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 4,
                'charged_at' => $chargedAtValue,
                "payer_id" => $this->payerid
            ]);
        }
    }
    public function saveDiaInsuranceTransaction()
    {
        $renter_id = !empty($this->payerid) ? $this->payerid : $this->renterid;
        $emailQueue = new EmailQueueService();

        Reportlib::saveAccountReportData([
            "user_id" => $renter_id,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 14,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {
                if ($transaction['amt'] <= 0) {
                    continue;
                }

                $chargedAtValue = $transaction['charged_at'] ?? $this->chargedat ?? now();
                $source = $transaction['source'] ?? 'card';

                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 14,
                    'amount' => $transaction['amt'],
                    'transaction_id' => $transaction['transaction_id'],
                    'payer_id' => $this->payerid,
                    'charged_at' => $chargedAtValue,
                    'currency' => $this->currency
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $renter_id,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 14,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 14
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 14,
                    'charged_at' => $chargedAtValue,
                    "payer_id" => $this->payerid
                ]);

                if ($renter_id == $this->renterid && $transaction['amt'] > 0) {
                    $msg = "Payment was successful for the insurance charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }
            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid) && $this->amount > 0) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => 14,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'payer_id' => $this->payerid,
                'charged_at' => $chargedAtValue,
                'currency' => $this->currency
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $renter_id,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 14,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 14
            ]);

            if ($renter_id == $this->renterid) {
                $msg = "Payment was successful for the insurance charges of your DriveItAway order ";
                $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);
            }

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 14,
                'charged_at' => $chargedAtValue,
                "payer_id" => $this->payerid
            ]);
        }
    }
    public function saveEmfTransaction()
    {
        $emailQueue = new EmailQueueService();
        Reportlib::saveAccountReportData([
            "user_id" => $this->renterid,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 16,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            $step = 1;
            foreach ($this->transactionid as $transaction) {
                if ($step++ == 2) {
                    $this->tax = 0;
                }
                if ($transaction['amt'] <= 0) {
                    continue;
                }

                $rent = ($transaction['amt'] - $this->tax) > 0 ? ($transaction['amt'] - $this->tax) : 0;
                $chargedAtValue = $transaction['charged_at'] ?? $this->chargedat ?? now();
                $source = $transaction['source'] ?? 'card';

                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 16,
                    'amount' => $transaction['amt'],
                    'rent' => $rent,
                    'tax' => $this->tax,
                    'transaction_id' => $transaction['transaction_id'],
                    'charged_at' => $chargedAtValue,
                    'currency' => $this->currency
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $this->renterid,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 16,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 16
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 16,
                    'charged_at' => $chargedAtValue
                ]);

                if ($transaction['amt'] > 0) {
                    $msg = "Payment was successful for the EMF charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }
            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid) && $this->amount > 0) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';
            $rent = ($this->amount - $this->tax) > 0 ? ($this->amount - $this->tax) : 0;

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => 16,
                'amount' => $this->amount,
                'rent' => $rent,
                'tax' => $this->tax,
                'transaction_id' => $this->transactionid,
                'charged_at' => $chargedAtValue,
                'currency' => $this->currency
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $this->renterid,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 16,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 16
            ]);

            $msg = "Payment was successful for the EMF charges of your DriveItAway order ";
            $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 16,
                'charged_at' => $chargedAtValue
            ]);
        }
    }
    public function saveLateFeeTransaction()
    {
        $emailQueue = new EmailQueueService();
        Reportlib::saveAccountReportData([
            "user_id" => $this->renterid,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 19,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            $step = 1;
            foreach ($this->transactionid as $transaction) {
                if ($step++ == 2) {
                    $this->tax = 0;
                    $this->dia_fee = 0;
                }
                if ($transaction['amt'] <= 0) {
                    continue;
                }

                $chargedAtValue = $transaction['charged_at'] ?? now();
                $source = $transaction['source'] ?? 'card';

                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 19,
                    'amount' => $transaction['amt'],
                    'rent' => $transaction['amt'],
                    'tax' => 0,
                    'dia_fee' => 0,
                    'transaction_id' => $transaction['transaction_id'],
                    'charged_at' => $chargedAtValue,
                    'currency' => $this->currency
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $this->renterid,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 19,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 19
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 19,
                    'charged_at' => $chargedAtValue
                ]);

                if ($transaction['amt'] > 0) {
                    $msg = "Payment was successful for the usage charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }
            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid) && $this->amount > 0) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => 19,
                'amount' => $this->amount,
                'rent' => $this->amount,
                'tax' => 0,
                'dia_fee' => 0,
                'transaction_id' => $this->transactionid,
                'charged_at' => $chargedAtValue,
                'currency' => $this->currency
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $this->renterid,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 19,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 2
            ]);

            $msg = "Payment was successful for the usage charges of your DriveItAway order ";
            $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 19,
                'charged_at' => $chargedAtValue
            ]);
        }
    }
    public function saveCancelTransaction()
    {
        $order = CsOrder::find($this->orderid);
        $renter_id = $order->renter_id ?? 0;

        Reportlib::saveAccountReportData([
            "user_id" => $renter_id,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 5,
            "transaction_id" => is_string($this->transactionid) ? $this->transactionid : "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {
                if ($transaction['amt']) {
                    $chargedAtValue = $transaction['charged_at'] ?? now();
                    $source = $transaction['source'] ?? 'card';

                    self::create([
                        'cs_order_id' => $this->orderid,
                        'type' => 5,
                        'amount' => $transaction['amt'],
                        'transaction_id' => $transaction['transaction_id'],
                        'charged_at' => $chargedAtValue
                    ]);

                    Reportlib::saveAccountReportData([
                        "user_id" => $renter_id,
                        "cs_order_id" => $this->orderid,
                        "rtype" => "C",
                        "type" => 5,
                        "transaction_id" => $transaction['transaction_id'],
                        "amt" => $transaction['amt'],
                        "source" => $source
                    ]);

                    if ($source == 'card') {
                        self::saveInPayoutTransaction([
                            'transaction_id' => $transaction['transaction_id'],
                            "order_id" => $this->orderid,
                            "type" => 5
                        ]);
                    }

                    ReportPayment::saveCharge([
                        "orderid" => $this->orderid,
                        "amount" => $transaction['amt'],
                        "transaction_id" => $transaction['transaction_id'],
                        "source" => ($source == 'card' ? 'stripe' : $source),
                        'type' => 5,
                        'charged_at' => $chargedAtValue
                    ]);
                }
            }
            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid)) {
            self::create([
                'cs_order_id' => $this->orderid,
                'type' => 5,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'charged_at' => now()
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $renter_id,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 5,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 5
            ]);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 5
            ]);
        }
    }
    public function saveTollTransaction()
    {
        $order = CsOrder::find($this->orderid);
        $renter_id = $order->renter_id ?? 0;
        $emailQueue = new EmailQueueService();

        Reportlib::saveAccountReportData([
            "user_id" => $renter_id,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 6,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {
                if ($transaction['amt'] <= 0) {
                    continue;
                }
                $chargedAtValue = $transaction['charged_at'] ?? $this->chargedat ?? now();
                $source = $transaction['source'] ?? 'card';

                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 6,
                    'currency' => $this->currency,
                    'amount' => $transaction['amt'],
                    'transaction_id' => $transaction['transaction_id'],
                    'charged_at' => $chargedAtValue
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $renter_id,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 6,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 6
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 6,
                    'charged_at' => $chargedAtValue
                ]);

                if ($transaction['amt'] > 0) {
                    $msg = "Payment was successful for the toll charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }
            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid)) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => 6,
                'currency' => $this->currency,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'charged_at' => $chargedAtValue
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $renter_id,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 6,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 6
            ]);

            $msg = "Payment was successful for the toll charges of your DriveItAway order ";
            $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 6,
                "charged_at" => $chargedAtValue
            ]);
        }
    }
    public function saveCustomerBalanceTransaction()
    {
        $order = CsOrder::find($this->orderid);
        $renter_id = $order->renter_id ?? 0;
        $emailQueue = new EmailQueueService();

        Reportlib::saveAccountReportData([
            "user_id" => $renter_id,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => 6,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {
                if ($transaction['amt'] <= 0) {
                    continue;
                }
                $chargedAtValue = $transaction['charged_at'] ?? $this->chargedat ?? now();
                $source = $transaction['source'] ?? 'card';

                $orderPayment = self::create([
                    'cs_order_id' => $this->orderid,
                    'type' => 6,
                    'amount' => $transaction['amt'],
                    'transaction_id' => $transaction['transaction_id'],
                    'charged_at' => $chargedAtValue
                ]);

                Reportlib::saveAccountReportData([
                    "user_id" => $renter_id,
                    "cs_order_id" => $this->orderid,
                    "rtype" => "C",
                    "type" => 6,
                    "transaction_id" => $transaction['transaction_id'],
                    "amt" => $transaction['amt'],
                    "source" => $source
                ]);

                if ($source == 'card') {
                    self::saveInPayoutTransaction([
                        'transaction_id' => $transaction['transaction_id'],
                        "order_id" => $this->orderid,
                        "type" => 6
                    ]);
                }

                ReportPayment::saveCharge([
                    "orderid" => $this->orderid,
                    "amount" => $transaction['amt'],
                    "transaction_id" => $transaction['transaction_id'],
                    "source" => ($source == 'card' ? 'stripe' : $source),
                    'type' => 6,
                    'charged_at' => $chargedAtValue
                ]);

                if ($transaction['amt'] > 0) {
                    $msg = "Payment was successful for the balance over due charges of your DriveItAway order ";
                    $emailQueue->saveEmailToQueue($orderPayment->id, $transaction['amt'], $msg, $this->orderid, $source);
                }
            }
            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid)) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => 6,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'charged_at' => $chargedAtValue
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $renter_id,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => 6,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            self::saveInPayoutTransaction([
                'transaction_id' => $this->transactionid,
                "order_id" => $this->orderid,
                "type" => 6
            ]);

            $msg = "Payment was successful for the balance over due charges of your DriveItAway order ";
            $emailQueue->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => 6,
                "charged_at" => $chargedAtValue
            ]);
        }
    }
    public function saveTDKTransaction()
    {
        $order = CsOrder::find($this->orderid);
        $renter_id = $order->renter_id ?? 0;

        Reportlib::saveAccountReportData([
            "user_id" => $renter_id,
            "cs_order_id" => $this->orderid,
            "rtype" => "D",
            "type" => $this->type,
            "transaction_id" => "",
            "amt" => $this->amount
        ]);

        if (is_array($this->transactionid)) {
            foreach ($this->transactionid as $transaction) {
                if ($transaction['amt']) {
                    $chargedAtValue = $transaction['charged_at'] ?? $this->chargedat ?? now();
                    $source = $transaction['source'] ?? 'card';

                    self::create([
                        'cs_order_id' => $this->orderid,
                        'type' => $this->type,
                        'amount' => $transaction['amt'],
                        'transaction_id' => $transaction['transaction_id'],
                        'charged_at' => $chargedAtValue
                    ]);

                    Reportlib::saveAccountReportData([
                        "user_id" => $renter_id,
                        "cs_order_id" => $this->orderid,
                        "rtype" => "C",
                        "type" => $this->type,
                        "transaction_id" => $transaction['transaction_id'],
                        "amt" => $transaction['amt'],
                        "source" => $source
                    ]);

                    ReportPayment::saveCharge([
                        "orderid" => $this->orderid,
                        "amount" => $transaction['amt'],
                        "transaction_id" => $transaction['transaction_id'],
                        "source" => ($source == 'card' ? 'stripe' : $source),
                        'type' => 7,
                        'charged_at' => $chargedAtValue
                    ]);
                }
            }
            return;
        }

        if (!empty($this->orderid) && !empty($this->transactionid)) {
            $chargedAtValue = $this->chargedat ?: now();
            $source = 'card';

            $orderPayment = self::create([
                'cs_order_id' => $this->orderid,
                'type' => $this->type,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'charged_at' => $chargedAtValue
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $renter_id,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => $this->type,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            $types = (new Common())->getPayoutTypeValue(1);
            $typeName = $types[$this->type] ?? 'fee';
            $msg = "Payment was successful for the {$typeName} charges of your DriveItAway order ";
            (new EmailQueueService())->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => $this->type,
                "charged_at" => $chargedAtValue
            ]);
        }
    }
}
