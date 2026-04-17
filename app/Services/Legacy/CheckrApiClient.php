<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/CheckrApi.php
 * Manages Checkr background-check candidates, reports, and MVR retrieval.
 */
class CheckrApiClient
{
    private string $apiUrl;
    private string $apiKey;

    private const CANADA_PROVINCES = [
        'NL','PE','NS','NB','QC','ON','MB','SK','AB','BC','YT','NT','NU',
    ];

    private array $candidateDefaults = [
        'first_name' => '',
        'middle_name' => '',
        'no_middle_name' => true,
        'last_name' => '',
        'mother_maiden_name' => null,
        'email' => '',
        'phone' => '',
        'zipcode' => '',
        'dob' => '1970-01-22',
        'driver_license_number' => '',
        'driver_license_state' => 'NY',
        'previous_driver_license_number' => null,
        'previous_driver_license_state' => null,
        'copy_requested' => false,
        'custom_id' => null,
        'report_ids' => [],
        'geo_ids' => [],
    ];

    public function __construct()
    {
        $this->apiUrl = config('services.checkr.url', 'https://api.checkr.com/v1/');
        $this->apiKey = config('services.checkr.key', '');
    }

    /**
     * Add new candidate and persist UserReport record.
     */
    public function addCandidateAndSave(array $userdata): array
    {
        $result = $this->addCandidateToApi($userdata);

        if (!$result['status']) {
            return ['status' => false, 'message' => $result['message'], 'result' => []];
        }

        DB::table('user_reports')->insert([
            'user_id'   => $userdata['id'],
            'channel'   => 'CKR',
            'checkr_id' => $result['candidate_id'],
        ]);

        return $result;
    }

    /**
     * Update an existing candidate on Checkr.
     */
    public function updateCandidateToApi(array $userdata, string $existingCheckrId): array
    {
        $result = $this->addCandidateToApi($userdata, $existingCheckrId);
        if (!$result['status']) {
            return ['status' => false, 'message' => $result['message'], 'result' => []];
        }
        return $result;
    }

    public function addCandidateToApi(array $user, string $candidateId = ''): array
    {
        $candidate = $this->candidateDefaults;

        if (!empty($candidateId)) {
            $candidate['id'] = $candidateId;
        }
        $candidate['custom_id'] = $user['id'] ?? '';
        $candidate['first_name'] = $user['first_name'] ?? '';
        $candidate['last_name'] = $user['last_name'] ?? '';
        $candidate['email'] = $user['email'] ?? '';
        $candidate['phone'] = $user['contact_number'] ?? '';

        $isCanada = in_array($user['licence_state'] ?? '', self::CANADA_PROVINCES);
        $zipRaw = preg_replace('/[^0-9A-Z]/', '', strtoupper($user['zip'] ?? ''));
        $candidate['zipcode'] = $isCanada ? substr($zipRaw, 0, 6) : substr($zipRaw, 0, 5);
        $candidate['dob'] = !empty($user['dob']) ? date('Y-m-d', strtotime($user['dob'])) : '';
        $candidate['driver_license_number'] = $user['licence_number'] ?? '';
        $candidate['driver_license_state'] = $user['licence_state'] ?? '';

        $country = !empty($user['country']) ? 'CA' : ($isCanada ? 'CA' : 'US');
        $candidate['work_locations'][] = [
            'country' => $country,
            'state' => $user['licence_state'] ?? '',
        ];

        $endpoint = !empty($candidateId) ? "candidates/{$candidateId}" : 'candidates';
        $result = $this->sendHttpRequest($endpoint, $candidate);

        if (empty($result)) {
            return ['status' => false, 'message' => 'Checkr API is down'];
        }
        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error']];
        }

