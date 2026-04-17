<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/Common.php
 * Large utility class with helpers for users, dates, VIN decoding, orders,
 * geocoding, vehicle status, program options, and more.
 *
 * Methods that depend on CakePHP models have been rewritten to use DB facade.
 * Very large data maps (timezone list, cab list, vehicle types) are preserved as-is.
 */
class Common
{
    // ── Users, roles, and admins ──

    public function isUserExists(array $user, int $userId = 0): bool
    {
        $q = DB::table('users')->where('email', $user['email'] ?? '');
        if ($userId) {
            $q->where('id', '!=', $userId);
        }
        return $q->exists();
    }

    public function getRoleList(): array
    {
        return DB::table('roles')->pluck('title', 'id')->toArray();
    }

    public function getAdminRoleList(): array
    {
        return DB::table('admin_roles')->pluck('name', 'id')->toArray();
    }

    public function getAdminEmail(?int $adminId = null): ?object
    {
        return DB::table('admins')->where('id', $adminId ?: 1)->first(['id', 'name', 'email']);
    }

    public function getAdminList(): array
    {
        $admin = DB::table('admins')->where('id', 1)->first(['id', 'name']);
        return $admin ? [$admin->id => $admin->name] : [];
    }

    public function generatePassword(int $length = 10): string
    {
        $vowels = 'aeuy';
        $consonants = 'bdghjmnpqrstvz';
        $password = '';
        $alt = (bool)random_int(0, 1);
        for ($i = 0; $i < $length; $i++) {
            $password .= $alt ? $consonants[random_int(0, strlen($consonants) - 1)] : $vowels[random_int(0, strlen($vowels) - 1)];
            $alt = !$alt;
        }
        return $password;
    }

    // ── Dates, times, and time zones ──

