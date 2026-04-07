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
}
