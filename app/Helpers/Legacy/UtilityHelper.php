<?php

namespace App\Helpers\Legacy;

use App\Models\Legacy\User;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\UserLicenseDetail;
use Illuminate\Http\Request;

class UtilityHelper
{

    public static function _getParam(Request $request, string $name, $def = null, int $mask = 2)
    {
        if (!$request->has($name)) {
            return $def;
        }

        $value = $request->input($name);

        if (is_string($value)) {
            // 1 represents _NOTRIM
            if (!($mask & 1)) {
                $value = trim($value);
            }
            // 2 represents _ALLOWHTML
            if (!($mask & 2)) {
                $value = strip_tags($value);
            }

        }

        return str_replace('$', '&#36;', $value);
    }

    public static function display_message($arr, $sClass = 'errMsg')
    {
        $str = '';
        $errHead = 0;

        if (is_array($arr)) {
            foreach ($arr as $key => $value) {

                if ($key == 0 && $value == 1) {
                    continue;
                }

                $errHead = 1;
                $str .= "{$value} <br />";
            }
        }

        if ($errHead) {
            $str = "<div class='{$sClass}'> {$str} </div>";
        }

        return $str;
    }

    public static function get_User($id = null)
    {
        if (!$id) {
            return [
                "first_name" => "",
                "last_name" => "",
                "email" => "",
                "contact_number" => "",
                "dob" => ""
            ];
        }

        $user = User::select(
            'first_name',
            'last_name',
            'contact_number',
            'email',
            'dob',
            'licence_state'
        )->find($id);

        return $user ? $user->toArray() : [
            "first_name" => "",
            "last_name" => "",
            "email" => "",
            "contact_number" => "",
            "dob" => ""
        ];
    }

    public static function get_UserFirstLastName($id = null)
    {
        $user = User::select('first_name', 'last_name')->find($id);

        if ($user) {
            return $user->first_name . ' ' . $user->last_name;
        }

        return "";
    }

    public static function getVehicleUniqueCode($vehicleId)
    {
        return Vehicle::where('id', $vehicleId)->value('vehicle_unique_id');
    }

    public static function getUserLicenceDetails($userId)
    {
        $details = UserLicenseDetail::where('user_id', $userId)
            ->select('dateOfBirth as dob', 'addressState as licence_state')
            ->first();

        return $details ? $details->toArray() : null;
    }

}