<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Legacy\User;
use App\Models\Legacy\CsTwilioOrder;
use App\Models\Legacy\VehicleReservation;


/**
 * Port of CakePHP app/Lib/Emailnotify.php
 */
class Emailnotify
{
    public function sendActivationCode(string $email, string $code, string $businessName): void
    {
        if (empty($email)) {
            return;
        }

        $businessName = html_entity_decode($businessName);
        $body = 'Please use following code to verify your account.'
            . "<br><p>Your Account Activation Code : {$code}</p>"
            . '<br><br>Thanks,<br>' . $businessName;

        $this->sendRawHtml($email, 'Activation Code', $body, $businessName);
    }
    public function sendNotificationToOwner(array $order, array $emails, string $startDatetime, array $driverInfo): void
    {
        $email = !empty($emails['notify_email']) ? $emails['notify_email'] : ($emails['email'] ?? '');

        if (empty($email)) {
            return;
        }

        $vehicle = trim(($order['year'] ?? '') . ' ' . ($order['make'] ?? '') . ' ' . ($order['model'] ?? '') . ' ' . ($order['vin_no'] ?? '') . ' ' . ($order['stock_no'] ?? ''));

        $data = [
            'logourl' => legacy_asset('img/DriveitawayBluelogo.png'),
            'VEHICLE' => $vehicle,
            'BOOKINGTIME' => date('F d h:i A', strtotime($startDatetime)),
            'CUSTOMEREMAIL' => $email,
            'DRIVERINFO' => ($driverInfo['first_name'] ?? '') . ' ' . ($driverInfo['last_name'] ?? '') . ' ' . ($driverInfo['contact_number'] ?? ''),
        ];

        $views = [
            'html' => 'emails.html.owner_booking_notification',
            'text' => 'emails.text.owner_booking_notification',
        ];
        $this->sendTemplate($views, $data, $email, 'New Booking');
    }
    public function sendbookingExpireEmail(array $csorder): void
    {
        $email = !empty($csorder['owner']['notify_email']) ? $csorder['owner']['notify_email'] : ($csorder['owner']['email'] ?? '');

        if (empty($email)) {
            return;
        }

        $data = [
            'logourl' => legacy_asset('img/DriveitawayBluelogo.png'),
            'VEHICLE' => ($csorder['vehicle']['year'] ?? '') . ' ' . ($csorder['vehicle']['make'] ?? '') . ' ' . ($csorder['vehicle']['model'] ?? ''),
            'BOOKINGTIME' => date('F d h:i A', strtotime($csorder['start_datetime'] ?? '')),
            'CUSTOMEREMAIL' => $email,
            'RENTERNAME' => ($csorder['renter']['first_name'] ?? '') . ' ' . ($csorder['renter']['last_name'] ?? ''),
            'RENTERPHONE' => $csorder['renter']['contact_number'] ?? '',
        ];

        $incrementId = $csorder['increment_id'] ?? '';
        $this->sendTemplate('emails.html.owner_booking_expire_notification', $data, $email, "Booking {$incrementId} Expire notification");
    }
    public function notifyVehicleMileage(string $email, array $allVehicles): void
    {
        $data = [
            'logourl' => legacy_asset('img/DriveitawayBluelogo.png'),
            'VEHICLES' => $allVehicles,
            'CUSTOMEREMAIL' => $email,
        ];
        $this->sendTemplate('emails.html.owner_vehicle_mileage_notification', $data, $email, 'Vehicle Mileage Expire Notification');
    }
    public function notifyEmailToOwner(string $renterPhone, int $ownerId, string $msg, int $csTwilioOrderId): void
    {
        $owner = User::select(['email', 'notify_email'])->find($ownerId);
        $email = !empty($owner->notify_email) ? $owner->notify_email : ($owner->email ?? '');

        if (empty($email)) {
            return;
        }

        $cto = CsTwilioOrder::with(['csOrder.vehicle', 'csOrder.renter'])->find($csTwilioOrderId);

        if (empty($cto) || empty($cto->csOrder)) {
            return;
        }

        $csorder = $cto->csOrder;
        $data = [
            'logourl' => legacy_asset('img/DriveitawayBluelogo.png'),
            'MESSAGE' => $msg,
            'PHONE_NUMBER' => $renterPhone,
            'RENTERNAME' => ($csorder->renter->first_name ?? '') . ' ' . ($csorder->renter->last_name ?? ''),
            'VEHICLE' => $csorder->vehicle->vehicle_name ?? '',
            'BOOKINGTIME' => date('F d h:i A', strtotime($csorder->start_datetime ?? '')),
        ];

        $incrementId = $csorder->increment_id ?? '';
        $this->sendTemplate('emails.html.twilio_recieve', $data, $email, "Booking # {$incrementId} Renter Replied");
    }
    public function sendNotificationToOwnerForVehicleReservation(array $reservation): void
    {
        $owner = User::select(['email', 'notify_email'])->find($reservation['user_id']);
        $email = !empty($owner->notify_email) ? $owner->notify_email : ($owner->email ?? '');

        if (empty($email)) {
            return;
        }

        $driver = User::select([
            'first_name',
            'last_name',
            'contact_number',
            'email',
            'id'
        ])->find($reservation['renter_id']);

        $data = [
            'logourl' => legacy_asset('img/DriveitawayBluelogo.png'),
            'VEHICLE' => $reservation['vehicle_name'] ?? '',
            'BOOKINGTIME' => date('F d h:i A', strtotime($reservation['start_datetime'] ?? '')),
            'CUSTOMEREMAIL' => $email,
            'DRIVERINFO' => ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '') . ' ' . ($driver->contact_number ?? ''),
        ];

