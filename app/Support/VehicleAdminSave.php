<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Normalizes admin vehicle form POST to vehicles row (Cake VehiclesController::admin_add subset).
 */
final class VehicleAdminSave
{
    /** @return array<string, string> */
    public static function availabilityOptions(): array
    {
        return ['0' => 'Available Now', '1' => 'Waitlist', '2' => 'Normal Wait Time'];
    }

    /** @return array<string, string> */
    public static function financingOptions(): array
    {
        return ['1' => 'Rent', '2' => 'Rent To Own', '3' => 'Lease', '4' => 'Lease to own'];
    }

    /**
     * @param  \App\Models\Legacy\Vehicle|null  $existing
     * @return array<string, mixed>
     */
    public static function buildRow(array $p, $existing): array
    {
        $cab = trim((string)($p['cab_type'] ?? ''));
        if ($cab === '') {
            $cab = 'Regular Sedan';
        }

        $fare = strtoupper(trim((string)($p['fare_type'] ?? 'S')));
        if (!in_array($fare, ['S', 'D', 'L'], true)) {
            $fare = 'S';
        }

        $dayRent = (float)preg_replace('/[^0-9.]/', '', (string)($p['day_rent'] ?? '0'));
        $rate = (float)preg_replace('/[^0-9.]/', '', (string)($p['rate'] ?? '0'));
        if ($fare === 'D' || $fare === 'L') {
            $dayRent = 0;
        }

        $uid = (int)($p['user_id'] ?? ($existing->user_id ?? 0));

        $row = [
            'user_id' => $uid,
            'vin_no' => strtoupper(preg_replace('/\s+/', '', (string)($p['vin_no'] ?? ''))),
            'make' => trim((string)($p['make'] ?? '')),
            'model' => trim((string)($p['model'] ?? '')),
            'year' => trim((string)($p['year'] ?? '')),
            'plate_number' => trim((string)($p['plate_number'] ?? '')),
            'stock_no' => trim((string)($p['stock_no'] ?? '')),
            'type' => in_array($p['type'] ?? 'demo', ['real', 'demo'], true) ? $p['type'] : 'demo',
            'waitlist' => isset($p['waitlist']) ? (int)$p['waitlist'] : (int)($existing->waitlist ?? 2),
            'availability_date' => self::parseDateYmd($p['availability_date'] ?? null),
            'trim' => trim((string)($p['trim'] ?? '')),
            'engine' => trim((string)($p['engine'] ?? '')),
            'transmition_type' => in_array($p['transmition_type'] ?? 'M', ['M', 'A'], true) ? $p['transmition_type'] : 'M',
            'cab_type' => $cab,
            'color' => trim((string)($p['color'] ?? '')),
            'interior_color' => trim((string)($p['interior_color'] ?? '')),
            'mpg_city' => (int)($p['mpg_city'] ?? 0),
            'mpg_hwy' => (int)($p['mpg_hwy'] ?? 0),
            'doors' => (int)($p['doors'] ?? 4),
            'equipment' => (string)($p['equipment'] ?? ''),
            'details' => (string)($p['details'] ?? ''),
            'disclosure' => (string)($p['disclosure'] ?? ''),
            'financing' => (int)($p['financing'] ?? 2),
            'allowed_miles' => (float)preg_replace('/[^0-9.]/', '', (string)($p['allowed_miles'] ?? '33.33')),
            'insurance_included_fee' => isset($p['insurance_included_fee']) ? (int)$p['insurance_included_fee'] : 1,
            'maintenance_included_fee' => isset($p['maintenance_included_fee']) ? (int)$p['maintenance_included_fee'] : 1,
            'ccm_auth_no' => trim((string)($p['ccm_auth_no'] ?? '')),
            'toll_enabled' => isset($p['toll_enabled']) ? (int)$p['toll_enabled'] : 0,
            'gps_serialno' => trim((string)($p['gps_serialno'] ?? '')),
            'passtime_serialno' => trim((string)($p['passtime_serialno'] ?? '')),
            'autopi_unit_id' => trim((string)($p['autopi_unit_id'] ?? '')),
            'odometer' => trim((string)($p['odometer'] ?? '')),
            'total_mileage' => (int)($p['total_mileage'] ?? 0),
            'multi_location' => isset($p['multi_location']) ? (int)$p['multi_location'] : 0,
            'registered_name' => trim((string)($p['registered_name'] ?? '')),
            'registered_state' => trim((string)($p['registered_state'] ?? 'NY')),
            'reg_name_date' => self::parseDateFlexible($p['reg_name_date'] ?? ''),
            'reg_name_exp_date' => self::parseDateFlexible($p['reg_name_exp_date'] ?? ''),
            'insurance_company' => trim((string)($p['insurance_company'] ?? '')),
            'insurance_policy_no' => trim((string)($p['insurance_policy_no'] ?? '')),
            'insurance_policy_date' => self::normalizePolicyDate($p['insurance_policy_date'] ?? ''),
            'insurance_policy_exp_date' => self::parseDateFlexible($p['insurance_policy_exp_date'] ?? ''),
            'inspection_exp_date' => self::parseDateFlexible($p['inspection_exp_date'] ?? ''),
            'state_insp_exp_date' => self::parseDateFlexible($p['state_insp_exp_date'] ?? ''),
            'rate' => $rate,
            'day_rent' => $dayRent,
            'fare_type' => $fare,
            'rent_opt' => '',
            'msrp' => (float)preg_replace('/[^0-9.]/', '', (string)($p['msrp'] ?? '0')),
            'premium_msrp' => (float)preg_replace('/[^0-9.]/', '', (string)($p['premium_msrp'] ?? '0')),
            'homenet_msrp' => (float)preg_replace('/[^0-9.]/', '', (string)($p['homenet_msrp'] ?? '0')),
            'homenet_modelnumber' => trim((string)($p['homenet_modelnumber'] ?? '')),
            'vehicleCostInclRecon' => (float)preg_replace('/[^0-9.]/', '', (string)($p['vehicleCostInclRecon'] ?? '0')),
            'kbbnadaWholesaleBook' => (float)preg_replace('/[^0-9.]/', '', (string)($p['kbbnadaWholesaleBook'] ?? '0')),
            'is_featured' => isset($p['is_featured']) ? (int)$p['is_featured'] : 0,
            'visibility' => isset($p['visibility']) ? (int)$p['visibility'] : 1,
            'rideshare' => isset($p['rideshare']) ? (int)$p['rideshare'] : 0,
            'auth_require' => isset($p['auth_require']) ? (int)$p['auth_require'] : 0,
            'battery' => (int)($p['battery'] ?? 0),
            'pto' => isset($p['pto']) ? (int)$p['pto'] : 0,
            'roadside_assistance_included' => isset($p['roadside_assistance_included']) ? (int)$p['roadside_assistance_included'] : 0,
            'sharing_allowed' => isset($p['sharing_allowed']) ? (int)$p['sharing_allowed'] : 0,
            'status' => 1,
        ];

        if ($existing !== null) {
            $row['passtime_status'] = (int)($existing->passtime_status ?? 1);
            $row['booked'] = (int)($existing->booked ?? 0);
            $row['trash'] = (int)($existing->trash ?? 0);
            $row['last_mile'] = (int)($existing->last_mile ?? 0);
        } else {
            $row['passtime_status'] = 1;
            $row['booked'] = 0;
            $row['trash'] = 0;
            $row['last_mile'] = 0;
        }

        $row['vehicle_name'] = self::composeVehicleName($row);

        return $row;
    }

