<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Port of CakePHP app/Lib/Emailnotify.php
 * Sends various notification emails (owner booking alerts, mileage, custom, etc.).
 * Uses Laravel Mail facade; templates should be migrated to resources/views/emails/.
 */
class Emailnotify
{
    public function sendActivationCode(string $email, string $code, string $businessName): void
    {
        if (empty($email)) return;
        $businessName = html_entity_decode($businessName);

        $body = 'Please use following code to verify your account.'
            . "<br><p>Your Account Activation Code : {$code}</p>"
            . '<br><br>Thanks,<br>' . $businessName;

        $this->sendRawHtml($email, 'Activation Code', $body, $businessName);
    }

    public function sendNotificationToOwner(array $order, array $emails, string $startDatetime, array $driverInfo): void
    {
        $email = !empty($emails['notify_email']) ? $emails['notify_email'] : ($emails['email'] ?? '');
        if (empty($email)) return;

        $vehicle = trim(($order['Vehicle']['year'] ?? '') . ' ' . ($order['Vehicle']['make'] ?? '') . ' '
            . ($order['Vehicle']['model'] ?? '') . ' ' . ($order['Vehicle']['vin_no'] ?? '') . ' '
            . ($order['Vehicle']['stock_no'] ?? ''));

        $data = [
            'logourl'       => config('app.url') . '/img/DriveitawayBluelogo.png',
            'VEHICLE'       => $vehicle,
            'BOOKINGTIME'   => date('F d h:i A', strtotime($startDatetime)),
            'CUSTOMEREMAIL' => $email,
            'DRIVERINFO'    => ($driverInfo['first_name'] ?? '') . ' ' . ($driverInfo['last_name'] ?? '') . ' ' . ($driverInfo['contact_number'] ?? ''),
        ];

        $this->sendTemplate('emails.owner_booking_notification', $data, $email, 'New Booking');
    }

    public function sendbookingExpireEmail(array $csorder): void
    {
        $email = !empty($csorder['Owner']['notify_email']) ? $csorder['Owner']['notify_email'] : ($csorder['Owner']['email'] ?? '');
        if (empty($email)) return;

        $data = [
            'logourl'       => config('app.url') . '/img/DriveitawayBluelogo.png',
            'VEHICLE'       => ($csorder['Vehicle']['year'] ?? '') . ' ' . ($csorder['Vehicle']['make'] ?? '') . ' ' . ($csorder['Vehicle']['model'] ?? ''),
            'BOOKINGTIME'   => date('F d h:i A', strtotime($csorder['CsOrder']['start_datetime'] ?? '')),
            'CUSTOMEREMAIL' => $email,
            'RENTERNAME'    => ($csorder['Renter']['first_name'] ?? '') . ' ' . ($csorder['Renter']['last_name'] ?? ''),
            'RENTERPHONE'   => $csorder['Renter']['contact_number'] ?? '',
        ];

        $incrementId = $csorder['CsOrder']['increment_id'] ?? '';
        $this->sendTemplate('emails.owner_booking_expire_notification', $data, $email, "Booking {$incrementId} Expire notification");
    }

    public function notifyVehicleMileage(string $email, array $allVehicles): void
    {
        $data = [
            'logourl'       => config('app.url') . '/img/DriveitawayBluelogo.png',
            'VEHICLES'      => $allVehicles,
            'CUSTOMEREMAIL' => $email,
        ];
        $this->sendTemplate('emails.owner_vehicle_mileage_notification', $data, $email, 'Vehicle Mileage Expire Notification');
    }

    public function notifyEmailToOwner(string $renterPhone, int $ownerId, string $msg, int $csTwilioOrderId): void
    {
        $owner = DB::table('users')->where('id', $ownerId)->first(['email', 'notify_email']);
        $email = !empty($owner->notify_email) ? $owner->notify_email : ($owner->email ?? '');
        if (empty($email)) return;

        $csorder = DB::table('cs_twilio_orders as cto')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'cto.cs_order_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'CsOrder.renter_id')
            ->where('cto.id', $csTwilioOrderId)
            ->first([
                'CsOrder.increment_id', 'CsOrder.start_datetime',
                'Vehicle.vehicle_name', 'Renter.first_name', 'Renter.last_name',
            ]);

        $data = [
            'logourl'      => config('app.url') . '/img/DriveitawayBluelogo.png',
            'MESSAGE'      => $msg,
            'PHONE_NUMBER' => $renterPhone,
            'RENTERNAME'   => ($csorder->first_name ?? '') . ' ' . ($csorder->last_name ?? ''),
            'VEHICLE'      => $csorder->vehicle_name ?? '',
            'BOOKINGTIME'  => date('F d h:i A', strtotime($csorder->start_datetime ?? '')),
        ];

        $incrementId = $csorder->increment_id ?? '';
        $this->sendTemplate('emails.twilio_recieve', $data, $email, "Booking # {$incrementId} Renter Replied");
    }

    public function sendNotificationToOwnerForVehicleReservation(array $reservation): void
    {
        $owner = DB::table('users')->where('id', $reservation['user_id'])->first(['email', 'notify_email']);
        $email = !empty($owner->notify_email) ? $owner->notify_email : ($owner->email ?? '');
        if (empty($email)) return;

        $driver = DB::table('users')->where('id', $reservation['renter_id'])->first(['first_name', 'last_name', 'contact_number', 'email', 'id']);

        $data = [
            'logourl'       => config('app.url') . '/img/DriveitawayBluelogo.png',
            'VEHICLE'       => $reservation['vehicle_name'] ?? '',
            'BOOKINGTIME'   => date('F d h:i A', strtotime($reservation['start_datetime'] ?? '')),
            'CUSTOMEREMAIL' => $email,
            'DRIVERINFO'    => ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '') . ' ' . ($driver->contact_number ?? ''),
        ];