        $views = [
            'html' => 'emails.html.owner_booking_notification',
            'text' => 'emails.text.owner_booking_notification',
        ];
        $this->sendTemplate($views, $data, $email, 'New Pending Booking');
    }
    public function sendNotificationToDriverForVehiclePreparation(array $reservation, int $preparationHrs): void
    {
        $driver = User::select(['first_name', 'last_name', 'email'])->find($reservation['renter_id']);
        $email = $driver->email ?? '';

        if (empty($email)) {
            return;
        }

        if ($preparationHrs > 24) {
            $secs = $preparationHrs * 3600;
            $dtF = new \DateTime('@0');
            $dtT = new \DateTime("@{$secs}");
            $formatted = $dtF->diff($dtT)->format('%a day(s), %h hours');
        } else {
            $formatted = "{$preparationHrs} hours";
        }

        $data = [
            'logourl' => legacy_asset('img/DriveitawayBluelogo.png'),
            'VEHICLE' => $reservation['vehicle_name'] ?? '',
            'DRIVERINFO' => ($driver->first_name ?? '') . ' ' . ($driver->last_name ?? ''),
            'PREPARATIONTIME' => $formatted,
            'CUSTOMEREMAIL' => $email,
        ];

        $this->sendTemplate('emails.html.booking_preparation_notification', $data, $email, 'Thanks for Booking');
    }
    public function autonotifyByTwilio(string $telephone, string $msg, int $csTwilioOrderId, int $userId): void
    {
        (new TwilioClient())->autonotifyByTwilio($telephone, $msg, $csTwilioOrderId, $userId);
    }
    public function sendCustomEmail(string $msg, string $email, string $subject): void
    {
        if (empty($email)) {
            return;
        }

        $data = [
            'logourl' => legacy_asset('img/DriveitawayBluelogo.png'),
            'MESSAGE' => $msg,
        ];

        $this->sendTemplate('emails.html.custom_email', $data, $email, $subject, 'driveitawayreceipts@gmail.com');
    }
    public function sendInviteEmail(string $email, string $subject, array $data): void
    {
        if (empty($email)) {
            return;
        }
        $this->sendTemplate('emails.html.invitation_email', $data, $email, $subject);
    }
    public function sendEmailToDealerForVehicleSellRequest(int $reservationId): void
    {
        if (empty($reservationId)) {
            return;
        }

        $res = VehicleReservation::with('vehicle')->find($reservationId);

        if (empty($res)) {
            return;
        }

        $owner = User::select(['email', 'notify_email'])->find($res->user_id);
        $email = !empty($owner->notify_email) ? $owner->notify_email : ($owner->email ?? '');

        $subject = 'DriveitAway Team - Your selling vehicle data is updated';
        $vehicleName = $res->vehicle->vehicle_name ?? '';
        $msg = "Your vehicle {$vehicleName}, it was requested to be sold, is updated. Please check the details and contact admin for more info.";
        $this->sendCustomEmail($msg, $email, $subject);
    }
    public function sendEmailToPushToDealer(int $reservationId, int $flag): void
    {
        if (empty($reservationId)) {
            return;
        }

        $res = VehicleReservation::with('vehicle')->find($reservationId);

        if (empty($res)) {
            return;
        }

        $owner = User::select(['email', 'notify_email'])->find($res->user_id);
        $email = !empty($owner->notify_email) ? $owner->notify_email : ($owner->email ?? '');

        $vehicleName = $res->vehicle->vehicle_name ?? '';

        if ($flag == 1) {
            $subject = 'DriveitAway Team - Your vehicle is requested to be sold';
            $msg = "Your vehicle {$vehicleName} is requested to be sold. Please check the details and contact admin for more info.";
        } else {
            $subject = 'DriveitAway Team - Your vehicle is removed from sale request';
            $msg = "Your vehicle {$vehicleName} is removed from sale request. Please check the details and contact admin for more info.";
        }

        $this->sendCustomEmail($msg, $email, $subject);
    }
    private function sendTemplate($views, array $data, string $to, string $subject, ?string $cc = null): void
    {
        try {
            Mail::send($views, $data, function ($m) use ($to, $subject, $cc) {
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
