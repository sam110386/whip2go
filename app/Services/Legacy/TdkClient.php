<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Tdk.php
 * TDK fleet-tracking API client. Most of the original functionality was already
 * commented out in the CakePHP source; the Laravel port preserves method signatures
 * and returns early / stubs where the legacy code did the same.
 */
class TdkClient
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        if (app()->environment('local')) {
            $this->apiUrl = 'https://rjbsapa8a9.execute-api.us-east-1.amazonaws.com/dev/';
            $this->apiKey = 'fbwaOeDRmWaUat4wtqSgx67KUvNW5bmp7afneS1G';
        } else {
            $this->apiUrl = 'https://v2ckihx5jk.execute-api.us-east-1.amazonaws.com/prod/';
            $this->apiKey = 'JFl9dGRxER2qfTe4tnPbDEW3dpkUmar7E5C5EIWa';
        }
    }

    /**
     * Sync a vehicle to TDK on vehicle-create event.
     * Commented out in the original CakePHP code; kept as stub.
     */
    public function syncVehicleTDK(int $vehicleId): void
    {
        Log::info("TdkClient::syncVehicleTDK – stub for vehicle {$vehicleId}");
    }

    public function addVehicle(array $vehicleArray): ?array
    {
        Log::info('TdkClient::addVehicle – disabled in legacy; returning null');
        return null;
    }

    /**
     * Notify TDK about booking start/end.
     * Commented out in the original CakePHP code; returns empty array.
     */
    public function tdkBookingStarted(int $bookingId, bool $started = false): array
    {
        Log::info("TdkClient::tdkBookingStarted – stub for booking {$bookingId}, started=" . ($started ? 'true' : 'false'));
        return [];
    }

    private function sendHttpRequest(string $body, string $action): ?array
    {
        Log::info("TdkClient::sendHttpRequest – stub for action {$action}");
        return null;
    }
}
