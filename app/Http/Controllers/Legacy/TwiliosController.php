<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Cake `TwiliosController` — inbound SMS webhook and Twilio order helpers.
 */
class TwiliosController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function twilioRecieve(Request $request)
    {
        $from = (string) $request->input('From', $request->query('From', ''));
        $body = (string) $request->input('Body', $request->query('Body', ''));

        Log::info('Twilio inbound SMS', [
            'from' => $from,
            'body' => $body,
        ]);

        if ($from !== '' && $body !== '') {
            $passengerPhone = preg_replace('/[^0-9]/', '', $from);
            $passengerPhone = substr($passengerPhone, -10);

            if (Schema::hasTable('cs_twilio_logs')) {
                $renterData = DB::table('cs_twilio_logs')
                    ->where('renter_phone', $passengerPhone)
                    ->orderByDesc('id')
                    ->first();

                if ($renterData === null) {
                    return response('dont do anything', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
                }

                $now = now()->toDateTimeString();
                DB::table('cs_twilio_logs')->insert([
                    'cs_twilio_order_id' => (int) $renterData->cs_twilio_order_id,
                    'user_id' => (int) $renterData->user_id,
                    'renter_phone' => $passengerPhone,
                    'type' => 2,
                    'msg' => $body,
                    'created' => $now,
                    'modified' => $now,
                ]);

                Log::info('Twilio owner email stub', [
                    'renter_phone' => $passengerPhone,
                    'user_id' => (int) $renterData->user_id,
                    'cs_twilio_order_id' => (int) $renterData->cs_twilio_order_id,
                ]);
            }
        }

        return response('finished', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function OrderAutoCreate($CsTwilioOrderId)
    {
        if (empty($CsTwilioOrderId) || ! Schema::hasTable('cs_twilio_orders')) {
            return response('', 204);
        }

        $order = DB::table('cs_twilio_orders')
            ->where('id', (int) $CsTwilioOrderId)
            ->where('status', 0)
            ->where('approved', 1)
            ->first();

        if ($order === null) {
            return response('dont do anything', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        Log::info('Twilio OrderAutoCreate: auto-renew logic not ported (stub)', [
            'cs_twilio_order_id' => (int) $order->id,
            'cs_order_id' => $order->cs_order_id ?? null,
            'extend' => $order->extend ?? null,
        ]);

        return response('', 204);
    }

    public function confirm($cstworderid)
    {
        if (empty($cstworderid) || ! Schema::hasTable('cs_twilio_orders')) {
            return response('Sorry, something went wrong.', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $row = DB::table('cs_twilio_orders')
            ->whereRaw('MD5(cs_order_id) = ?', [(string) $cstworderid])
            ->where('status', 0)
            ->first();

        if ($row === null) {
            return response('Sorry, something went wrong. Better luck for next time', 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        if (! empty($row->approved)) {
            return response('We already accepted your request', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $endTs = $row->end_datetime ? strtotime((string) $row->end_datetime) : false;
        $endDate = $endTs !== false ? date('Y-m-d', $endTs) : '';
        $inWindow = $endTs !== false
            && $endTs < time()
            && $endDate <= date('Y-m-d');

        DB::table('cs_twilio_orders')->where('id', (int) $row->id)->update([
            'approved' => 1,
            'modified' => now()->toDateTimeString(),
        ]);

        if ($inWindow) {
            $this->OrderAutoCreate($row->id);

            return response('We accepted your request', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        return response('Sorry, your request cant be processed due to time expire. Please contact to support team.', 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
