<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/EvStation.php
 * Searches NREL Alternative Fuel Stations API for nearby EV charging stations.
 */
class EvStation
{
    private string $apiKey;
    private string $baseApi;

    public function __construct()
    {
        $this->apiKey = config('services.nrel.api_key', '3AwYyoRiCwOsM4tHs6HfzVSsSJMar65JeN4cZTl8');
        $this->baseApi = "https://developer.nrel.gov/api/alt-fuel-stations/v1/nearest.json?fuel_type=ELEC&limit=30&api_key={$this->apiKey}&status=E";
    }

    public function search(?string $address, ?string $lat, ?string $lng): array
    {
        if (!empty($lat) && !empty($lng)) {
            $url = $this->baseApi . "&latitude={$lat}&longitude={$lng}&radius=10&value=EGC7";
        } elseif (!empty($address)) {
            $url = $this->baseApi . '&location=' . urlencode($address) . '&radius=10';
        } else {
            return ['status' => false, 'message' => 'No location provided.', 'result' => []];
        }

        $response = Http::timeout(30)->get($url);
        $stations = $response->json();

        if (empty($stations) || ($stations['total_results'] ?? 0) == 0) {
            return [
                'status'  => false,
                'message' => 'Oops, no station found as per your search criteria. Please try another location.',
                'result'  => [],
            ];
        }

        $result = [];
        foreach ($stations['fuel_stations'] as $station) {
            $result[] = [
                'access_type'      => $station['access_code'] ?? '',
                'station_name'     => $station['station_name'] ?? '',
                'street_address'   => trim(($station['street_address'] ?? '') . ' ' . ($station['city'] ?? '') . ' ' . ($station['state'] ?? '')),
                'latitude'         => $station['latitude'] ?? null,
                'longitude'        => $station['longitude'] ?? null,
                'ev_connector_types' => $station['ev_connector_types'] ?? [],
                'station_phone'    => $station['station_phone'] ?? '',
                'access_note'      => $station['access_days_time'] ?? '',
            ];
        }

        return ['status' => true, 'message' => 'found', 'result' => $result];
    }
}