        $this->sendTemplate('emails.owner_booking_notification', $data, $email, 'New Pending Booking');
    }

    public function sendNotificationToDriverForVehiclePreparation(array $reservation, int $preparationHrs): void
    {
        $driver = DB::table('users')->where('id', $reservation['renter_id'])->first(['first_name', 'last_name', 'email']);
        $email = $driver->email ?? '';
        if (empty($email)) return;

        if ($preparationHrs > 24) {
            $secs = $preparationHrs * 3600;
            $dtF = new \DateTime('@0');
            $dtT = new \DateTime("@{$secs}");
            $formatted = $dtF->diff($dtT)->format('%a day(s), %h hours');
        } else {
            $formatted = "{$preparationHrs} hours";
        }

        $data = [
            'logourl'         => config('app.url') . '/img/DriveitawayBluelogo.png',
            'VEHICLE'         => $reservation['vehicle_name'] ?? '',
            'DRIVERINFO'      => ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? ''),
            'PREPARATIONTIME' => $formatted,
            'CUSTOMEREMAIL'   => $email,
        ];

        $this->sendTemplate('emails.booking_preparation_notification', $data, $email, 'Thanks for Booking');
    }

    public function autonotifyByTwilio(string $telephone, string $msg, int $csTwilioOrderId, int $userId): void
    {
        (new TwilioClient())->autonotifyByTwilio($telephone, $msg, $csTwilioOrderId, $userId);
    }

    public function sendCustomEmail(string $msg, string $email, string $subject): void
    {
        if (empty($email)) return;

        $data = [
            'logourl' => config('app.url') . '/img/DriveitawayBluelogo.png',
            'MESSAGE' => $msg,
        ];

        $this->sendTemplate('emails.custom_email', $data, $email, $subject, 'driveitawayreceipts@gmail.com');
    }

    public function sendInviteEmail(string $email, string $subject, array $data): void
    {
        if (empty($email)) return;
        $this->sendTemplate('emails.invitation_email', $data, $email, $subject);
    }

    public function sendEmailToDealerForVehicleSellRequest(int $reservationId): void
    {
        if (empty($reservationId)) return;

        $res = DB::table('vehicle_reservations as vr')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->where('vr.id', $reservationId)
            ->first(['vr.*', 'v.vehicle_name']);
        if (empty($res)) return;

        $owner = DB::table('users')->where('id', $res->user_id)->first(['email', 'notify_email']);
        $email = !empty($owner->notify_email) ? $owner->notify_email : ($owner->email ?? '');

        $subject = 'DriveitAway Team - Your selling vehicle data is updated';
        $msg = "Your vehicle {$res->vehicle_name}, it was requested to be sold, is updated. Please check the details and contact admin for more info.";
        $this->sendCustomEmail($msg, $email, $subject);
    }

    public function sendEmailToPushToDealer(int $reservationId, int $flag): void
    {
        if (empty($reservationId)) return;

        $res = DB::table('vehicle_reservations as vr')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->where('vr.id', $reservationId)
            ->first(['vr.*', 'v.vehicle_name']);
        if (empty($res)) return;

        $owner = DB::table('users')->where('id', $res->user_id)->first(['email', 'notify_email']);
        $email = !empty($owner->notify_email) ? $owner->notify_email : ($owner->email ?? '');

        if ($flag == 1) {
            $subject = 'DriveitAway Team - Your vehicle is requested to be sold';
            $msg = "Your vehicle {$res->vehicle_name} is requested to be sold. Please check the details and contact admin for more info.";
        } else {
            $subject = 'DriveitAway Team - Your vehicle is removed from sale request';
            $msg = "Your vehicle {$res->vehicle_name} is removed from sale request. Please check the details and contact admin for more info.";
        }

        $this->sendCustomEmail($msg, $email, $subject);
    }

    /**
     * Send an HTML email using a Blade template. Falls back to raw HTML if the template is missing.
     */
    private function sendTemplate(string $view, array $data, string $to, string $subject, ?string $cc = null): void
    {
        try {
            Mail::send($view, $data, function ($m) use ($to, $subject, $cc) {
                $m->from('support@driveitaway.com', 'DriveItAway Team')
                  ->replyTo('no-reply@driveitaway.com')
                  ->to($to)
                  ->subject($subject);
                if ($cc) {
                    $m->cc($cc);
                }
            });
        } catch (\Throwable $e) {
            Log::warning("Emailnotify: failed to send '{$subject}' to {$to} – {$e->getMessage()}");
        }
    }

    private function sendRawHtml(string $to, string $subject, string $html, string $fromName = 'DriveItAway Team'): void
    {
        try {
            Mail::html($html, function ($m) use ($to, $subject, $fromName) {
                $m->from('no-reply@whip2go.com', $fromName)
                  ->replyTo('no-reply@example.com')
                  ->to($to)
                  ->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning("Emailnotify: failed to send '{$subject}' to {$to} – {$e->getMessage()}");
        }
    }
}
