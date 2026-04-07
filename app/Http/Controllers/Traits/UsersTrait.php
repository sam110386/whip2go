<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\UserLicenseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

trait UsersTrait {
    use CommonTrait, MobileApi;

    /**
     * _getUsersQuery: Shared filtering for user lists
     */
    protected function _getUsersQuery(Request $request, array $extraConditions = [])
    {
        $query = User::query()->where('is_admin', 0);

        if ($request->filled('Search.keyword')) {
            $keyword = $request->input('Search.keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%$keyword%")
                  ->orWhere('last_name', 'LIKE', "%$keyword%")
                  ->orWhere('email', 'LIKE', "%$keyword%")
                  ->orWhere('contact_number', 'LIKE', "%$keyword%");
            });
        }

        if ($request->filled('Search.show')) {
            $status = $request->input('Search.show') == 'Active' ? 1 : 0;
            $query->where('status', $status);
        }

        foreach ($extraConditions as $col => $val) {
            if (is_array($val)) {
                $query->whereIn($col, $val);
            } else {
                $query->where($col, $val);
            }
        }

        return $query;
    }

    /**
     * _saveUser: Common logic for user profile updates
     */
    protected function _saveUser(Request $request, $id)
    {
        try {
            return DB::transaction(function() use ($request, $id) {
                $user = User::findOrFail($id);
                $data = $request->input('User');

                // Sanitize and Format
                $data['first_name'] = ucwords(strtolower($data['first_name']));
                $data['last_name'] = ucwords(strtolower($data['last_name']));
                
                if (isset($data['password']) && !empty($data['password'])) {
                    $data['password'] = Hash::make($data['password']);
                } else {
                    unset($data['password']);
                }

                $user->update($data);

                // Handle Signature Upload
                if ($request->hasFile('representative_sign')) {
                    $file = $request->file('representative_sign');
                    $filename = 'representative_sign_' . $id . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('files/userdocs'), $filename);
                    $user->update(['representative_sign' => $filename]);
                }

                return ['status' => true, 'message' => "Profile updated successfully"];
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
