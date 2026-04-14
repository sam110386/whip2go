<?php

namespace App\Http\Controllers\Traits;

use Carbon\Carbon;

trait CommonTrait {

    public function days_between_dates($date_start, $date_end) {
        if (empty($date_start) || empty($date_end)) return 0;
        return (int) Carbon::parse($date_start)->diffInDays(Carbon::parse($date_end));
    }

    public function getReservationStatus($all = false, $key = null) {
        $statuses = [
            0 => "In Review",
            1 => "Active",
            2 => "Cancelled",
            3 => "Completed",
            4 => "Expired",
            5 => "Rejected",
            6 => "Draft",
            7 => "Pending Payment",
            8 => "Awaiting Pick-up",
            9 => "Pick-up Completed",
            10 => "Waitlist"
        ];

        if ($all) return $statuses;
        return $statuses[$key] ?? "Unknown";
    }

    public function getMissingChecklist($orderCheckLists, $checklists, $validateMaybe = false) {
        $orderCheckLists = is_array($orderCheckLists) ? $orderCheckLists : json_decode($orderCheckLists ?? '[]', true);
        $missing = [];
        foreach ($checklists as $key => $label) {
            if (!isset($orderCheckLists[$key]) || $orderCheckLists[$key] == 0) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    public function makeDateInOption($basedate, $arraydata) {
        // Simple implementation for displaying scheduled dates
        $results = [];
        $base = Carbon::parse($basedate);
        foreach ($arraydata as $opt) {
            $results[] = [
                'date' => $base->copy()->addDays($opt['after_day'] ?? 0)->format('m/d/Y'),
                'amount' => $opt['amount'] ?? 0
            ];
        }
        return $results;
    }

    /**
     * toCoordinates: Fetch latitude and longitude for a given address
     */
    public function toCoordinates($address) {
        $address = str_replace(" ", "+", $address);
        $apiKey = config('services.google.maps_api_key');
        
        if (empty($apiKey)) {
            return ['lat' => 0, 'lng' => 0];
        }

        $url = "https://maps.google.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response);
            
            if (isset($result->status) && $result->status == "OK") {
                return [
                    'lat' => $result->results[0]->geometry->location->lat,
                    'lng' => $result->results[0]->geometry->location->lng
                ];
            }
        } catch (\Exception $e) {
            \Log::error("Geocoding failed: " . $e->getMessage());
        }

        return ['lat' => 0, 'lng' => 0];
    }
}
