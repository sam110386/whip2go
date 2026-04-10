<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\UsersTrait;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use App\Helpers\Legacy\Security;
use App\Helpers\Legacy\Number;

class UsersController extends LegacyAppController
{
    use UsersTrait;

    public function admin_index(Request $request)
    {
        $adminUser = $this->getAdminUserid();

        if (!$adminUser['administrator']) {
            session()->flash('error', 'Sorry, you are not authorized user for this action');
            return redirect('admin/linked_users/index');
        }

        $cookies = json_decode(Cookie::get('user_list_search', '[]'), true) ?: [];

        if ($request->has('ClearFilter')) {
            Cookie::queue(Cookie::forget('user_list_search'));
            $cookies = [];
            if (!$request->ajax()) {
                return redirect('admin/users/index');
            }
            $request->merge(['keyword' => '', 'show' => '', 'type' => '']);
        }

        $keyword = $request->input('keyword') ?: ($cookies['keyword'] ?? '');
        $show = $request->input('show') ?: ($cookies['show'] ?? '');
        $type = $request->input('type') ?: ($cookies['type'] ?? '');

        $request->merge([
            'keyword' => $keyword,
            'show' => $show,
            'type' => $type
        ]);

        if (!$request->ajax()) {
            Cookie::queue('user_list_search', json_encode(['keyword' => $keyword, 'show' => $show, 'type' => $type]), 1440);
        }

        $query = $this->_getUsersQuery($request);

        $sess_limit_name = "Users_limit";
        $sess_limit_value = Session::get($sess_limit_name);

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            Session::put($sess_limit_name, $limit);
        } elseif (!empty($sess_limit_value)) {
            $limit = $sess_limit_value;
        } else {
            $limit = 50;
        }

        $users = $query->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.elements.users.admin_index', compact('users', 'keyword', 'show', 'type', 'limit'));
        }

        return view('admin.users.admin_index', compact('users', 'keyword', 'show', 'type', 'limit'));
    }

    public function admin_add(Request $request, $id = null)
    {
        $id = base64_decode($id);
        $listTitle = !empty($id) ? 'Update User' : 'Add User';
        $user = null;

        if ($request->isMethod('post')) {

            $result = $this->_saveUser($request, $id);

            Session::flash($result['status'] ? 'success' : 'error', $result['message']);

            if ($result['status']) {
                return redirect('admin/users/index');
            }
        }

        if (!empty($id) && $request->isMethod('get')) {
            $user = User::with('userLicenseDetail')->findOrFail($id);

            if (!empty($user->userLicenseDetail->documentNumber)) {
                $user->userLicenseDetail->documentNumber = Security::decrypt($user->userLicenseDetail->documentNumber);
            }

            if (!empty($user->licence_number)) {
                $user->licence_number = Security::decrypt($user->licence_number);
            }
        }

        $currencies = Number::getCurrencies();

        return view('admin.users.admin_add', compact('user', 'listTitle', 'currencies', 'id'));
    }

    public function admin_view($id)
    {
        $listTitle = 'View User';
        $id = base64_decode($id);
        $user = User::findOrFail($id);
        return view('admin.users.admin_view', compact('user', 'listTitle'));
    }




    public function admin_status($id, $status)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'status', $status);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }

    /**
     * admin_verify: Toggle user verification status
     */
    public function admin_verify($id)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'is_verified', 1);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }


    /**
     * admin_edit: Profile edit screen and save
     */
    public function admin_edit(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession())
            return $redirect;

        $id = base64_decode($id);
        if ($request->isMethod('post')) {
            $result = $this->_saveUser($request, $id);
            Session::flash($result['status'] ? 'success' : 'error', $result['message']);
            if ($result['status'])
                return redirect()->route('admin.users.index');
        }

        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * admin_trash: Soft delete/Trash a user
     */
    public function admin_trash($id, $status = null)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'trash', $status);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }


    public function admin_address_proof_popup(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_bankdetails(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_change_phone(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_checkr_status(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_checkrreport(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_dealer_approve($id, $status = null)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'is_dealer', $status);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }

    public function admin_driverstatus($id, $status = null)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'is_driver', $status);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }
    public function admin_getDriverLicense(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_getmystripeurl(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_getstripeloginurl(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_loadPayoutSchedule(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_revsetting(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_saveaddressproof(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_showargyldetails(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
    public function admin_updatePayoutSchedule(Request $request)
    {
        return $this->pendingResponse(__FUNCTION__);
    }
}
