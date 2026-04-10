<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\UserLicenseDetail;
use App\Models\Legacy\AdminUserAssociation;
use App\Helpers\Legacy\Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;


trait UsersTrait
{
    use CommonTrait, MobileApi;

    protected function _getUsersQuery(Request $request)
    {
        $query = User::query()->where('is_admin', 0);

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")
                    ->orWhere('last_name', 'LIKE', "%$keyword%")
                    ->orWhere('email', 'LIKE', "%$keyword%")
                    ->orWhere('username', 'LIKE', "%$keyword%")
                    ->orWhere('business_name', 'LIKE', "%$keyword%");
            });
        }

        if ($request->filled('show')) {
            $show = $request->input('show');
            switch ($show) {
                case 'Active':
                    $query->where('status', 1);
                    break;
                case 'Deactive':
                    $query->where('status', 0);
                    break;
            }
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            switch ($type) {
                case 1:
                    $query->where('is_verified', 1);
                    break;
                case 2:
                    $query->where('is_verified', 0);
                    break;
                case 3:
                    $query->where('is_renter', 1);
                    break;
                case 4:
                    $query->where('is_driver', 1);
                    break;
                case 5:
                    $query->where('is_dealer', 1);
                    break;
                case 6:
                    $query->where('is_dealer', 2);
                    break;
            }
        }

        $sort = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'desc')) == 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'id',
            'first_name',
            'last_name',
            'email',
            'contact_number',
            'created',
            'status',
            'is_verified',
            'is_renter',
            'is_driver',
            'is_dealer',
            'checkr_status',
            'trash'
        ];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }


    protected function _saveUser(Request $request, $id = null)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $data = $request->input('User');
                $isNew = empty($id);

                if ($isNew) {
                    $user = new User();
                    $data['username'] = preg_replace("/[^0-9]/", "", $data['contact_number']);
                    $data['is_verified'] = 1;
                    $data['is_driver'] = 1;
                    $data['status'] = 1;
                    // For admin_add, the dealer_id should probably be the logged-in admin's ID if applicable,
                    // but in legacy it was the session userParentId.
                } else {
                    $user = User::findOrFail($id);
                }

                // Sanitize and Format
                $data['first_name'] = ucwords(strtolower($data['first_name'] ?? ''));
                $data['last_name'] = ucwords(strtolower($data['last_name'] ?? ''));

                if (!empty($data['licence_number'])) {
                    $data['is_driver'] = 1;
                    $data['licence_number'] = Security::encrypt($data['licence_number']);
                }

                if (!empty($data['ss_no'])) {
                    $data['ss_no'] = Security::encrypt($data['ss_no']);
                }

                if (!empty($data['pwd'])) {
                    // Cake used a specific salt/hash
                    $data['password'] = Security::hash($data['pwd'], null, true);
                    unset($data['pwd']);
                } elseif (isset($data['pwd'])) {
                    unset($data['pwd']);
                }

                // Address Coordinates (Dummy implementation for now if geocoder not available)
                // $address = ($data['address'] ?? '') . ' ' . ($data['city'] ?? '') . ' ' . ($data['state'] ?? '') . ' ' . ($data['zip'] ?? '');
                // $latlng = $this->toCoordinates($address);

                $user->fill($data);
                $user->save();

                $id = $user->id;

                // Handle File Uploads
                if ($request->hasFile('User.photo')) {
                    $file = $request->file('User.photo');
                    $filename = 'user_pic_' . $id . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('img/user_pic'), $filename);
                    $user->update(['photo' => $filename]);
                }

                if ($request->hasFile('tmp_doc_1')) {
                    $file = $request->file('tmp_doc_1');
                    $filename = 'license_doc_1_' . $id . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('files/userdocs'), $filename);
                    $user->update(['license_doc_1' => $filename]);
                }

                if ($request->hasFile('tmp_doc_2')) {
                    $file = $request->file('tmp_doc_2');
                    $filename = 'license_doc_2_' . $id . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('files/userdocs'), $filename);
                    $user->update(['license_doc_2' => $filename]);
                }

                if ($request->hasFile('representative_sign')) {
                    $file = $request->file('representative_sign');
                    $filename = 'representative_sign_' . $id . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('files/userdocs'), $filename);
                    $user->update(['representative_sign' => $filename]);
                }

                // Handle UserLicenseDetail
                if (!$user->is_dealer) {
                    $licenseData = $request->input('UserLicenseDetail', []);
                    if (!empty($licenseData)) {
                        $licenseData['user_id'] = $id;
                        $licenseData['dateOfExpiry'] = $data['licence_exp_date'] ?? null;
                        $licenseData['documentNumber'] = $data['licence_number'] ?? null;

                        $detail = UserLicenseDetail::where('user_id', $id)->first();
                        if ($detail) {
                            $detail->update($licenseData);
                        } else {
                            UserLicenseDetail::create($licenseData);
                        }
                    }
                }

                if ($isNew) {
                    AdminUserAssociation::saveLeadAssociation($user->username, $id);
                }

                return ['status' => true, 'message' => "User " . ($isNew ? "added" : "updated") . " successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error in _saveUser: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * _toggleStatus: Toggle boolean flags for users
     */
    protected function _toggleStatus($id, $field, $value)
    {
        try {
            $user = User::findOrFail($id);
            $user->update([$field => $value]);
            return ['status' => true, 'message' => "User $field updated"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * _ccManagement: Placeholder for Stripe card logic
     */
    protected function _ccManagement($action, $data = [])
    {
        // Integration with PaymentProcessor library
        Log::info("CC Management Action: $action");
        return ['status' => true, 'message' => "Action $action completed"];
    }

    /**
     * _getRevSettings: Fetch revenue share settings
     */
    protected function _getRevSettings($userId)
    {
        $revSetting = DB::table('rev_settings')->where('user_id', $userId)->first();
        return $revSetting ?: ['rental_rev' => 85];
    }
}
