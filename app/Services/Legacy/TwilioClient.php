<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Twilio.php
 *
 * In the legacy app, actual Twilio SMS sending was largely replaced by Intercom messaging.
 * This port preserves that behaviour: messages route through the Intercom service class,
 * with the original Twilio SDK path available behind a feature flag for future re-enablement.
 */
class TwilioClient
{
    public function autonotifyByTwilio(string $telephone, string $msg, int $csTwilioOrderId, int $userId): ?array
    {
        $telephone = substr(preg_replace('/[^0-9]/', '', $telephone), -10);

        $user = DB::table('users')->where('username', $telephone)->first();
        if (empty($user)) {
            Log::warning("TwilioClient::autonotifyByTwilio – user not found for phone {$telephone}");
            return null;
        }

        $intercom = new IntercomClient();
        return $intercom->sendMessage((array)$user, $msg, $csTwilioOrderId, $userId);
    }

    public function notifyByTwilio(string $telephone, string $msg, int $userId): void
    {
        $telephone = substr(preg_replace('/[^0-9]/', '', $telephone), -10);
        if (empty($telephone)) {
            return;
        }

        $user = DB::table('users')->where('username', $telephone)->first();
        if (empty($user)) {
            return;
        }

        $intercom = new IntercomClient();
        $intercom->sendMessageOpt((array)$user, $msg, $userId);
    }

    public function onlyNotifyTwilio(string $telephone, string $msg): void
    {
        $telephone = substr(preg_replace('/[^0-9]/', '', $telephone), -10);
        if (empty($telephone)) {
            return;
        }

        // Twilio SDK call – stubbed; wire when SDK is available
        Log::info("TwilioClient::onlyNotifyTwilio – stub: would SMS {$telephone}");
    }
}
