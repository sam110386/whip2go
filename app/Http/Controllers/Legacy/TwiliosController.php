<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TwiliosController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function twilioRecieve(Request $request)
    {
        $from = (string) $request->input('From', '');
        $body = (string) $request->input('Body', '');
        if ($from === '' || $body === '') {
            return response('finished');
        }

        $passengerPhone = substr(preg_replace('/[^0-9]/', '', $from), -10);

        $renterData = DB::table('cs_twilio_logs')
            ->where('renter_phone', $passengerPhone)
            ->orderByDesc('id')
            ->first();

        if (empty($renterData)) {
            return response('dont do anything');
        }

        DB::table('cs_twilio_logs')->insert([
            'cs_twilio_order_id' => (int) $renterData->cs_twilio_order_id,
            'user_id' => (int) $renterData->user_id,
            'renter_phone' => $passengerPhone,
            'type' => 2,
            'msg' => $body,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response('finished');
    }

    public function OrderAutoCreate($csTwilioOrderId)
    {
        if (empty($csTwilioOrderId)) {
            return response('done');
        }

        $order = DB::table('cs_twilio_orders')
            ->where('id', (int) $csTwilioOrderId)
            ->where('status', 0)
            ->where('approved', 1)
            ->first();

        if (empty($order)) {
            return response('dont do anything');
        }

        // Booking renew flow is still handled in legacy traits/libs.
        return response('done');
    }

    public function confirm($cstworderid)
    {
        if (empty($cstworderid)) {
            return response('Sorry, something went wrong.');
        }

        $order = DB::table('cs_twilio_orders')
            ->whereRaw('MD5(cs_order_id) = ?', [(string) $cstworderid])
            ->where('status', 0)
            ->first();

        if (empty($order)) {
            return response('Sorry, something went wrong. Better luck for next time');
        }

        if (!empty($order->approved)) {
            return response('We already accepted your request');
        }

        DB::table('cs_twilio_orders')
            ->where('id', (int) $order->id)
            ->update(['approved' => 1, 'updated_at' => now()]);

        $endDatetime = strtotime((string) $order->end_datetime);
        if ($endDatetime < time() && date('Y-m-d', $endDatetime) <= date('Y-m-d')) {
            $this->OrderAutoCreate((int) $order->id);
            return response('We accepted your request');
        }

        return response('Sorry, your request cant be processed due to time expire. Please contact to support team.');
    }
}