        return ['status' => true, 'message' => 'Driver data pushed to Checker console successfully', 'candidate_id' => $result['id']];
    }

    public function createPackage(): ?array
    {
        return $this->sendHttpRequest('packages', [
            'name' => 'DIV Vehicle Report',
            'slug' => 'dia_mvr',
            'screenings' => [['type' => 'motor_vehicle_report', 'subtype' => null]],
        ]);
    }

    public function createReport(string $candidateId, array $worklocation): array
    {
        $isCanada = in_array($worklocation['licence_state'] ?? '', self::CANADA_PROVINCES);
        $country = $isCanada ? 'CA' : 'US';
        $workLoc = [
            'country' => $country,
            'state' => !empty($worklocation['licence_state']) ? $worklocation['licence_state'] : ($worklocation['state'] ?? ''),
            'city' => $worklocation['city'] ?? '',
        ];

        $report = ['package' => 'dia_mvr', 'candidate_id' => $candidateId, 'work_locations' => [$workLoc]];
        if ($country === 'CA') {
            $report['package'] = 'international_mvr';
            $res = $this->sendHttpRequest('invitations', $report);
        } else {
            $res = $this->sendHttpRequest('reports', $report);
        }

        if (empty($res)) {
            return ['status' => false, 'message' => 'Checkr API is down'];
        }
        if (isset($res['error'])) {
            return ['status' => false, 'message' => $res['error']];
        }

        return ['status' => true, 'message' => "Driver's MVR report requested successfully", 'report_id' => $res['id']];
    }

    public function getReport(string $reportId): array
    {
        $main = $this->sendHttpRequest("reports/{$reportId}");

        if (empty($main)) {
            return ['status' => false, 'message' => 'Checkr API is down', 'checkrMsg' => 'Checkr API is down'];
        }
        if (isset($main['error'])) {
            return ['status' => false, 'message' => $main['error'], 'checkrMsg' => $main['error'] . '. Car cannot be rented. Please contact support.'];
        }

        $status = $main['status'] ?? '';
        if (in_array($status, ['suspended', 'dispute', 'consider'])) {
            $label = strtoupper($status);
            return [
                'status' => false,
                'message' => "Sorry Report has {$label} status",
                'checkrMsg' => "This user's MVR returned \"{$label}\". Car cannot be rented. Please contact support.",
            ];
        }

        if ($status === 'clear') {
            $actual = $this->sendHttpRequest('motor_vehicle_reports/' . ($main['motor_vehicle_report_id'] ?? ''));
            if (isset($actual['error'])) {
                return ['status' => false, 'message' => $actual['error']];
            }
            return ['status' => true, 'message' => 'Driver MVR report is successfully fetched', 'data' => $actual];
        }

        return [
            'status' => false,
            'message' => "Sorry Report is {$status}",
            'checkrMsg' => "This user's MVR returned \"{$status}\". Car cannot be rented. Please contact support.",
        ];
    }

    public function getMotorVehicleReport(string $mvrId): array
    {
        $actual = $this->sendHttpRequest("motor_vehicle_reports/{$mvrId}");
        if (isset($actual['error'])) {
            return ['status' => false, 'message' => $actual['error'], 'data' => 'error returned'];
        }
        return ['status' => true, 'message' => 'Driver MVR report is successfully fetched', 'data' => $actual];
    }

    public function getInternationalMotorVehicleReport(string $mvrId): array
    {
        $actual = $this->sendHttpRequest("international_motor_vehicle_reports/{$mvrId}");
        if (isset($actual['error'])) {
            return ['status' => false, 'message' => $actual['error'], 'data' => 'error returned'];
        }
        return ['status' => true, 'message' => 'Driver MVR report is successfully fetched', 'data' => $actual];
    }

    private function sendHttpRequest(string $api, array $body = []): ?array
    {
        $url = rtrim($this->apiUrl, '/') . '/' . $api;

        $pending = Http::withHeaders([
            'Content-type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
            'Accept-Charset' => 'utf-8',
        ])->timeout(30);

        $response = empty($body) ? $pending->get($url) : $pending->post($url, $body);

        return $response->json();
    }
}
