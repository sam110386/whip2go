<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Ported from CakePHP app/Controller/Traits/DriverBackgroundReport.php
 *
 * Manages driver background checks via Checkr and Digisure APIs.
 */
trait DriverBackgroundReportTrait
{
    private array $_userObj = [];

    /**
     * Add a candidate for driver background report.
     */
    public function addCandidateToDriverBackgroundReport($userid, $owner = '', $TrustScore = false): array
    {
        $User = DB::table('users')
            ->leftJoin('user_license_details as LicenseDetail', 'LicenseDetail.user_id', '=', 'users.id')
            ->where('users.id', $userid)
            ->select(
                'users.*',
                'LicenseDetail.givenName', 'LicenseDetail.lastName', 'LicenseDetail.dateOfBirth',
                'LicenseDetail.dateOfExpiry', 'LicenseDetail.addressCity', 'LicenseDetail.addressState',
                'LicenseDetail.addressPostalCode', 'LicenseDetail.documentNumber'
            )
            ->first();

        if (empty($User)) {
            return ['status' => false, 'message' => 'User not found.', 'result' => []];
        }

        $this->_userObj = (array) $User;
        $userArr = (array) $User;

        // TODO: Replace Security::decrypt with Crypt::decryptString or legacy decryption
        $decryptedDocNumber = $this->decryptSecurityValue($userArr['documentNumber'] ?? '');
        if (empty($userArr['documentNumber']) || empty($decryptedDocNumber)) {
            return ['status' => false, 'message' => "Sorry, driver didnt add his license number yet.", 'result' => []];
        }

        $userdata = [
            'first_name'      => $userArr['first_name'],
            'last_name'       => $userArr['last_name'],
            'id'              => $userArr['id'],
            'email'           => $userArr['email'],
            'contact_number'  => $userArr['contact_number'],
            'zip'             => substr($userArr['zip'] ?? '', 0, 8),
            'dob'             => !empty($userArr['dateOfBirth']) ? $userArr['dateOfBirth'] : $userArr['dob'],
            'licence_number'  => $decryptedDocNumber,
            'licence_exp_date' => !empty($userArr['dateOfExpiry']) ? $userArr['dateOfExpiry'] : ($userArr['licence_exp_date'] ?? ''),
            'licence_state'   => !empty($userArr['addressState']) ? $userArr['addressState'] : ($userArr['state'] ?? ''),
            'address'         => $userArr['address'] ?? '',
            'city'            => $userArr['city'] ?? '',
            'state'           => $userArr['state'] ?? '',
        ];

        $userExist = DB::table('user_reports')->where('user_id', $userid)->first();

        try {
            if (!empty($userExist)) {
                $existArr = (array) $userExist;
                if ($existArr['channel'] == 'CKR') {
                    // TODO: Replace with CheckrApiClient service – _updateCandidateToApi()
                    return $this->checkrUpdateCandidateStub($userdata, $existArr);
                }
                if ($existArr['channel'] == 'DIG') {
                    // TODO: Replace with DigisureApi service – _updateCandidateToApi()
                    return $this->digisureUpdateCandidateStub($userdata, $existArr, $TrustScore);
                }
            }

            if (empty($owner)) {
                $driver_checker = 'CKR';
            } else {
                $OwnerSetting = DB::table('cs_settings')
                    ->where('user_id', $owner)
                    ->select('driver_checker')
                    ->first();
                $driver_checker = $OwnerSetting->driver_checker ?? 'CKR';
            }

            $response = ['status' => false, 'message' => 'Unknown driver checker.', 'result' => []];

            if ($driver_checker == 'DIG') {
                // TODO: Replace with DigisureApi service – _addCandidateToApi()
                $response = $this->digisureAddCandidateStub($userdata, $TrustScore);
            }
            if ($driver_checker == 'CKR') {
                // TODO: Replace with CheckrApiClient service – _addCandidateToApi()
                $response = $this->checkrAddCandidateStub($userdata);
            }

            if (!$response['status']) {
                DB::table('users')->where('id', $userid)->update(['checkr_status' => 4]);
                return $response;
            }

            return $response;
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage(), 'result' => []];
        }
    }

    /**
     * Update existing candidate in background report.
     */
    public function updateCandidateToDriverBackgroundReport($userid, $owner = ''): array
    {
        $userExist = DB::table('user_reports')->where('user_id', $userid)->first();

        if (empty($userExist)) {
            return $this->addCandidateToDriverBackgroundReport($userid, $owner, true);
        }
        $existArr = (array) $userExist;

        $User = DB::table('users')
            ->leftJoin('user_license_details as LicenseDetail', 'LicenseDetail.user_id', '=', 'users.id')
            ->where('users.id', $userid)
            ->select(
                'users.*',
                'LicenseDetail.givenName', 'LicenseDetail.lastName', 'LicenseDetail.dateOfBirth',
                'LicenseDetail.dateOfExpiry', 'LicenseDetail.addressCity', 'LicenseDetail.addressState',
                'LicenseDetail.addressPostalCode', 'LicenseDetail.documentNumber'
            )
            ->first();

        $this->_userObj = (array) $User;
        $userArr = (array) $User;

        $decryptedDocNumber = $this->decryptSecurityValue($userArr['documentNumber'] ?? '');
        if (empty($userArr['documentNumber']) || empty($decryptedDocNumber)) {
            return ['status' => false, 'message' => "Sorry, driver didnt add his license number yet.", 'result' => []];
        }

        $userdata = [
            'first_name'      => $userArr['first_name'],
            'last_name'       => $userArr['last_name'],
            'id'              => $userArr['id'],
            'email'           => $userArr['email'],
            'contact_number'  => $userArr['contact_number'],
            'zip'             => substr($userArr['zip'] ?? '', 0, 8),
            'dob'             => !empty($userArr['dateOfBirth']) ? $userArr['dateOfBirth'] : $userArr['dob'],
            'licence_number'  => $decryptedDocNumber,
            'licence_exp_date' => !empty($userArr['dateOfExpiry']) ? $userArr['dateOfExpiry'] : ($userArr['licence_exp_date'] ?? ''),
            'licence_state'   => !empty($userArr['addressState']) ? $userArr['addressState'] : ($userArr['state'] ?? ''),
            'address'         => $userArr['address'] ?? '',
            'city'            => $userArr['city'] ?? '',
            'state'           => $userArr['state'] ?? '',
        ];

        try {
            if ($existArr['channel'] == 'CKR') {
                // TODO: Replace with CheckrApiClient service – _updateCandidateToApi()
                return $this->checkrUpdateCandidateStub($userdata, $existArr);
            }
            if ($existArr['channel'] == 'DIG') {
                // TODO: Replace with DigisureApi service – _updateCandidateToApi()
                return $this->digisureUpdateCandidateStub($userdata, $existArr, true);
            }
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage(), 'result' => []];
        }

        return ['status' => false, 'message' => 'Unknown channel.', 'result' => []];
    }

    /**
     * Create a background report for a candidate.
     */
    public function createBackgroundReport($userid, $owner = ''): array
    {
        $userExist = DB::table('user_reports')->where('user_id', $userid)->first();

        if (!empty($userExist) && ((array) $userExist)['channel'] == 'DIG') {
            return $this->addCandidateToDriverBackgroundReport($userid, $owner, true);
        }

        if (empty($userExist)) {
            $this->addCandidateToDriverBackgroundReport($userid, $owner, true);
            $userExist = DB::table('user_reports')->where('user_id', $userid)->first();
        }

        if (!empty($userExist) && ((array) $userExist)['channel'] == 'CKR') {
            $existArr = (array) $userExist;

            if (empty($this->_userObj)) {
                $userRow = DB::table('users')->where('id', $userid)->first();
                $this->_userObj = $userRow ? (array) $userRow : [];
                $decrypted = $this->decryptSecurityValue($this->_userObj['licence_number'] ?? '');
                if (empty($this->_userObj['licence_number']) || empty($decrypted)) {
                    return ['status' => false, 'message' => "Sorry, driver didnt add his license number yet.", 'result' => []];
                }
            }

            $worklocation = [
                'licence_state' => !empty($this->_userObj['licence_state']) ? $this->_userObj['licence_state'] : ($this->_userObj['state'] ?? ''),
                'address'       => $this->_userObj['address'] ?? '',
                'city'          => $this->_userObj['city'] ?? '',
                'state'         => $this->_userObj['state'] ?? '',
            ];

            // TODO: Replace with CheckrApiClient service – createReport()
            $response = $this->checkrCreateReportStub($existArr['checkr_id'], $worklocation);

            if (!$response['status']) {
                return $response;
            }

            DB::table('user_reports')->where('id', $existArr['id'])->update([
                'status'          => 1,
                'checkr_reportid' => $response['report_id'],
            ]);

            return $response;
        }

        return ['status' => false, 'message' => 'Unable to create background report.', 'result' => []];
    }

    /**
     * Pull background report results for a user.
     */
    public function pullBackgroundReport($userid): array
    {
        $UserReport = DB::table('user_reports')->where('user_id', $userid)->first();

        if (empty($UserReport) || empty(((array) $UserReport)['checkr_id'])) {
            return ['status' => false, 'message' => 'Sorry, Driver background report is not requested yet or Driver not added yet to background checker.'];
        }

        $reportArr = (array) $UserReport;

        if ($reportArr['channel'] == 'CKR' && empty($reportArr['checkr_reportid'])) {
            return ['status' => false, 'message' => 'Sorry, Driver background report is not requested yet or Driver not added yet to background checker.'];
        }

        if ($reportArr['channel'] == 'CKR' && !empty($reportArr['checkr_reportid'])) {
            // TODO: Replace with CheckrApiClient service – getReport()
            return $this->checkrGetReportStub($reportArr['checkr_reportid']);
        }

        if ($reportArr['channel'] == 'DIG') {
            // TODO: Replace with DigisureApi service – pullDriverFromDigisure()
            return $this->digisurePullDriverStub($reportArr['checkr_id']);
        }

        return ['status' => false, 'message' => 'Sorry, Driver background report is not requested yet or Driver not added yet to background checker.'];
    }

    // ------------------------------------------------------------------
    // Stub methods – replace with actual service calls
    // ------------------------------------------------------------------

    /** TODO: Implement actual decryption matching legacy Security::decrypt */
    private function decryptSecurityValue(?string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }
        // TODO: Replace with Crypt::decryptString() or legacy-compatible decryption
        return $encrypted;
    }

    /** TODO: Replace with CheckrApiClient service */
    private function checkrAddCandidateStub(array $userdata): array
    {
        return ['status' => false, 'message' => 'CheckrApi service not yet connected.', 'result' => []];
    }

    /** TODO: Replace with CheckrApiClient service */
    private function checkrUpdateCandidateStub(array $userdata, array $existingReport): array
    {
        return ['status' => false, 'message' => 'CheckrApi service not yet connected.', 'result' => []];
    }

    /** TODO: Replace with CheckrApiClient service */
    private function checkrCreateReportStub(string $checkrId, array $worklocation): array
    {
        return ['status' => false, 'message' => 'CheckrApi service not yet connected.', 'result' => [], 'report_id' => ''];
    }

    /** TODO: Replace with CheckrApiClient service */
    private function checkrGetReportStub(string $reportId): array
    {
        return ['status' => false, 'message' => 'CheckrApi service not yet connected.', 'result' => []];
    }

    /** TODO: Replace with DigisureApi service */
    private function digisureAddCandidateStub(array $userdata, bool $TrustScore): array
    {
        return ['status' => false, 'message' => 'DigisureApi service not yet connected.', 'result' => []];
    }

    /** TODO: Replace with DigisureApi service */
    private function digisureUpdateCandidateStub(array $userdata, array $existingReport, bool $TrustScore): array
    {
        return ['status' => false, 'message' => 'DigisureApi service not yet connected.', 'result' => []];
    }

    /** TODO: Replace with DigisureApi service */
    private function digisurePullDriverStub(string $checkrId): array
    {
        return ['status' => false, 'message' => 'DigisureApi service not yet connected.', 'result' => []];
    }
}
