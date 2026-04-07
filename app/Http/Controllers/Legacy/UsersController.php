<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\UsersTrait;
use App\Models\Legacy\User;
use App\Models\Legacy\UserLicenseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Security;

class UsersController extends LegacyAppController
{
    use UsersTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * my_account: Main user profile view
     */
    public function my_account(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userid = Session::get('userParentId') ?: Session::get('userid');
        $driver = User::with(['UserLicenses'])->find($userid);

        return view('legacy.users.my_account', compact('driver'));
    }

    /**
     * update_profile: Profile edit screen and save
     */
    public function update_profile(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userid = Session::get('userParentId') ?: Session::get('userid');
        if ($request->isMethod('post')) {
            $result = $this->_saveUser($request, $userid);
            Session::flash($result['status'] ? 'success' : 'error', $result['message']);
            if ($result['status']) return redirect()->route('legacy.users.my_account');
        }

        $user = User::findOrFail($userid);
        $timezones = $this->get_time_zone();
        
        return view('legacy.users.update_profile', compact('user', 'timezones'));
    }

    /**
     * changePassword: Password update screen and save
     */
    public function changePassword(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userid = Session::get('userParentId') ?: Session::get('userid');
        if ($request->isMethod('post')) {
            $oldPassword = $request->input('User.oldPassword');
            $newPassword = $request->input('User.newpassword');
            $confirmPassword = $request->input('User.confirmpassword');

            $user = User::findOrFail($userid);

            if (Hash::check($oldPassword, $user->password)) {
                if ($newPassword === $confirmPassword) {
                    $user->update(['password' => Hash::make($newPassword)]);
                    Session::flash('success', 'Password updated successfully');
                    return redirect()->route('legacy.users.my_account');
                } else {
                    Session::flash('error', 'New password and confirm password do not match');
                }
            } else {
                Session::flash('error', 'Invalid old password');
            }
        }

        return view('legacy.users.change_password');
    }

    /**
     * index: List of staff/linked users for a dealer
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userid = Session::get('userParentId') ?: Session::get('userid');
        $query = $this->_getUsersQuery($request, ['dealer_id' => $userid]);

        $users = $query->orderBy('id', 'DESC')->paginate(25)->withQueryString();

        return view('legacy.users.index', compact('users'));
    }

    /**
     * status: Toggle staff status
     */
    public function status($id, $status)
    {
        if ($redirect = $this->ensureUserSession()) return response()->json(['error' => 'Unauthorized'], 403);
        
        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'status', $status);
        
        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }
}
