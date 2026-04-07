<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\UsersTrait;
use App\Models\Legacy\User;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class LinkedUsersController extends LegacyAppController
{
    use UsersTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * cloud_index: List users/drivers for linked dealers
     */
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $adminUser = Session::get('SESSION_ADMIN');
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();

        $query = $this->_getUsersQuery($request, ['dealer_id' => $dealers]);

        $users = $query->orderBy('id', 'DESC')->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return view('cloud.elements.linkedusers.index', compact('users'));
        }

        return view('cloud.linked_users.index', compact('users'));
    }

    /**
     * cloud_edit: Profile edit screen and save for linked users
     */
    public function cloud_edit(Request $request, $id)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $id = base64_decode($id);
        $user = User::findOrFail($id);
        
        // Ensure user belongs to the cloud user's dealers
        $adminUser = Session::get('SESSION_ADMIN');
        $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();
        if (!in_array($user->dealer_id, $dealers)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($request->isMethod('post')) {
            $result = $this->_saveUser($request, $id);
            Session::flash($result['status'] ? 'success' : 'error', $result['message']);
            if ($result['status']) return redirect()->route('cloud.linked_users.index');
        }

        return view('cloud.linked_users.edit', compact('user'));
    }

    /**
     * cloud_ccindex: List credit cards for a linked user
     */
    public function cloud_ccindex($userid)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;
        
        $id = base64_decode($userid);
        $user = User::findOrFail($id);
        
        // Integration with CC list logic
        return view('cloud.linked_users.ccindex', compact('user'));
    }

    /**
     * cloud_dynamicfares: Manage dynamic fare settings for a linked user
     */
    public function cloud_dynamicfares(Request $request, $userId)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;
        
        $id = base64_decode($userId);
        $user = User::findOrFail($id);
        
        // Dynamic fare logic placeholder
        return view('cloud.linked_users.dynamicfares', compact('user'));
    }
}
