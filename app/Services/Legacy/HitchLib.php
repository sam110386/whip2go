<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class HitchLib
{
    protected TwilioClient $twilio;

    public function __construct()
    {
        $this->twilio = new TwilioClient();
    }

    public function processQueue(): void
    {
        $queues = DB::table('hitch_leads')
            ->where('status', 0)
            ->limit(50)
            ->get();

        if ($queues->isEmpty()) {
            return;
        }

        $twilioAccount = DB::table('twilio_settings')
            ->where('dispacher_id', config('legacy.COMPANY_DISPACHER'))
            ->where('status', 1)
            ->first();

        if (empty($twilioAccount)) {
            Log::warning('HitchLib::processQueue - No active Twilio settings found');
            return;
        }

        foreach ($queues as $queue) {
            DB::table('hitch_leads')
                ->where('id', $queue->id)
                ->update(['status' => 1]);

            $email = $queue->email;
            $subject = 'Hitch and Employbridge have invited you to the vehicle rent-to-own program';

            if (filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email)) {
                $this->sendEmail($email, $subject);
            }

            $msg = 'Hitch has invited you to the vehicle rent-to-own program powered by DriveItAway. Please download the app from the link below and find your dream car! Be sure to register to receive available credits from Hitch. Download DriveItAway (http://onelink.to/dg5jbd)';
            $telephone = substr(preg_replace('/[^0-9]/', '', $queue->phone), -10);

            if (!empty($telephone)) {
                $this->twilio->sendSms(
                    $twilioAccount->twilio_sid,
                    $twilioAccount->twilio_authtoken,
                    $twilioAccount->twilio_from,
                    $telephone,
                    $msg
                );
            }
        }
    }

    public function sendEmail(string $email, string $subject): void
    {
        if (empty($email)) {
            return;
        }

        try {
            Mail::send('emails.hitch.invitation', [], function ($message) use ($email, $subject) {
                $message->from('support@driveitaway.com', 'DriveItAway Team')
                    ->replyTo('no-reply@driveitaway.com')
                    ->to($email)
                    ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('HitchLib::sendEmail failed: ' . $e->getMessage());
        }
    }
}
