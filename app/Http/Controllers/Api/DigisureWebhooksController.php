<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Legacy\Emailnotify;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migrated from: app/Plugin/Digisure/Controller/DigisureWebhooksController.php
 *
 * Digisure trustscore webhook handler. Updates user checkr_status and
 * booking reservation states based on incoming events.
 */
class DigisureWebhooksController extends Controller
{
    private string $webhookSecret = 'GJHGJHGHG788768UYT';

    public function index(Request $request): Response
    {
        $signature = $request->header('Digisure-Signature', '');
        $postData = $request->getContent();

        Log::channel('daily')->info('digisure_webhook', [
            'body'    => $postData,
            'headers' => $request->headers->all(),
        ]);

        $parts = explode(',', $signature);
        if (count($parts) < 2) {
            return response('dont do anything, signature dont matched', 200);
        }

        $timestamp = explode('=', $parts[0])[1] ?? '';
        $sig = explode('=', $parts[1])[1] ?? '';

        $digest = hash_hmac('SHA256', "{$timestamp}.{$postData}", $this->webhookSecret);

        if ($sig !== $digest) {
            return response('dont do anything, signature dont matched', 200);
        }

        $data = json_decode($postData, true);
        $event = $data['event'] ?? '';

        $pendingEvents = [
            'driver.trustscore.pending', 'trustscore.pending',
            'driver.trustscore.in_review', 'trustscore.in_review',
        ];
        if (in_array($event, $pendingEvents)) {
            return response('do nothing', 200);
        }

        $userId = $data['reference']['custom_fields']['user_id'] ?? null;

        $userReport = DB::table('user_reports')
            ->where('user_id', $userId)
            ->where('channel', 'DIG')
            ->first();

        if (empty($userReport)) {
            return response('do nothing, record not found', 200);
        }

        $declinedEvents = [
            'driver.trustscore.declined', 'trustscore.declined',
            'driver.trustscore.failed', 'trustscore.failed',
            'driver.trustscore.incomplete', 'trustscore.incomplete',
        ];

        $approvedEvents = [
            'driver.trustscore.approved', 'trustscore.approved',
        ];

        if (in_array($event, $declinedEvents)) {
            DB::table('users')->where('id', $userReport->user_id)->update(['checkr_status' => 4]);
            $this->cancelBooking($userReport->user_id, 4);
        }

        if (in_array($event, $approvedEvents)) {
            DB::table('users')->where('id', $userReport->user_id)->update(['checkr_status' => 1]);
            $this->activateBooking($userReport->user_id, 1);
            $this->notifyToAdmin($userReport->user_id, 'Approved');
        }

        return response('', 200);
    }

    private function cancelBooking(int $renterId, int $checkrStatus): void
    {
        $reservation = DB::table('vehicle_reservations')
            ->where('renter_id', $renterId)
            ->where('status', 0)
            ->orderByDesc('id')
            ->select('id')
            ->first();

        if (empty($reservation)) {
            return;
        }

        DB::table('vehicle_reservations')
            ->where('id', $reservation->id)
            ->update(['checkr_status' => $checkrStatus]);
    }

    private function activateBooking(int $renterId, int $checkrStatus): void
    {
        $reservation = DB::table('vehicle_reservations')
            ->where('renter_id', $renterId)
            ->where('status', 0)
            ->orderByDesc('id')
            ->select('id')
            ->first();

        if (empty($reservation)) {
            return;
        }

        DB::table('vehicle_reservations')
            ->where('id', $reservation->id)
            ->update(['checkr_status' => $checkrStatus]);
    }

    private function notifyToAdmin(int $renterId, string $status): void
    {
        $order = DB::table('cs_orders')
            ->where('renter_id', $renterId)
            ->orderByDesc('id')
            ->select('increment_id')
            ->first();

        if (empty($order)) {
            return;
        }

        $msg = "One of your booking ({$order->increment_id}) is updated in your account due to checkr API status ({$status}) update.";
        $email = 'adam01@gmail.com';
        $subject = "Booking #{$order->increment_id} ChekrApi Update";

        (new Emailnotify())->sendCustomEmail($msg, $email, $subject);
    }
}
