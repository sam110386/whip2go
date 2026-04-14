<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntercomWebhooksController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request)
    {
        $authorization = $request->header('Authorization', '');
        $authToken = trim(str_replace('Basic', '', $authorization));
        $postData = json_decode($request->getContent(), true);

        if ($authToken !== 'GJHGJHGHG788768UYT' || empty($postData)) {
            return response('sorry, wrong attempt', 403);
        }

        $messages = [
            'message1' => 'Hitch has invited you to the vehicle rent-to-own program powered by DriveItAway. Please download the app from the link below and find your dream car! Be sure to register to receive your credits from Hitch! Download DriveItAway (http://onelink.to/dg5jbd)',
            'message2' => 'Hey, Sami here from DriveItAway. Rent-To-Own Vehicles are now available in your area. If you are endlessly renting or looking for a new way to buy a car, come check out the DriveItAway app. Please let us know if you\'re still interested so that we can get you started. Thanks...Please reply YES to join our text alerts. .Msg&Data rates may apply. Send>STOP 2quit',
            'message3' => 'Would you get married without first dating? Then why would you buy a car AND THEN DRIVE IT? Check out DRIVEITAWAY--the new way to buy a car...Please reply YES to join our text alerts. .Msg&Data rates may apply. Send>STOP 2quit',
            'message4' => 'Hey, Sami here from DriveItAway. Brand New Nissan Altimas and Nissan Rogues are available through our Subscribe-To-Own program. If you are endlessly renting or looking for a new way to buy a car, come check out the DriveItAway app. Please let us know if you\'re still interested so that we can get you started. Thanks...Please reply YES to join our text alerts. .Msg&Data rates may apply. Send>STOP 2quit',
        ];

        $phone = $postData['phone'] ?? '';
        $msg = $postData['msg'] ?? '';

        if (!empty($phone) && isset($messages[$msg])) {
            try {
                $twilio = app(\App\Services\Legacy\TwilioClient::class);
                $twilio->onlyNotifyTwilio($phone, $messages[$msg]);
            } catch (\Exception $e) {
                \Log::error('IntercomWebhook SMS failed: ' . $e->getMessage());
            }
        }

        return response('', 200);
    }

    public function ticket(Request $request)
    {
        $postData = json_decode($request->getContent(), true);

        \Log::channel('daily')->info('intercom_ticket: ' . json_encode($postData));

        if (!empty($postData['data']['item']['id'])) {
            $ticketState = $postData['data']['item']['ticket_state'] ?? '';
            $status = '';

            if ($ticketState === 'in_progress' || $ticketState === 'waiting_on_customer') {
                $status = 1;
            } elseif ($ticketState === 'resolved') {
                $status = 3;
            }

            if ($status !== '') {
                $intercomId = $postData['data']['item']['id'];

                $issue = DB::table('cs_vehicle_issues')
                    ->where('intercom_id', $intercomId)
                    ->select('id')
                    ->first();

                if (!empty($issue->id)) {
                    DB::table('cs_vehicle_issues')
                        ->where('id', $issue->id)
                        ->update(['status' => $status]);
                }
            }
        }

        return response('', 200);
    }
}
