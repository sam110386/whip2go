<?php

namespace App\Services\Legacy;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Migrated from: app/Plugin/EmailQueue/Lib/EmailQueuelib.php
 *
 * Handles email queue processing and payment receipt generation.
 */
class EmailQueueService
{
    private array $paymentTypes;

    public function __construct()
    {
        $this->paymentTypes = (new Common())->getPayoutTypeValue(true);
    }

    public function saveEmailToQueue(int $paymentId, float $amount, string $msg, int $orderId, string $source = 'card'): void
    {
        DB::table('email_queues')->insert([
            'order_id'   => $orderId,
            'amount'     => $amount,
            'payment_id' => $paymentId,
            'text'       => $msg,
            'source'     => $source,
        ]);
    }

    public function processEmailQueue(): void
    {
        $queues = DB::table('email_queues')
            ->where('status', 0)
            ->orderBy('id')
            ->limit(5)
            ->get();

        foreach ($queues as $queue) {
            DB::table('email_queues')->where('id', $queue->id)->update(['status' => 1]);

            if (!empty($queue->payment_id)) {
                $orderData = DB::table('cs_order_payments as CsOrderPayment')
                    ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsOrderPayment.cs_order_id')
                    ->where('CsOrderPayment.id', $queue->payment_id)
                    ->select(
                        'CsOrderPayment.*',
                        'CsOrder.increment_id', 'CsOrder.renter_id',
                        'CsOrder.start_datetime', 'CsOrder.end_datetime',
                        'CsOrder.timezone', 'CsOrder.vehicle_name'
                    )
                    ->first();

                $renter = DB::table('users')
                    ->where('id', $orderData->renter_id)
                    ->select('first_name', 'last_name', 'email', 'address', 'city', 'state', 'zip')
                    ->first();

                $resp = $this->generateReceipt($orderData, $renter, $queue->source, $queue->text);
            } else {
                $orderData = DB::table('cs_orders as CsOrder')
                    ->where('CsOrder.id', $queue->order_id)
                    ->select(
                        'CsOrder.increment_id', 'CsOrder.renter_id',
                        'CsOrder.start_datetime', 'CsOrder.end_datetime',
                        'CsOrder.timezone', 'CsOrder.vehicle_name', 'CsOrder.currency'
                    )
                    ->first();

                $renter = DB::table('users')
                    ->where('id', $orderData->renter_id)
                    ->select('first_name', 'last_name', 'email', 'address', 'city', 'state', 'zip')
                    ->first();

                $resp = $this->generateReceiptForAdvancePayment($orderData, $renter, $queue);
            }

            if ($resp['status']) {
                $this->sendEmail($renter->email, $orderData->increment_id, $resp['filefullname'], $resp['filename']);
            }
        }
    }

    public function generateReceiptForAdvancePayment(object $orderData, object $renter, object $queue): array
    {
        $filename = $orderData->increment_id . '_' . $queue->id . '.pdf';
        $dir = public_path('files/payment_reciept');

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filefullname = $dir . '/' . $filename;

        if (is_file($filefullname)) {
            return ['status' => true, 'message' => 'File already exists', 'filefullname' => $filefullname, 'filename' => $filename];
        }

        $tz = $orderData->timezone ?? 'UTC';
        $data = [
            'logo'            => '<img src="' . config('app.url') . '/img/DriveitawayBluelogo.png" alt="logo" width="150"/>',
            'date'            => date('F j, Y'),
            'RENTERNAME'      => $renter->first_name . ' ' . $renter->last_name,
            'RENTERSTREET1'   => $renter->address,
            'RENTERSTREET2'   => $renter->city . ' ' . $renter->state . ' ' . $renter->zip,
            'RENTEREMAIL'     => $renter->email,
            'TRANSACTIONDATE' => date('F j, Y'),
            'currency'        => $orderData->currency,
            'amount'          => $queue->amount,
            'VEHICLE'         => $orderData->vehicle_name,
            'BOOKINGID'       => $orderData->increment_id,
            'STARTDATETIME'   => Carbon::parse($orderData->start_datetime)->timezone($tz)->format('m/d/Y h:i A'),
            'ENDDATETIME'     => Carbon::parse($orderData->end_datetime)->timezone($tz)->format('m/d/Y h:i A'),
            'TRANSACTIONID'   => '',
            'SOURCE'          => $queue->source === 'card' ? 'Stripe' : 'Wallet',
            'NOTE'            => $queue->text . $orderData->increment_id,
        ];

        $summaryTable = '<table class="totalsummery" width="100%">
                <thead><tr><th align="right">Description</th><th>Amount</th></tr></thead>
                <tbody><tr><td>Partial/Advance Payment</td><td>$' . $data['amount'] . '</td></tr></tbody>
                <tfoot class="summary" width="100%">
                    <tr class="total"><td width="50%" align="right"><b>Total</b></td><td width="50%" align="right"><b>$' . $data['amount'] . '</b></td></tr>
                </tfoot></table>';

        $data['SUMMERYTABLE'] = $summaryTable;

        $agreement = new Agreement();
        $result = $agreement->generatePaymentreciept($data, $filefullname);
        $result['filefullname'] = $filefullname;
        $result['filename'] = $filename;

        return $result;
    }

