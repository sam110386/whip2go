<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dealer → renter SMS path from Cake `SmslogsController::sendmessage` / `Twilio::autonotifyByTwilio`.
 * Cake currently returns early from Intercom; this records outbound rows in `cs_twilio_logs` for UI parity.
 */
class LegacyDealerOutboundSms
{
    /**
     * @return array{status: bool, message: string}
     */
    public function sendFromDealerSession(int $dealerUserId, string $phoneRaw, string $message): array
    {
        $msg = trim($message);
        $phone = trim($phoneRaw);
        if ($phone === '' || $msg === '') {
            return ['status' => false, 'message' => 'Sorry, message body is empty'];
        }

        if (!Schema::hasTable('cs_twilio_logs')) {
            return ['status' => false, 'message' => 'Something went wrong, please try again'];
        }

        $digits = substr(preg_replace('/\D/', '', $phone), -10);
        if ($digits === '') {
            return ['status' => false, 'message' => 'Something went wrong, please try again'];
        }

        $renterData = DB::table('cs_twilio_logs')
            ->where('user_id', $dealerUserId)
            ->where('renter_phone', 'like', '%' . $digits . '%')
            ->orderByDesc('id')
            ->select(['cs_twilio_order_id'])
            ->first();

        if ($renterData === null) {
            return ['status' => false, 'message' => 'Something went wrong, please try again'];
        }

        $orderId = (int) ($renterData->cs_twilio_order_id ?? 0);

        DB::table('cs_twilio_logs')->insert([
            'cs_twilio_order_id' => $orderId,
            'renter_phone' => $digits,
            'user_id' => $dealerUserId,
            'msg' => $msg,
            'type' => 1,
            'created' => now(),
            'modified' => now(),
        ]);

        return ['status' => true, 'message' => 'Your message is sent successfully.'];
    }
}
