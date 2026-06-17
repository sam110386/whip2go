<?php

namespace App\Services\Legacy;

use App\Models\Legacy\TwilioSetting;
use App\Models\Legacy\User;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Twilio.php
 */
class TwilioClient
{
    public function autonotifyByTwilio(string $telephone, string $msg, int $csTwilioOrderId, int $userId): ?array
    {
        $telephone = substr(preg_replace('/[^0-9]/', '', $telephone), -10);
        $user = User::where('username', $telephone)->first();

        if (empty($user)) {
            Log::warning("TwilioClient::autonotifyByTwilio – user not found for phone {$telephone}");
            return null;
        }

        $intercom = new IntercomClient();
        return $intercom->sendMessage($user->toArray(), $msg, $csTwilioOrderId, $userId);
    }
    public function notifyByTwilio(string $telephone, string $msg, int $userId): void
    {
        $telephone = substr(preg_replace('/[^0-9]/', '', $telephone), -10);

        if (empty($telephone)) {
            return;
        }

        $user = User::where('username', $telephone)->first();

        if (empty($user)) {
            return;
        }

        $intercom = new IntercomClient();
        $intercom->sendMessageOpt($user->toArray(), $msg, $userId);
    }
    public function onlyNotifyTwilio(string $telephone, string $msg): void
    {
        $telephone = substr(preg_replace('/[^0-9]/', '', $telephone), -10);

        if (empty($telephone)) {
            return;
        }

        $settings = TwilioSetting::where('dispacher_id', config('legacy.COMPANY_DISPACHER'))
            ->where('status', 1)
            ->first();

        if (empty($settings)) {
            Log::warning('TwilioClient::onlyNotifyTwilio – no active Twilio settings found');
            return;
        }

        $this->sendSms(
            $settings->twilio_sid,
            $settings->twilio_authtoken,
            $settings->twilio_from,
            $telephone,
            $msg
        );
    }
    public function sendSms(string $sid, string $token, string $from, string $to, string $msg): bool
    {
        try {
            $client = new Client($sid, $token);
            $client->messages->create(
                $to,
                [
                    'from' => $from,
                    'body' => $msg,
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('TwilioClient::sendSms failed: ' . $e->getMessage());
            return false;
        }
    }
}
