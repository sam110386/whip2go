<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\UsersTrait;
use App\Models\Legacy\User;
use App\Models\Legacy\UserLicenseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class UsersController extends LegacyAppController
{
    use UsersTrait;

    protected bool $shouldLoadLegacyModules = true;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "AdminUsers::{$action} is pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * admin_index: Main admin user listing
     */
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $extraConditions = [];
        if ($request->filled('Search.is_driver')) {
            $extraConditions['is_driver'] = $request->input('Search.is_driver');
        }
        if ($request->filled('Search.is_owner')) {
            $extraConditions['is_owner'] = $request->input('Search.is_owner');
        }

        $query = $this->_getUsersQuery($request, $extraConditions);

        $limit = $request->input('Record.limit') ?: Session::get('users_limit', 50);
        if ($request->has('Record.limit')) Session::put('users_limit', $limit);

        $users = $query->orderBy('id', 'DESC')->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.elements.users.index', compact('users'));
        }

        return view('admin.users.index', compact('users'));
    }

    /**
     * admin_status: Toggle user status
     */
    public function admin_status($id, $status)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'status', $status);
        
        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }

    /**
     * admin_verify: Toggle user verification status
     */
    public function admin_verify($id, $status)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'is_verified', $status);
        
        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }

    /**
     * admin_view: Detailed user view
     */
    public function admin_view($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        $user = User::with(['UserLicenses'])->findOrFail($id);
        $revSetting = $this->_getRevSettings($id);

        return view('admin.users.view', compact('user', 'revSetting'));
    }

    /**
     * admin_edit: Profile edit screen and save
     */
    public function admin_edit(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        if ($request->isMethod('post')) {
            $result = $this->_saveUser($request, $id);
            Session::flash($result['status'] ? 'success' : 'error', $result['message']);
            if ($result['status']) return redirect()->route('admin.users.index');
        }

        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * admin_trash: Soft delete/Trash a user
     */
    public function admin_trash($id)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'is_trash', 1);
        
        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return redirect()->route('admin.users.index');
    }

    public function admin_add(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        if ($request->isMethod('post')) {
            $result = $this->_saveUser($request, $request->input('User.id'));
            return response()->json($result);
        }
        return $this->pendingResponse(__FUNCTION__);
    }

    public function admin_address_proof_popup(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_bankdetails(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_change_phone(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_checkr_status(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_checkrreport(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_dealer_approve(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_delete(Request $request, $id = null) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_driverstatus(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getDriverLicense(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getmystripeurl(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_getstripeloginurl(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_loadPayoutSchedule(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_revsetting(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_saveaddressproof(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_showargyldetails(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function admin_updatePayoutSchedule(Request $request) { return $this->pendingResponse(__FUNCTION__); }
}