    public function generateReceipt(object $orderData, object $renter, string $source, string $msg): array
    {
        $filename = $orderData->increment_id . '_' . $orderData->id . '.pdf';
        $dir = public_path('files/payment_reciept');

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filefullname = $dir . '/' . $filename;

        if (is_file($filefullname)) {
            return ['status' => true, 'message' => 'File already exists', 'filefullname' => $filefullname, 'filename' => $filename];
        }

        $tz = $orderData->timezone ?? 'UTC';
        $data = [
            'logo'            => '<img src="' . config('app.url') . '/img/DriveitawayBluelogo.png" alt="logo" width="150"/>',
            'date'            => date('F j, Y'),
            'RENTERNAME'      => $renter->first_name . ' ' . $renter->last_name,
            'RENTERSTREET1'   => $renter->address,
            'RENTERSTREET2'   => $renter->city . ' ' . $renter->state . ' ' . $renter->zip,
            'RENTEREMAIL'     => $renter->email,
            'TRANSACTIONDATE' => date('F j, Y', strtotime($orderData->charged_at)),
            'currency'        => $orderData->currency,
            'amount'          => $orderData->amount,
            'VEHICLE'         => $orderData->vehicle_name,
            'BOOKINGID'       => $orderData->increment_id,
            'STARTDATETIME'   => Carbon::parse($orderData->start_datetime)->timezone($tz)->format('m/d/Y h:i A'),
            'ENDDATETIME'     => Carbon::parse($orderData->end_datetime)->timezone($tz)->format('m/d/Y h:i A'),
            'TRANSACTIONID'   => $orderData->transaction_id,
            'SOURCE'          => $source === 'card' ? 'Stripe' : 'Wallet',
            'NOTE'            => $msg . $orderData->increment_id,
        ];

        $summaryTable = '<table class="totalsummery" width="100%">
                <thead><tr><th align="right">Description</th><th>Amount</th></tr></thead><tbody>';

        if ($orderData->rent > 0) {
            $summaryTable .= '<tr><td>' . ($this->paymentTypes[$orderData->type] ?? 'Fee') . '</td><td>$' . $orderData->rent . '</td></tr>';
        } else {
            $summaryTable .= '<tr><td>' . ($this->paymentTypes[$orderData->type] ?? 'Fee') . '</td><td>$' . $orderData->amount . '</td></tr>';
        }

        if ($orderData->tax > 0) {
            $summaryTable .= '<tr><td>Tax</td><td>$' . $orderData->tax . '</td></tr>';
        }

        if ($orderData->dia_fee > 0) {
            $summaryTable .= '<tr><td>DIA Fee</td><td>$' . $orderData->dia_fee . '</td></tr>';
        }

        $summaryTable .= '</tbody>
            <tfoot class="summary" width="100%">
                <tr class="total"><td width="50%" align="right"><b>Total</b></td><td width="50%" align="right"><b>$' . $orderData->amount . '</b></td></tr>
            </tfoot></table>';

        $data['SUMMERYTABLE'] = $summaryTable;

        $agreement = new Agreement();
        $result = $agreement->generatePaymentreciept($data, $filefullname);
        $result['filefullname'] = $filefullname;
        $result['filename'] = $filename;

        return $result;
    }

    public function sendEmail(string $email, string $incrementId, string $attachment, string $filename): void
    {
        if (empty($email) || empty($attachment)) {
            return;
        }

        try {
            Mail::raw('', function ($message) use ($email, $incrementId, $attachment, $filename) {
                $message->from('support@driveitaway.com', 'DriveItAway Team')
                    ->replyTo('no-reply@driveitaway.com')
                    ->to($email)
                    ->cc('driveitawayreceipts@gmail.com')
                    ->subject('DriveItAway #' . $incrementId . ' Receipt')
                    ->attach($attachment, ['as' => $filename, 'mime' => 'application/pdf']);
            });
        } catch (\Exception $e) {
            // Silently fail to match legacy behavior
        }
    }
}
