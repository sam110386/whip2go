<?php

namespace App\Models\Legacy;
use App\Services\Legacy\Common;
use App\Services\Legacy\IntercomClient;


class CsPaymentLog extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_payment_logs';

    protected $fillable = [
        'cs_order_id',
        'type',
        'amount',
        'transaction_id',
        'old_transaction_id',
        'refund_transaction_id',
        'note',
        'status',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    public function csOrder()
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id');
    }

    public static function savePaymentLog($obj): void
    {
        $orderId = $obj['orderid'] ?? null;
        $type = $obj['type'] ?? null;
        $amount = $obj['amount'] ?? null;
        $status = $obj['status'] ?? null;

        $saveTo = [
            'cs_order_id' => $orderId,
            'type' => $type,
            'amount' => $amount,
            'transaction_id' => $obj['transaction_id'] ?? '',
            'old_transaction_id' => $obj['old_transaction_id'] ?? '',
            'refund_transaction_id' => $obj['refundtransactionid'] ?? '',
            'status' => $status,
            'note' => $obj['note'] ?? null,
        ];

        if ($orderId) {
            self::create($saveTo);
        }

        if (!empty($orderId)) {
            $commonObj = new Common();
            $paymentType = $commonObj->getpaymentTypeValue(false, $type, false);
            $orderData = CsOrder::select('increment_id', 'renter_id')
                ->where('id', $orderId)
                ->first();

            if ($orderData) {
                $opt = [
                    "event_name" => $status == 2 ? "payment_failed" : "payment_made",
                    "created_at" => time(),
                    "external_id" => $orderData->renter_id,
                    "user_id" => $orderData->renter_id,
                    "metadata" => [
                        "booking_id" => $orderId,
                        "increment_id" => $orderData->increment_id,
                        "payment_type" => $paymentType,
                        "amount" => $amount,
                        "error" => ($status == 2 ? ($obj['note'] ?? '') : "")
                    ]
                ];

                $intercomObj = app(IntercomClient::class);
                $intercomObj->createEvents($opt);
            }
        }
    }
    public static function logdatainCsTwilioModel($orderDta)
    {
        $alreadySent = CsTwilioOrder::where('cs_order_id', $orderDta['id'])->first();

        if ($alreadySent) {
            return $alreadySent->id;
        }

        $twilioLog = $orderDta;
        unset($twilioLog['created'], $twilioLog['modified'], $twilioLog['status']);
        $twilioLog['renter_phone'] = $orderDta['Renter']['contact_number'] ?? null;
        $twilioLog['cs_order_id'] = $orderDta['id'];
        $twilioLog['status'] = 0;
        $newTwilioLog = CsTwilioOrder::create($twilioLog);
        return $newTwilioLog->id;
    }
    public static function savePartialPaymentLog($obj, $type = 29): void
    {
        if (empty($obj['orderid'])) {
            return;
        }

        $saveTo = [
            'cs_order_id' => $obj['orderid'],
            'type' => $type,
            'amount' => $obj['amount'] ?? 0,
            'transaction_id' => $obj['transaction_id'] ?? '',
            'status' => 1,
            'note' => $obj['note'] ?? null,
            'payer_id' => $obj['payer_id'] ?? null,
            'refund_transaction_id' => $obj['refundtransactionid'] ?? '',
        ];

        if (!empty($obj['created'])) {
            $saveTo['created'] = $obj['created'];
        }

        self::create($saveTo);
    }
    public static function saveOnlyPaymentLog($obj, $type = 29): void
    {
        $saveTo = [
            'cs_order_id' => !empty($obj['orderid']) ? $obj['orderid'] : 0,
            'type' => $type,
            'amount' => $obj['amount'] ?? 0,
            'transaction_id' => $obj['transaction_id'] ?? '',
            'status' => 1,
            'note' => $obj['note'] ?? null,
            'payer_id' => $obj['payer_id'] ?? null,
            'refund_transaction_id' => $obj['refundtransactionid'] ?? '',
        ];

        self::create($saveTo);
    }
}