    /**
     * @param  string|\DateTimeInterface|null  $dbValue
     */
    public static function formatDateInput($dbValue): string
    {
        if ($dbValue === null || $dbValue === '') {
            return '';
        }
        try {
            return Carbon::parse($dbValue)->format('m/d/Y');
        } catch (\Throwable $e) {
            return is_scalar($dbValue) ? (string)$dbValue : '';
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function composeVehicleName(array $row): string
    {
        $year = isset($row['year']) && $row['year'] !== '' ? substr((string)$row['year'], -2) . '-' : '';
        $make = isset($row['make']) && $row['make'] !== '' ? str_replace(' ', '_', (string)$row['make']) . '-' : '';
        $model = isset($row['model']) && $row['model'] !== '' ? str_replace(' ', '_', (string)$row['model']) : '';
        $vin = isset($row['vin_no']) && $row['vin_no'] !== '' ? '-' . substr((string)$row['vin_no'], -6) : '';

        return $year . $make . $model . $vin;
    }

    private static function parseDateYmd(?string $s): ?string
    {
        return self::parseDateFlexible($s);
    }

    private static function parseDateFlexible(?string $s): ?string
    {
        $s = trim((string)$s);
        if ($s === '') {
            return null;
        }
        foreach (['m/d/Y', 'Y-m-d', 'm-d-Y'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $s)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }
        try {
            return Carbon::parse($s)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function normalizePolicyDate(?string $s): ?string
    {
        $s = trim((string)$s);
        if ($s === '') {
            return null;
        }
        $parsed = self::parseDateFlexible($s);

        return $parsed ?? $s;
    }
}
