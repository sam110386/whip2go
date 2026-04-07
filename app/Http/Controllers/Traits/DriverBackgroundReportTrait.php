<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\UserReport;

trait DriverBackgroundReportTrait
{
    /**
     * Add user as a candidate to Checkr (driver background check service).
     * Mirrors CakePHP's DriverBackgroundReport::addCandidateToDriverBackgroundReport()
     */
    protected function addCandidateToDriverBackgroundReport(int $userId): array
    {
        $user = User::find($userId);

        if (empty($user)) {
            return ['status' => false, 'message' => 'User not found.'];
        }

        $checkrClass = '\\App\\Lib\\Legacy\\CheckrApi';
        if (!class_exists($checkrClass)) {
            return ['status' => false, 'message' => 'Checkr API not configured.'];
        }

        $checkr = new $checkrClass();

        // Checkr requires DOB and SSN — both encrypted in legacy DB
        $candidateData = [
            'first_name'  => $user->first_name,
            'last_name'   => $user->last_name,
            'email'       => $user->email,
            'phone'       => $user->contact_number,
            'dob'         => $user->dob ?? null,
            'ssn'         => !empty($user->ss_no) ? base64_decode($user->ss_no) : null,
            'driver_license_number' => !empty($user->licence_number) ? base64_decode($user->licence_number) : null,
            'driver_license_state'  => $user->licence_state ?? null,
            'zipcode'     => $user->zip ?? null,
        ];

        $result = $checkr->addCandidate($candidateData);

        if (!empty($result['status']) && $result['status'] === 'success') {
            // Persist the candidate_id to user_reports
            $report = UserReport::firstOrNew(['user_id' => $userId]);
            $report->user_id      = $userId;
            $report->candidate_id = $result['candidate_id'] ?? null;
            $report->status       = 0;
            $report->save();

            return ['status' => true, 'message' => 'User is added to Checkr API for processing'];
        }

        return ['status' => false, 'message' => $result['message'] ?? 'Checkr API error.'];
    }

    /**
     * Pull the latest background report from Checkr for the given user.
     * Mirrors CakePHP's DriverBackgroundReport::pullBackgroundReport()
     */
    protected function pullBackgroundReport(int $userId): array
    {
        $report = UserReport::where('user_id', $userId)->first();

        if (empty($report) || empty($report->checkr_reportid)) {
            return ['status' => false, 'message' => 'No Checkr report ID found for this user.'];
        }

        $checkrClass = '\\App\\Lib\\Legacy\\CheckrApi';
        if (!class_exists($checkrClass)) {
            return ['status' => false, 'message' => 'Checkr API not configured.'];
        }

        $checkr = new $checkrClass();
        $result = $checkr->getReport($report->checkr_reportid);

        if (!empty($result['status']) && $result['status'] === 'clear') {
            UserReport::where('user_id', $userId)->update([
                'report_status' => $result['status'],
                'adjudication'  => $result['adjudication'] ?? null,
            ]);
            return ['status' => true, 'message' => 'User Report is Ready'];
        }

        return ['status' => false, 'message' => $result['message'] ?? 'Report not ready yet.'];
    }

    /**
     * Request a new background report on Checkr for an already-added candidate.
     * Mirrors CakePHP's DriverBackgroundReport::createBackgroundReport()
     */
    protected function createBackgroundReport(int $userId): array
    {
        $report = UserReport::where('user_id', $userId)->first();

        if (empty($report) || empty($report->candidate_id)) {
            return ['status' => false, 'message' => 'Candidate not found in Checkr.'];
        }

        $checkrClass = '\\App\\Lib\\Legacy\\CheckrApi';
        if (!class_exists($checkrClass)) {
            return ['status' => false, 'message' => 'Checkr API not configured.'];
        }

        $checkr = new $checkrClass();
        $result = $checkr->createReport($report->candidate_id);

        if (!empty($result['status']) && $result['status'] === 'success') {
            UserReport::where('user_id', $userId)->update([
                'checkr_reportid'        => $result['report_id']            ?? null,
                'motor_vehicle_report_id' => $result['motor_vehicle_report_id'] ?? null,
                'status'                 => 1,
            ]);
            return ['status' => true, 'message' => 'User Report is requested'];
        }

        return ['status' => false, 'message' => $result['message'] ?? 'Failed to create report.'];
    }
}
