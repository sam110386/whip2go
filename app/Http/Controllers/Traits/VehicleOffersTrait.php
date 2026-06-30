<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleOffer;
use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\UserReport;
use App\Models\Legacy\CsSetting;
use App\Models\Legacy\UserIncome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

trait VehicleOffersTrait
{

    protected function _userautocomplete($params)
    {
        $users = [];
        $searchTerm = $params['term'] ?? '';
        $user_id = $params['user_id'] ?? '';

        $query = User::where('status', 1)
            ->where('is_driver', 1)
            ->where('is_admin', 0);

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('contact_number', 'LIKE', "%$searchTerm%")
                    ->orWhere('first_name', 'LIKE', "%$searchTerm%")
                    ->orWhere('email', 'LIKE', "%$searchTerm%")
                    ->orWhere('last_name', 'LIKE', "%$searchTerm%");
            });
        }

        if (!empty($user_id)) {
            $user = User::where('id', $user_id)->where('is_admin', 0)->first();
            if ($user) {
                return [
                    'id' => $user->id,
                    'tag' => $user->first_name . ' - ' . $user->contact_number
                ];
            }
        } else {
            $userlists = $query->orderBy('first_name', 'ASC')->limit(10)->get();
            foreach ($userlists as $value) {
                $users[] = [
                    'id' => $value->id,
                    'tag' => $value->first_name . ' - ' . $value->contact_number
                ];
            }
        }

        return $users;
    }
    protected function _vehicleautocomplete($params)
    {
        $vehicles = [];
        $adminUser = $this->getAdminUserId();
        $dealers = [];

        if (!$adminUser['administrator']) {
            $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])
                ->pluck('user_id')
                ->toArray();
        }

        $searchTerm = $params['term'] ?? '';
        $vehicleId = $params['vehicle_id'] ?? '';
        $dealerId = $params['dealer_id'] ?? '';

        if (!empty($vehicleId)) {
            $vehicle = Vehicle::select('id', 'vehicle_unique_id', 'vehicle_name', 'msrp', 'allowed_miles')
                ->find($vehicleId);

            if ($vehicle) {
                $vehicles['id'] = $vehicle->id;
                $vehicles['tag'] = "{$vehicle->vehicle_unique_id} - {$vehicle->vehicle_name}";
                $vehicles['msrp'] = $vehicle->msrp;
                $miles_options = [];

                while ($k <= 15000) {
                    $miles_options[$k] = $k;
                    $k += 500;
                }

                $vehicles['miles_options'] = $miles_options;
            }
        } else {
            $query = Vehicle::select('id', 'vehicle_unique_id', 'vehicle_name', 'msrp', 'allowed_miles')
                ->where('status', 1)
                ->where('booked', 0);

            if (!$adminUser['administrator']) {
                if (!empty($dealerId)) {
                    $query->where('user_id', $dealerId);
                } else {
                    $query->whereIn('user_id', $dealers);
                }
            }

            $query->where(function ($q) use ($searchTerm) {
                $q->where('vehicle_unique_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('vehicle_name', 'LIKE', "%{$searchTerm}%");
            });

            $vehicleLists = $query->orderBy('vehicle_unique_id', 'ASC')
                ->limit(10)
                ->get();

            foreach ($vehicleLists as $index => $vehicle) {
                $vehicles[$index]['id'] = $vehicle->id;
                $vehicles[$index]['tag'] = "{$vehicle->vehicle_unique_id}-{$vehicle->vehicle_name}";
                $vehicles[$index]['msrp'] = $vehicle->msrp;
                $k = $vehicle->allowed_miles ? ceil($vehicle->allowed_miles * 30) : 1000;
                $miles_options = [];

                while ($k <= 15000) {
                    $miles_options[$k] = $k;
                    $k += 500;
                }

                $vehicles[$index]['miles_options'] = $miles_options;
            }
        }

        return $vehicles;
    }
    protected function qualifyCheckr($offer)
    {
        $return = [
            "status" => false,
            "message" => "Sorry, driver phone is not registered yet."
        ];
        $phone = substr(preg_replace("/[^0-9]/", "", $offer['driver_phone'] ?? ''), -10);
        $user = User::where('username', $phone)->first();

        if (!$user) {
            return $return;
        }

        $userReport = UserReport::where('user_id', $user->id)->first();

        if (!$userReport) {
            $checkrStatus = $this->addCandidateToDriverBackgroundReport($user->id);

            if ($checkrStatus['status']) {
                return [
                    "status" => true,
                    "message" => "User is added to Checkr API for processing, please wait for few seconds for final report"
                ];
            } else {
                return [
                    "status" => false,
                    "message" => $checkrStatus['message']
                ];
            }

        } elseif ($userReport->status && !empty($userReport->checkr_reportid)) {
            return $this->pullBackgroundReport($user->id);
        } elseif (empty($userReport->checkr_reportid)) {
            $checkrReport = $this->createBackgroundReport($user->id);

            if ($checkrReport['status']) {
                return [
                    "status" => true,
                    "message" => "We requested the driver report, please wait for few seconds for final report"
                ];
            } else {
                return [
                    "status" => false,
                    "message" => $checkrReport['message']
                ];
            }
        }

        return $return;
    }
    protected function _qualifyIncome($offer)
    {
        $return = ["status" => true, "message" => ""];
        $phone = substr(preg_replace("/[^0-9]/", "", $offer['driver_phone'] ?? ''), -10);
        $user = User::where('username', $phone)->first();

        if (!$user) {
            return $return;
        }

        $incomeObj = UserIncome::where('user_id', $user->id)->first();

        if (!$incomeObj || $incomeObj->income == 0) {
            return $return;
        }

        $vehicle = Vehicle::find($offer['vehicle_id']);

        if (!$vehicle || $vehicle->msrp == 0) {
            return $return;
        }

        $APRSellingPrice = $this->commonService->calculateAPRSellingPrice($incomeObj->income);
        $monthlyRent = ($offer['day_rent'] ?? 0) * 30;

        if ($APRSellingPrice < $monthlyRent) {
            return [
                'status' => false,
                'message' => "Sorry, This user's stated income shows as too low for this vehicle. Are you sure you want to proceed?"
            ];
        }

        return $return;
    }

    protected function _duplicate($offerData)
    {
        $newOffer = $offerData->replicate();
        $newOffer->status = 0;
        $newOffer->start_datetime = Carbon::now()->format('Y-m-d') . ' ' . Carbon::parse($offerData->start_datetime)->format('H:i:s');
        $newOffer->save();

        // Notify simulation
        Log::info("Pubnub: notifyForOffer for user " . $newOffer->user_id);

        return $newOffer->id;
    }
}
