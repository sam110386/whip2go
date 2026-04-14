<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\UserLicenseDetail;
use App\Models\Legacy\AdminUserAssociation;
use App\Helpers\Legacy\Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Exception;


trait UsersTrait
{
    use CommonTrait, MobileApi, DriverBackgroundReport;

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
                $isDealer = (isset($data['is_dealer']) && $data['is_dealer'] == 1);

                $rules = [
                    'User.first_name' => 'required|max:50',
                    'User.last_name' => 'required|max:50',
                    'User.email' => [
                        'required',
                        'email',
                        Rule::unique('users', 'email')->ignore($id)
                    ],
                    'User.address' => 'required|max:150',
                    'User.city' => 'required|max:50',
                    'User.state' => 'required|max:2',
                ];

                if ($isNew) {
                    $rules['User.contact_number'] = 'required|numeric|digits:10|unique:users,contact_number';
                    $rules['User.pwd'] = 'required|min:6';
                }

                if ($isDealer) {
                    $rules += [
                        'User.company_address' => 'required|max:150',
                        'User.company_city' => 'required|max:50',
                        'User.company_state' => 'required|max:2',
                        'User.company_zip' => 'required|max:10',
                        'User.company_country' => 'required|max:20',
                    ];
                } else {
                    $rules += [
                        'UserLicenseDetail.givenName' => 'required|max:50',
                        'UserLicenseDetail.lastName' => 'required|max:50',
                        'UserLicenseDetail.addressStreet' => 'required|max:150',
                        'UserLicenseDetail.addressCity' => 'required|max:50',
                        'UserLicenseDetail.addressState' => 'required|max:2',
                    ];
                }

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                if ($isNew) {
                    $user = new User();
                    $data['username'] = preg_replace("/[^0-9]/", "", $data['contact_number']);
                    $data['is_verified'] = 1;
                    $data['is_driver'] = 0;
                    $data['status'] = 1;
                    $data['created'] = Carbon::now()->toDateTimeString();
                } else {
                    $user = User::findOrFail($id);
                    $data['modified'] = Carbon::now()->toDateTimeString();
                }

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
                    $data['password'] = Security::hash($data['pwd'], null, true);
                    unset($data['pwd']);
                } elseif (isset($data['pwd'])) {
                    unset($data['pwd']);
                }

                // Generate Geocoding Coordinates
                if (!empty($data['address']) && !empty($data['city']) && !empty($data['state'])) {
                    $addressString = $data['address'] . ', ' . $data['city'] . ', ' . $data['state'] . (!empty($data['zip']) ? ' ' . $data['zip'] : '');
                    $coords = $this->toCoordinates($addressString);
                    $data['address_lat'] = $coords['lat'];
                    $data['address_lng'] = $coords['lng'];
                }

                $user->fill($data);
                $user->save();

                $id = $user->id;

                if ($request->hasFile('User.photo')) {
                    $file = $request->file('User.photo');
                    $filename = 'user_pic_' . $id . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('img/user_pic'), $filename);
                    $user->update(['photo' => $filename]);
                }

                if ($request->hasFile('tmp_doc_1') || $request->hasFile('tmp_doc_2') || $request->hasFile('representative_sign')) {
                    if (!file_exists(public_path('files/userdocs'))) {
                        mkdir(public_path('files/userdocs'), 0777, true);
                    }
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

                if (!$user->is_dealer) {
                    $licenseData = $request->input('UserLicenseDetail', []);
                    if (!empty($licenseData)) {
                        $licenseData['user_id'] = $id;
                        $licenseData['dateOfExpiry'] = $data['licence_exp_date'] ?? null;
                        $licenseData['documentNumber'] = $data['licence_number'] ?? null;

                        // Handle fields that don't have default values in DB
                        $licenseData['jurisdictionRestrictionCodes'] = $licenseData['jurisdictionRestrictionCodes'] ?? '';
                        $licenseData['jurisdictionEndorsementCodes'] = $licenseData['jurisdictionEndorsementCodes'] ?? '';
                        $licenseData['sex'] = $licenseData['sex'] ?? 'M'; // Default to 'M' if missing
                        $licenseData['eyeColor'] = $licenseData['eyeColor'] ?? '';
                        $licenseData['height'] = $licenseData['height'] ?? '';
                        $licenseData['documentDiscriminator'] = $licenseData['documentDiscriminator'] ?? '';
                        $licenseData['issuer'] = $licenseData['issuer'] ?? '';

                        $detail = UserLicenseDetail::where('user_id', $id)->first();
                        if ($detail) {
                            $licenseData['modified'] = Carbon::now()->toDateTimeString();
                            $detail->update($licenseData);
                        } else {
                            $licenseData['created'] = Carbon::now()->toDateTimeString();
                            $licenseData['modified'] = Carbon::now()->toDateTimeString();
                            UserLicenseDetail::create($licenseData);
                        }
                    }
                }

                if ($isNew) {
                    AdminUserAssociation::saveLeadAssociation($user->username, $id);
                }

                if ($request->has('updatelicense') && $request->input('updatelicense') == 1) {
                    $repResponse = $this->updateCandidateToDriverBackgroundReport($id);
                    if ($repResponse['status']) {
                        $user->update(['checkr_status' => 2]);
                    } else {
                        $user->update(['checkr_status' => 4]);
                    }
                }

                return ['status' => true, 'message' => "User " . ($isNew ? "added" : "updated") . " successfully"];
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
