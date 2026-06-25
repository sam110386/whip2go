<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\UserLicenseDetail;
use App\Models\Legacy\UserReport;
use App\Models\Legacy\CsSetting;
use App\Helpers\Legacy\Security;
use Illuminate\Support\Facades\Log;
use App\Services\Legacy\CheckrApiClient;

trait DriverBackgroundReport {

    private $_userObj = [];

    public function addCandidateToDriverBackgroundReport($userid, $owner = '', $TrustScore = false)
    {
        $this->_userObj = User::where('id', $userid)
            ->with('UserLicenseDetail')
            ->first();

        if (!$this->_userObj) {
            return ['status' => false, 'message' => "User not found."];
        }

        $licenseDetail = $this->_userObj->UserLicenseDetail;
        
        $documentNumber = !empty($licenseDetail->documentNumber) ? $licenseDetail->documentNumber : null;
        if (!$documentNumber) {
            return ['status' => false, 'message' => "Sorry, driver didn't add his license number yet."];
        }

        try {
            $decryptedLicense = Security::decrypt($documentNumber);
        } catch (\Exception $e) {
            $decryptedLicense = $documentNumber; // Fallback or handle error
        }

        $userdata = [
            'first_name' => $this->_userObj->first_name,
            'last_name' => $this->_userObj->last_name,
            'id' => $this->_userObj->id,
            'email' => $this->_userObj->email,
            'contact_number' => $this->_userObj->contact_number,
            'zip' => substr($this->_userObj->zip, 0, 8),
            'dob' => !empty($licenseDetail->dateOfBirth) ? $licenseDetail->dateOfBirth : $this->_userObj->dob,
            'licence_number' => $decryptedLicense,
            'licence_exp_date' => !empty($licenseDetail->dateOfExpiry) ? $licenseDetail->dateOfExpiry : $this->_userObj->licence_exp_date,
            'licence_state' => !empty($licenseDetail->addressState) ? $licenseDetail->addressState : $this->_userObj->state,
            'address' => $this->_userObj->address,
            'city' => $this->_userObj->city,
            'state' => $this->_userObj->state
        ];

        $userExist = UserReport::where('user_id', $userid)->first();

        try {
            if (!empty($userExist)) {
                if ($userExist->channel == 'CKR') {
                    $checkr = new CheckrApiClient();
                    return $checkr->updateCandidateToApi($userdata, $userExist->checkr_id);
                }
                if ($userExist->channel == 'DIG') {
                    // Placeholder for DigisureApi
                    Log::info("DigisureApi: _updateCandidateToApi for user $userid");
                    return ['status' => true, 'message' => "Digisure candidate update pending Lib migration."];
                }
            }

            if (empty($owner)) {
                $driver_checker = 'CKR';
            } else {
                $ownerSetting = CsSetting::where('user_id', $owner)->first();
                $driver_checker = $ownerSetting ? $ownerSetting->driver_checker : 'CKR';
            }

            $response = ['status' => false, 'message' => "No checker found."];

            if ($driver_checker == 'DIG') {
                // Placeholder for DigisureApi
                Log::info("DigisureApi: _addCandidateToApi for user $userid");
                $response = ['status' => true, 'message' => "Digisure candidate add pending Lib migration."];
            }
            if ($driver_checker == 'CKR') {
                $checkr = new CheckrApiClient();
                $response = $checkr->addCandidateAndSave($userdata);
            }

            if (!$response['status']) {
                User::where('id', $userid)->update(['checkr_status' => 4]);
            }
            return $response;

        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateCandidateToDriverBackgroundReport($userid, $owner = '')
    {
        $userExist = UserReport::where('user_id', $userid)->first();
        if (empty($userExist)) {
            return $this->addCandidateToDriverBackgroundReport($userid, $owner, true);
        }

        $user = User::where('id', $userid)->with('UserLicenseDetail')->first();
        if (!$user) {
            return ['status' => false, 'message' => "User not found."];
        }

        $licenseDetail = $user->UserLicenseDetail;
        $documentNumber = !empty($licenseDetail->documentNumber) ? $licenseDetail->documentNumber : null;
        if (!$documentNumber) {
            return ['status' => false, 'message' => "Sorry, driver didn't add his license number yet."];
        }

        try {
            $decryptedLicense = Security::decrypt($documentNumber);
        } catch (\Exception $e) {
            $decryptedLicense = $documentNumber;
        }

        $userdata = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'id' => $user->id,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'zip' => substr($user->zip, 0, 8),
            'dob' => !empty($licenseDetail->dateOfBirth) ? $licenseDetail->dateOfBirth : $user->dob,
            'licence_number' => $decryptedLicense,
            'licence_exp_date' => !empty($licenseDetail->dateOfExpiry) ? $licenseDetail->dateOfExpiry : $user->licence_exp_date,
            'licence_state' => !empty($licenseDetail->addressState) ? $licenseDetail->addressState : $user->state,
            'address' => $user->address,
            'city' => $user->city,
            'state' => $user->state
        ];

        try {
            if ($userExist->channel == 'CKR') {
                // Placeholder
                Log::info("CheckrApi: _updateCandidateToApi for user $userid");
                return ['status' => true, 'message' => "Checkr update pending Lib migration."];
            }
            if ($userExist->channel == 'DIG') {
                // Placeholder
                Log::info("DigisureApi: _updateCandidateToApi for user $userid");
                return ['status' => true, 'message' => "Digisure update pending Lib migration."];
            }
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }

        return ['status' => false, 'message' => "Unknown channel."];
    }

    public function createBackgroundReport($userid, $owner = '')
    {
        $userReport = UserReport::where('user_id', $userid)->first();

        if ($userReport && $userReport->channel == 'DIG') {
            return $this->addCandidateToDriverBackgroundReport($userid, $owner, true);
        }

        if (empty($userReport)) {
            $this->addCandidateToDriverBackgroundReport($userid, $owner, true);
            $userReport = UserReport::where('user_id', $userid)->first();
        }

        if ($userReport && $userReport->channel == 'CKR') {
            if (empty($this->_userObj)) {
                $this->_userObj = User::find($userid);
            }

            if (!$this->_userObj || empty($this->_userObj->licence_number)) {
                return ['status' => false, 'message' => "Sorry, driver didn't add his license number yet."];
            }

            $worklocation = [
                'licence_state' => !empty($this->_userObj->licence_state) ? $this->_userObj->licence_state : $this->_userObj->state,
                'address' => $this->_userObj->address,
                'city' => $this->_userObj->city,
                'state' => $this->_userObj->state
            ];

            // Actual CheckrApi->createReport call
            $checkr = new CheckrApiClient();
            $response = $checkr->createReport($userReport->checkr_id, $worklocation);
            
            if ($response['status'] && !empty($response['report_id'])) {
                $userReport->update(["status" => 1, "checkr_reportid" => $response['report_id']]);
            }
            return $response;
        }

        return ['status' => false, 'message' => "Could not create report."];
    }

    public function pullBackgroundReport($userid)
    {
        $userReportArr = UserReport::where('user_id', $userid)->first();

        if (!$userReportArr || empty($userReportArr->checkr_id)) {
            return ["status" => false, "message" => "Sorry, Driver background report is not requested yet."];
        }

        if ($userReportArr->channel == 'CKR' && empty($userReportArr->checkr_reportid)) {
            return ["status" => false, "message" => "Sorry, Driver background report is not requested yet."];
        }

        if ($userReportArr->channel == 'CKR' && !empty($userReportArr->checkr_reportid)) {
            $checkr = new CheckrApiClient();
            return $checkr->getReport($userReportArr->checkr_reportid);
        }

        if ($userReportArr->channel == 'DIG') {
            // Placeholder for DigisureApi->pullDriverFromDigisure
            Log::info("DigisureApi: pullDriverFromDigisure for user $userid");
            return ["status" => true, 'message' => 'Simulated report data for DIG'];
        }

        return ["status" => false, "message" => "Unknown background checker channel."];
    }
}
