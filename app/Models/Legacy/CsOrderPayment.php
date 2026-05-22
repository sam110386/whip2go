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
    public $orderid;
    public $transactionid;
    public $renterid;
    public $chargedat;
    public $payerid;
    public $typeid;
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

    // ---------------------------------------------------------------------
    // Legacy getter methods (mirroring original CakePHP API)
    // ---------------------------------------------------------------------
    public static function getDepositTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 1)
            ->first();
    }

    public static function getActiveDepositTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 1)
            ->where('status', 1)
            ->first();
    }

    public static function getRentalTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 2)
            ->first();
    }

    public static function getInitialFeeTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 3)
            ->first();
    }

    public static function getInsuranceTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 4)
            ->first();
    }

    public static function getActiveDiaInsuranceTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 14)
            ->where('status', 1)
            ->first();
    }

    public static function getActiveEmfTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 16)
            ->where('status', 1)
            ->first();
    }

    public static function getActiveTollTransaction($orderid)
    {
        return self::where('cs_order_id', $orderid)
            ->where('type', 19)
            ->where('status', 1)
            ->first();
    }

    public static function getActiveLateFeeTransaction($orderid)
    {
        // Late fee uses type 5 (balance over due) in this implementation
        return self::where('cs_order_id', $orderid)
            ->where('type', 5)
            ->where('status', 1)
            ->first();
    }

    public static function getNoneTransferredDepositTransaction($orderid)
    {
        // Deposits that have not yet been transferred (status 0)
        return self::where('cs_order_id', $orderid)
            ->where('type', 1)
            ->where('status', 0)
            ->first();
    }

    // ---------------------------------------------------------------------
    // Legacy total calculation methods
    // ---------------------------------------------------------------------
    public static function getTotalDeposit($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 1)
            ->sum('amount');
    }

    public static function getTotalRentalTax($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 2)
            ->sum('tax');
    }

    public static function getTotalInsurance($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 4)
            ->sum('amount');
    }

    public static function getTotalDiaInsurance($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 14)
            ->sum('amount');
    }

    public static function getTotalEmf($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 16)
            ->sum('amount');
    }

    public static function getTotalInitialFee($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 3)
            ->sum('amount');
    }

    public static function getTotalToll($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 19)
            ->sum('amount');
    }

    public static function getTotalPaidRental($orderid)
    {
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 2)
            ->where('status', 1)
            ->sum('amount');
    }

    public static function getTotalPaidLateFee($orderid)
    {
        // Late fee payments (type 5) that are marked as paid (status 1)
        return (float) self::where('cs_order_id', $orderid)
            ->where('type', 5)
            ->where('status', 1)
            ->sum('amount');
    }

    // ---------------------------------------------------------------------
    // End of legacy methods
    // ---------------------------------------------------------------------



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
    public function saveDepositTransaction()
    {
        if (empty($this->orderid) || empty($this->transactionid)) {
            return;
        }

        $emailQueue = app(EmailQueueService::class);

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

        $emailQueue = app(EmailQueueService::class);

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

        $emailQueue = app(EmailQueueService::class);

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

        $emailQueue = app(EmailQueueService::class);

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

        $emailQueue = app(EmailQueueService::class);

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

        $emailQueue = app(EmailQueueService::class);

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

        $emailQueue = app(EmailQueueService::class);

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


        $emailQueue = app(EmailQueueService::class);

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


        $emailQueue = app(EmailQueueService::class);

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
            "type" => $this->typeid,
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
                        'type' => $this->typeid,
                        'amount' => $transaction['amt'],
                        'transaction_id' => $transaction['transaction_id'],
                        'charged_at' => $chargedAtValue
                    ]);

                    Reportlib::saveAccountReportData([
                        "user_id" => $renter_id,
                        "cs_order_id" => $this->orderid,
                        "rtype" => "C",
                        "type" => $this->typeid,
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
                'type' => $this->typeid,
                'amount' => $this->amount,
                'transaction_id' => $this->transactionid,
                'charged_at' => $chargedAtValue
            ]);

            Reportlib::saveAccountReportData([
                "user_id" => $renter_id,
                "cs_order_id" => $this->orderid,
                "rtype" => "C",
                "type" => $this->typeid,
                "transaction_id" => $this->transactionid,
                "amt" => $this->amount
            ]);

            $types = app(Common::class)->getPayoutTypeValue(1);
            $typeName = $types[$this->typeid] ?? 'fee';
            $msg = "Payment was successful for the {$typeName} charges of your DriveItAway order ";
            app(EmailQueueService::class)->saveEmailToQueue($orderPayment->id, $this->amount, $msg, $this->orderid);

            ReportPayment::saveCharge([
                "orderid" => $this->orderid,
                "amount" => $this->amount,
                "transaction_id" => $this->transactionid,
                "source" => 'stripe',
                'type' => $this->typeid,
                "charged_at" => $chargedAtValue
            ]);
        }
    }

    public static function saveInPayoutTransaction($transaction)
    {
        $txnid = $transaction['transaction_id'];
        $orderid = $transaction['cs_order_id'] ?? ($transaction['order_id'] ?? 0);
        $type = $transaction['type'] ?? 1;
        $hitch = config('legacy.HITCH');

        $paymentProcessorObj = app(PaymentProcessor::class);
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

        $paymentProcessorObj = app(PaymentProcessor::class);
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
}
