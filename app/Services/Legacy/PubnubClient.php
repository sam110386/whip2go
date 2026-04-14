<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Pubnub.php
 *
 * Push notification service using PubNub for iOS (APNs) and Android (FCM).
 * The legacy app used a custom Pubnubpush vendor class. This port preserves
 * all payload structures and delegates to the same publish helper.
 *
 * To fully operate, the Pubnubpush vendor class must be available or
 * replaced with the PubNub PHP SDK.
 */
class PubnubClient
{
    private string $sub_key;
    private string $pub_key;
    private string $secret;

    public function __construct()
    {
        $this->sub_key = (string) config('services.pubnub.sub_key', '');
        $this->pub_key = (string) config('services.pubnub.pub_key', '');
        $this->secret = (string) config('services.pubnub.secret', '');
    }

    private function publish(int $userId, array $push): ?array
    {
        if (!class_exists('Pubnubpush')) {
            Log::warning('PubnubClient::publish – Pubnubpush vendor class not available, skipping push for user ' . $userId);
            return null;
        }
        $pubnub = new \Pubnubpush($this->pub_key, $this->sub_key, $this->secret);
        return $pubnub->pubnubpublish($userId, $push);
    }

    private function apnsPushBlock(): array
    {
        return [
            [
                'auth_method' => 'token',
                'targets' => [
                    ['topic' => 'com.mindseye.carshare', 'environment' => 'production'],
                ],
                'version' => 'v2',
            ],
        ];
    }