    public function getDifference(string $startDate, string $endDate, int $format = 6): int
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $diff = abs($end - $start);
        switch ($format) {
            case 1: return (int)round($diff / 60);
            case 2: return (int)round($diff / 3600);
            case 3: return (int)round($diff / 86400);
            case 4: return (int)round($diff / 604800);
            case 5: return (int)round($diff / 2592000);
            default: return (int)round($diff / 31536000);
        }
    }

    public function days_between_dates(string $start, string $end): int
    {
        return (int)(new \DateTime($start))->diff(new \DateTime($end))->days;
    }

    public function years_between_dates(string $start, string $end): int
    {
        return (int)(new \DateTime($start))->diff(new \DateTime($end))->y;
    }

    public function getExactDateAfterMonths(int $timestamp, int $months): int
    {
        $d = new \DateTime();
        $d->setTimestamp($timestamp);
        $d->modify("+{$months} months");
        return $d->getTimestamp();
    }

    // ── VIN decoding ──

    public function getVinDetails(string $vin): array
    {
        if (strlen($vin) !== 17) {
            return [];
        }
        try {
            $response = Http::timeout(15)->get("https://vpic.nhtsa.dot.gov/api/vehicles/decodevinvalues/{$vin}?format=json");
            $data = $response->json();
            $r = $data['Results'][0] ?? [];
            return [
                'make' => $r['Make'] ?? '',
                'year' => $r['ModelYear'] ?? '',
                'model' => $r['Model'] ?? '',
                'transmission' => $r['TransmissionStyle'] ?? '',
                'body' => $this->getVinBody($r['BodyClass'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getVinBody(string $body): string
    {
        $body = strtolower($body);
        if (str_contains($body, 'suv')) return 'SUV';
        if (str_contains($body, 'van') || str_contains($body, 'minivan')) return 'Minivan';
        if (str_contains($body, 'truck') || str_contains($body, 'pickup')) return 'Truck';
        if (str_contains($body, 'coupe') || str_contains($body, 'convertible')) return 'Coupe';
        if (str_contains($body, 'wagon')) return 'Wagon';
        return 'Sedan';
    }

    // ── Orders, increments, and payment typing ──

    public function getOrderIncrementId(): int
    {
        $current = DB::table('cs_eav_settings')->where('key', 'cs_order_id')->value('value');
        $next = ((int)$current) + 1;
        DB::table('cs_eav_settings')->where('key', 'cs_order_id')->update(['value' => $next]);
        return (int)$current;
    }

    public function getpaymentTypeValue(bool $all = false, $key = false, $val = false): array|string
    {
        $types = [
            1 => 'Deposit', 2 => 'Rental', 3 => 'Initial Fee', 4 => 'Insurance', 5 => 'Refund',
            6 => 'Extra Mileage', 7 => 'Lateness Fee', 8 => 'Toll', 9 => 'EMF Insurance',
            10 => 'EMF', 11 => 'DPA Fee', 12 => 'Damage Fee', 13 => 'Penalty',
            14 => 'Adjustment', 15 => 'Wallet Credit', 16 => 'Wallet Debit',
        ];
        if ($all) return $types;
        if ($key !== false) return $types[$key] ?? 'Unknown';
        if ($val !== false) return array_search($val, $types) ?: 'Unknown';
        return $types;
    }

    public function getRefundType(): array
    {
        return [5, 14, 15];
    }

    public function getPayoutTypeValue(bool $all = false, $key = false, $val = false): array|string
    {
        $types = [
            1 => 'Deposit', 2 => 'Rental', 3 => 'Initial Fee', 4 => 'Insurance',
            5 => 'Refund', 6 => 'Extra Mileage', 7 => 'Lateness', 8 => 'Toll',
        ];
        if ($all) return $types;
        if ($key !== false) return $types[$key] ?? 'Unknown';
        return $types;
    }

    public function getBookingTotalDeposit(int $bookingId): string
    {
        $total = DB::table('cs_order_payments')
            ->whereIn('cs_order_id', function ($q) use ($bookingId) {
                $q->select('id')->from('cs_orders')
                    ->where('id', $bookingId)->orWhere('parent_order_id', $bookingId);
            })
            ->where('type', 1)->where('status', 1)
            ->sum('amount');
        return number_format($total, 2);
    }

    public function getBookingTotalInitialFee(int $bookingId): string
    {
        $total = DB::table('cs_order_payments')
            ->whereIn('cs_order_id', function ($q) use ($bookingId) {
                $q->select('id')->from('cs_orders')
                    ->where('id', $bookingId)->orWhere('parent_order_id', $bookingId);
            })
            ->where('type', 3)->where('status', 1)
            ->sum('amount');
        return number_format($total, 2);
    }

    public function getBookingTotalInsurance(int $bookingId): string
    {
        $total = DB::table('cs_order_payments')
            ->whereIn('cs_order_id', function ($q) use ($bookingId) {
                $q->select('id')->from('cs_orders')
                    ->where('id', $bookingId)->orWhere('parent_order_id', $bookingId);
            })
            ->where('type', 4)->where('status', 1)
            ->sum('amount');
        return number_format($total, 2);
    }

    // ── IDs, URLs, and random strings ──

    public function get_unique_id(int $length = 32, string $pool = ''): string
    {
        if (empty($pool)) {
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        $result = '';
        $max = strlen($pool) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $pool[random_int(0, $max)];
        }
        return $result;
    }

    // ── Geography ──

    public function toCoordinates(string $address): array
    {
        $key = config('services.google.maps_key', '');
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query(['address' => $address, 'key' => $key]);
        try {
            $resp = Http::timeout(10)->get($url);
            $data = $resp->json();
            if (($data['status'] ?? '') === 'OK' && isset($data['results'][0]['geometry']['location'])) {
                $loc = $data['results'][0]['geometry']['location'];
                return ['lat' => $loc['lat'], 'lng' => $loc['lng']];
            }
        } catch (\Throwable $e) {
            // fall through
        }
        return ['lat' => 0, 'lng' => 0];
    }

    public function distanceBetweenTwoGeoPoints(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 3959; // miles
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // ── Vehicle status ──

    public function getVehicleStatus(): array
    {
        return [
            0 => 'Pending', 1 => 'Active', 2 => 'Inactive', 3 => 'Rejected',
            4 => 'Deleted', 5 => 'Booked', 6 => 'Paused', 7 => 'Draft',
        ];
    }

    public function getVehicleStatusForChange(): array
    {
        $s = $this->getVehicleStatus();
        unset($s[5]);
        return $s;
    }

    public function getVehicleIssueType(): array
    {
        return [
            1 => 'Depreciation', 2 => 'Mechanical Damage', 3 => 'Body Damage',
            4 => 'Maintenance', 5 => 'Toll', 6 => 'Accident', 7 => 'Roadside Assistance',
        ];
    }

    public function getFleetExpenseStatus(): array
    {
        return $this->getVehicleIssueType();
    }

    // ── States ──

    public function getStates(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
            'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
            'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
            'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
            'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
            'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
            'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
            'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
            'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas',
            'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington',
            'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
        ];
    }

    public function getCanadaStates(): array
    {
        return [
            'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador', 'NS' => 'Nova Scotia', 'NT' => 'Northwest Territories',
            'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
            'SK' => 'Saskatchewan', 'YT' => 'Yukon',
        ];
    }

    public function getStateName(string $code): string
    {
        return $this->getStates()[$code] ?? 'NEW JERSEY';
    }

    // ── Program/financing ──

    public function getVehicleFinancing(?int $id = null): array|string
    {
        $map = [0 => 'None', 1 => 'Rent', 2 => 'Rent To Own', 3 => 'Buy', 4 => 'Lease'];
        if ($id !== null) return $map[$id] ?? 'None';
        return $map;
    }

    public function programOptions(string $selected = ''): array
    {
        $opts = ['' => 'General', 'rideshare' => 'Rideshare', 'both' => 'Both'];
        if ($selected !== '') return [$selected => $opts[$selected] ?? $selected];
        return $opts;
    }

    public function financingOptions(array $selecteds = []): array
    {
        $all = $this->getVehicleFinancing();
        if (empty($selecteds)) return $all;
        return array_intersect_key($all, array_flip($selecteds));
    }

    public function makeRentalDays(int $minRentalPeriod = 336): array
    {
        $days = [];
        $minDays = max(1, (int)ceil($minRentalPeriod / 24));
        for ($i = $minDays; $i <= 365; $i++) {
            $days[$i] = "{$i} day" . ($i > 1 ? 's' : '');
        }
        return $days;
    }

    // ── Weekdays ──

    public function getWeekdays(): array
    {
        return ['sun' => 'Sunday', 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday'];
    }

    // ── Reservation status ──

    public function getReservationStatus(bool $all = false, $key = null): array|string
    {
        $statuses = [0 => 'Pending', 1 => 'Approved', 2 => 'Rejected', 3 => 'Cancelled', 4 => 'Expired', 5 => 'Activated', 6 => 'Completed'];
        if ($key !== null) return $statuses[$key] ?? 'Unknown';
        return $statuses;
    }

    // ── Checkr ──

    public function getCheckrTypeValue(bool $all = false, $key = false, $val = false): array|string
    {
        $types = [0 => 'Not Verified', 1 => 'Submitted', 2 => 'Clear', 3 => 'Consider', 4 => 'Suspended', 5 => 'Dispute'];
        if ($all) return $types;
        if ($key !== false) return $types[$key] ?? 'Unknown';
        return $types;
    }

    // ── Insurance payer ──

    public function getInsurancePayer(?int $payer = null): array|string
    {
        $map = [0 => 'Driver', 1 => 'Dealer', 2 => 'DIA'];
        if ($payer !== null) return $map[$payer] ?? 'Unknown';
        return $map;
    }

    // ── Availability ──

    public function getAvailabilityOptions(?int $val = null): array|string
    {
        $opts = [0 => 'Not Available', 1 => 'Available', 2 => 'Coming Soon'];
        if ($val !== null) return $opts[$val] ?? 'Unknown';
        return $opts;
    }

    // ── Renter helpers ──

    public function getRenterDetails(int $renterId): array
    {
        $user = DB::table('users')->where('id', $renterId)->first();
        return $user ? (array)$user : [];
    }

    public function getChildBookingEndDate(int $bookingId): array
    {
        $child = DB::table('cs_orders')
            ->where('parent_order_id', $bookingId)
            ->where('status', '>=', 2)
            ->orderByDesc('end_datetime')
            ->first();

        if ($child) {
            return [
                'end_datetime' => $child->end_datetime,
                'rent' => $child->rent ?? 0,
                'initial_fee' => $child->initial_fee ?? 0,
            ];
        }
        return [];
    }

    // ── File size parsing ──

    public function FileSizeInBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int)$val;
        return match ($last) {
            'g' => $val * 1073741824,
            'm' => $val * 1048576,
            'k' => $val * 1024,
            default => $val,
        };
    }

    public function toBytes(string $str): int
    {
        return $this->FileSizeInBytes($str);
    }
}
