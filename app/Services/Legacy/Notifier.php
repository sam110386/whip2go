<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Notifier.php
 * Orchestrates notifications via Intercom, Pubnub, and Twilio.
 */
class Notifier
{
    public function notifyByIntercom(int $driverId, string $msg, array $order = [], array $optional = []): ?array
    {
        $intercom = new IntercomClient();
        $userInfo = (array)DB::table('users')->find($driverId);

        if (!empty($optional) && !empty($optional['cs_twilio_order_id'])) {
            return $intercom->sendMessage($userInfo, $msg, $optional['cs_twilio_order_id'], $optional['user_id'] ?? 0);
        }

        if (empty($order)) {
            return $intercom->sendMessage($userInfo, $msg);
        }

        $existing = DB::table('cs_twilio_orders')
            ->where('cs_order_id', $order['id'])
            ->value('id');

        if (empty($existing)) {
            $existing = DB::table('cs_twilio_orders')->insertGetId([
                'cs_order_id'    => $order['id'],
                'user_id'        => $order['user_id'],
                'renter_id'      => $order['renter_id'],
                'vehicle_id'     => $order['vehicle_id'],
                'start_datetime' => $order['start_datetime'],
                'end_datetime'   => $order['end_datetime'],
                'renter_phone'   => substr(preg_replace('/[^0-9]/', '', $userInfo['contact_number'] ?? ''), -10),
                'created'        => now(),
                'short_url'      => '',
            ]);
        }

        return $intercom->sendMessage($userInfo, $msg, $existing, $order['user_id'] ?? 0);
    }

    public function getRenterDetails(int $renterId): array
    {
        $user = DB::table('users')->where('id', $renterId)->first();
        return $user ? (array)$user : [];
    }

    public function notifyForActivateBooking(int $csOrderId, array $renterData = [], array $tag = []): ?array
    {
        if (empty($renterData)) {
            $order = DB::table('cs_orders')->where('id', $csOrderId)->value('renter_id');
            $renterData = $this->getRenterDetails($order);
        }

        $intercom = new IntercomClient();
        return $intercom->sendMessageWithTag($renterData, '', 'booked', $csOrderId, $tag);
    }

    public function notifyPendingStatusChange(array $push, array $renterData = []): ?array
    {
        $existing = DB::table('cs_twilio_orders')
            ->where('reservation_id', $push['bookingid'])
            ->first();

        if (empty($existing)) {
            $csTwilioOrderId = DB::table('cs_twilio_orders')->insertGetId([
                'cs_order_id'    => '',
                'reservation_id' => $push['bookingid'],
                'user_id'        => $push['user_id'],
                'renter_id'      => $push['renter_id'],
                'vehicle_id'     => $push['vehicleid'] ?? ($push['vehicle_id'] ?? 0),
                'start_datetime' => now(),
                'end_datetime'   => now(),
                'renter_phone'   => substr(preg_replace('/[^0-9]/', '', $renterData['contact_number'] ?? ''), -10),
                'created'        => now(),
                'short_url'      => '',
            ]);
        } else {
            $csTwilioOrderId = $existing->id;
        }

        if (!empty($csTwilioOrderId)) {
            DB::table('cs_twilio_logs')->insert([
                'cs_twilio_order_id' => $csTwilioOrderId,
                'renter_phone'       => substr(preg_replace('/[^0-9]/', '', $renterData['contact_number'] ?? ''), -10),
                'user_id'            => $push['user_id'],
                'msg'                => $push['msg'],
            ]);
        }

        $renterData = $this->getRenterDetails($push['renter_id']);
        $intercom = new IntercomClient();
        return $intercom->sendMessage($renterData, $push['msg']);
    }

    public function notifyToDriverPendingBookingSuccess(array $push, array $renterData = []): ?array
    {
        if (empty($renterData)) {
            $renterData = $this->getRenterDetails($push['renter_id']);
        }

        $existing = DB::table('cs_twilio_orders')
            ->where('reservation_id', $push['bookingid'])
            ->first();

        if (empty($existing)) {
            $csTwilioOrderId = DB::table('cs_twilio_orders')->insertGetId([
                'cs_order_id'    => '',
                'reservation_id' => $push['bookingid'],
                'user_id'        => $push['user_id'],
                'renter_id'      => $push['renter_id'],
                'vehicle_id'     => $push['vehicleid'] ?? 0,
                'start_datetime' => now(),
                'end_datetime'   => now(),
                'renter_phone'   => substr(preg_replace('/[^0-9]/', '', $renterData['contact_number'] ?? ''), -10),
                'created'        => now(),
                'short_url'      => '',
            ]);
        } else {
            $csTwilioOrderId = $existing->id;
        }

        if (!empty($csTwilioOrderId)) {
            DB::table('cs_twilio_logs')->insert([
                'cs_twilio_order_id' => $csTwilioOrderId,
                'renter_phone'       => substr(preg_replace('/[^0-9]/', '', $renterData['contact_number'] ?? ''), -10),
                'user_id'            => $push['user_id'],
                'msg'                => $push['msg'],
            ]);
        }

        $pubnub = new PubnubClient();
        return $pubnub->notifyPendingStatusChange([
            'user_id'   => $push['renter_id'],
            'bookingid' => $push['bookingid'],
            'vehicleid' => $push['vehicleid'] ?? 0,
            'msg'       => $push['msg'],
        ]);
    }

    public static function notifyByIntercomWithTag(int $driverId, string $msg, string $tag, $bookingId = '', array $attributeOpt = []): ?array
    {
        $intercom = new IntercomClient();
        $userInfo = (array)DB::table('users')->find($driverId);
        return $intercom->sendMessageWithTag($userInfo, $msg, $tag, $bookingId, $attributeOpt);
    }

    public static function notifyByIntercomWithTagAsRenter(int $driverId, string $msg, string $tag): ?array
    {
        $intercom = new IntercomClient();
        $userInfo = (array)DB::table('users')->find($driverId);
        return $intercom->sendMessageWithTagAsRenter($userInfo, $msg, $tag);
    }

    public function pushEmployeBridgeLead(array $data): ?array
    {
        $intercom = new IntercomClient();
        return $intercom->pushEmployeBridgeLead($data);
    }

    public static function updateIntercomeUserAttrbute(int $driverId, array $opt = []): ?array
    {
        $intercom = new IntercomClient();
        $userInfo = (array)DB::table('users')->find($driverId);
        return $intercom->updateUserAttrbute($userInfo, $opt);
    }

    public static function createIntercomeUserEvent(array $opt = []): ?array
    {
        $intercom = new IntercomClient();
        return $intercom->createEvents($opt);
    }

    public static function updateUserStatusFromRetryPayment(array $order): void
    {
        self::updateIntercomeUserAttrbute($order['renter_id'], ['Rental_Status' => 'Paid']);
    }

    public static function notifyByIntercomAsRenter(array $userInfo, string $msg): ?array
    {
        $intercom = new IntercomClient();
        return $intercom->sendMessageAsRenter($userInfo, $msg);
    }
}
