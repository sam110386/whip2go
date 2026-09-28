<?php

namespace App\Models\Legacy;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class TwilioSetting extends LegacyModel
{
    protected $table = 'twilio_settings';

    protected $fillable = [
        'dispacher_id',
        'status',
        'award_default',
        'twilio_sid',
        'twilio_authtoken',
        'twilio_from',
        'arrive_msg',
        'pickup_msg',
        'drop_msg',
        'kiosk_msg',
        'driver_ref_msg',
        'created',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];


    public static function notifyActivationByTwilio(array $passengerData)
    {
        $dispatcherId = config('legacy.COMPANY_DISPACHER', null);
        $passengerPhone = $passengerData['phone_number'] ?? null;
        $activationCode = $passengerData['activation_code'] ?? null;

        $twilioSetting = self::where('dispacher_id', $dispatcherId)
            ->where('status', 1)
            ->first();

        if (!$twilioSetting || empty($passengerPhone)) {
            return;
        }

        $twilioSid = $twilioSetting->twilio_sid;
        $twilioAuthToken = $twilioSetting->twilio_authtoken;
        $twilioFrom = $twilioSetting->twilio_from;

        $msg = "Your account activation code is {$activationCode}. Please reply YES to join our text alerts. Send>STOP 2quit";

        if (!empty($msg)) {
            try {
                $client = new TwilioClient($twilioSid, $twilioAuthToken);

                $client->messages->create(
                    $passengerPhone,
                    [
                        'from' => $twilioFrom,
                        'body' => $msg
                    ]
                );
            } catch (\Throwable $e) {
                Log::error("Twilio Error: " . $e->getMessage());
                return;
            }
        }
    }

}