    public function notifyForActivateBooking(array $data): ?array
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $body = $data['msg'] ?? 'DIA booking activated';
        $push = [
            'text' => 'Booking Activated',
            'pn_apns' => [
                'aps' => [
                    'alert' => [
                        'title' => 'DriveItAway',
                        'data' => [
                            'type' => 'booking_activated',
                            'booking_id' => $data['bookingid'],
                            'path' => 'activeBookingView',
                            'vehicle_id' => $data['vehicleid'],
                            'title' => 'DriveItAway',
                            'body' => $body,
                        ],
                        'body' => $body,
                    ],
                    'sound' => 'default',
                ],
                'pn_push' => $this->apnsPushBlock(),
            ],
            'pn_gcm' => [
                'notification' => ['title' => 'DriveItAway', 'body' => $body, 'sound' => 'default'],
                'data' => [
                    'type' => 'booking_activated',
                    'booking_id' => $data['bookingid'],
                    'path' => 'activeBookingView',
                    'vehicle_id' => $data['vehicleid'],
                    'title' => 'DriveItAway',
                    'body' => $body,
                ],
            ],
        ];
        return $this->publish($data['user_id'], $push);
    }

    public function notifyForPaymentFailed(array $data): ?array
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $body = $data['msg'] ?? 'DIA booking payment failed';
        $push = [
            'text' => 'Payment Failed',
            'pn_apns' => [
                'aps' => [
                    'alert' => [
                        'title' => 'DriveItAway',
                        'data' => [
                            'type' => 'paymentfailed',
                            'booking_id' => $data['bookingid'],
                            'path' => 'activeBookingView',
                            'vehicle_id' => $data['vehicleid'],
                            'title' => 'DriveItAway',
                            'body' => $body,
                        ],
                        'body' => $body,
                    ],
                    'sound' => 'default',
                ],
                'pn_push' => $this->apnsPushBlock(),
            ],
            'pn_gcm' => [
                'notification' => ['title' => 'DriveItAway', 'body' => $body, 'sound' => 'default'],
                'data' => [
                    'type' => 'paymentfailed',
                    'booking_id' => $data['bookingid'],
                    'path' => 'activeBookingView',
                    'vehicle_id' => $data['vehicleid'],
                    'title' => 'DriveItAway',
                    'body' => $body,
                ],
            ],
        ];
        return $this->publish($data['user_id'], $push);
    }

    public function notifyForPTO(array $data): ?array
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $body = 'Your Goal About to Complete!. Click here for More Info';
        $push = [
            'text' => 'Your Goal About to Complete!!',
            'pn_apns' => [
                'aps' => [
                    'alert' => [
                        'title' => 'DriveItAway',
                        'data' => [
                            'type' => 'pto',
                            'booking_id' => $data['bookingid'],
                            'path' => 'pathscreen',
                            'vehicle_id' => $data['vehicleid'],
                            'title' => 'DriveItAway',
                            'body' => $body,
                        ],
                        'body' => $body,
                    ],
                    'sound' => 'default',
                ],
                'pn_push' => $this->apnsPushBlock(),
            ],
            'pn_gcm' => [
                'notification' => ['title' => 'DriveItAway', 'body' => $body, 'sound' => 'default'],
                'data' => [
                    'type' => 'pto',
                    'booking_id' => $data['bookingid'],
                    'path' => 'pathscreen',
                    'vehicle_id' => $data['vehicleid'],
                    'title' => 'DriveItAway',
                    'body' => $body,
                ],
            ],
        ];
        return $this->publish($data['user_id'], $push);
    }

    public function notify(array $data): ?array
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $msg = $data['msg'];
        $push = [
            'text' => $msg,
            'pn_apns' => [
                'aps' => [
                    'alert' => [
                        'title' => 'DriveItAway',
                        'body' => $msg,
                        'data' => ['title' => 'DriveItAway', 'body' => $msg],
                    ],
                    'sound' => 'default',
                ],
                'pn_push' => $this->apnsPushBlock(),
            ],
            'pn_gcm' => [
                'notification' => ['title' => 'DriveItAway', 'body' => $msg, 'sound' => 'default'],
                'data' => ['title' => 'DriveItAway', 'body' => $msg],
            ],
        ];
        return $this->publish($data['user_id'], $push);
    }

    public function notifyForOffer(array $data): ?array
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $body = $data['msg'] ?? 'DIA New Offer!!';
        $push = [
            'text' => 'New Offer',
            'pn_apns' => [
                'aps' => [
                    'alert' => [
                        'title' => 'DriveItAway',
                        'data' => [
                            'type' => 'offer_created',
                            'path' => 'myOfferScreenView',
                            'title' => 'DriveItAway',
                            'body' => $body,
                        ],
                        'body' => $body,
                    ],
                    'sound' => 'default',
                ],
                'pn_push' => $this->apnsPushBlock(),
            ],
            'pn_gcm' => [
                'notification' => ['title' => 'DriveItAway', 'body' => $body, 'sound' => 'default'],
                'data' => [
                    'type' => 'offer_created',
                    'path' => 'myOfferScreenView',
                    'title' => 'DriveItAway',
                    'body' => $body,
                ],
            ],
        ];
        return $this->publish($data['user_id'], $push);
    }

    public function notifyPendingStatusChange(array $data): ?array
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $body = $data['msg'] ?? 'Your booking status changed!!';
        $push = [
            'text' => 'Booking Status Changed',
            'pn_apns' => [
                'aps' => [
                    'alert' => [
                        'title' => 'DriveItAway',
                        'data' => [
                            'type' => 'pending_view',
                            'path' => 'pendingBookingView',
                            'booking_id' => $data['bookingid'],
                            'vehicle_id' => $data['vehicleid'],
                            'title' => 'DriveItAway',
                            'body' => $body,
                        ],
                        'body' => $body,
                    ],
                    'sound' => 'default',
                ],
                'pn_push' => $this->apnsPushBlock(),
            ],
            'pn_gcm' => [
                'notification' => ['title' => 'DriveItAway', 'body' => $body, 'sound' => 'default'],
                'data' => [
                    'type' => 'pending_view',
                    'path' => 'pendingBookingView',
                    'booking_id' => $data['bookingid'],
                    'vehicle_id' => $data['vehicleid'],
                    'title' => 'DriveItAway',
                    'body' => $body,
                ],
            ],
        ];
        return $this->publish($data['user_id'], $push);
    }

    public function notifyUberLocationUpdate(array $data): ?array
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $push = [
            'type' => 'location_update',
            'path' => 'UberMapView',
            'lat' => $data['latitude'],
            'lng' => $data['longitude'],
            'bearing' => $data['bearing'],
            'booking_id' => $data['id'],
            'message' => 'Your booking status changed!!',
        ];
        return $this->publish($data['user_id'], $push);
    }

    public function notifyUberUpdate(array $data): ?array
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $body = $data['message'] ?? 'Your booking status changed!!';
        $push = [
            'text' => 'Booking Status Changed',
            'pn_apns' => [
                'aps' => [
                    'alert' => [
                        'title' => 'DriveItAway',
                        'data' => [
                            'type' => 'status_update',
                            'path' => 'UberMapView',
                            'booking_id' => $data['id'],
                            'status' => $data['status'],
                            'title' => 'DriveItAway',
                            'body' => $body,
                        ],
                        'body' => $body,
                    ],
                    'sound' => 'default',
                ],
                'pn_push' => $this->apnsPushBlock(),
            ],
            'pn_gcm' => [
                'notification' => ['title' => 'DriveItAway', 'body' => $body, 'sound' => 'default'],
                'data' => [
                    'type' => 'status_update',
                    'path' => 'UberMapView',
                    'booking_id' => $data['id'],
                    'status' => $data['status'],
                    'title' => 'DriveItAway',
                    'body' => $body,
                ],
            ],
        ];
        return $this->publish($data['user_id'], $push);
    }
}
