<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\StaffUser;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Security;

class StaffUsersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── index (List of dealer staff) ──────────────────────────────────────────
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $this->layout = 'main';
        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        $keyword  = $request->input('Search.keyword', $request->query('keyword', ''));
        $showType = $request->input('Search.show', $request->query('showtype', ''));

        $this->set('title_for_layout', 'Manage Staff Users');

        $query = StaffUser::query()
            ->where('staff_parent', $userId)
            ->where('is_staff', 1);

        if (!empty($keyword)) {
            $v = strip_tags($keyword);
            $query->where(fn($q) => $q->where('first_name', 'LIKE', "%$v%")
                ->orWhere('last_name', 'LIKE', "%$v%")
                ->orWhere('email', 'LIKE', "%$v%"));
        }

        if ($showType !== '') {
            $matchShow = ($showType == 'Active') ? 1 : 0;
            $query->where('status', $matchShow);
        }

        $sessionLimitKey  = 'StaffUsers_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $subusers = $query->orderBy('id', 'DESC')->paginate($limit)->withQueryString();

        return view('legacy.staff_users.index', [
            'subusers' => $subusers,
            'keyword'  => $keyword,
            'show'     => $showType,
        ]);
    }

    // ─── add / edit ───────────────────────────────────────────────────────────
    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $id = $id ? base64_decode($id) : null;
        $userId = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        
        $listTitle = $id ? 'Update Staff User' : 'Add Staff User';

        if ($request->isMethod('post')) {
            $data = $request->input('StaffUser', []);
            
            if (empty($id)) {
                // Formatting for new user
                $data['username'] = preg_replace("/[^0-9]/", "", $data['contact_number'] ?? '');
                $data['status'] = 1;
                $data['is_staff'] = 1;
                $data['is_verified'] = 1;
                $data['is_owner'] = 1;
                $data['is_driver'] = 0;
                $data['staff_parent'] = $userId;
                $data['dealer_id'] = $userId;
                $data['is_dealer'] = 1;
            }

            if (!empty($data['pwd'])) {
                // In legacy, Security::hash was used. In Laravel, we use Hash::make
                $data['password'] = Hash::make($data['pwd']);
                unset($data['pwd']);
            }

            // Image handling (legacy attachment behavior simulated)
            if ($request->hasFile('StaffUser.photo')) {
                // $path = $request->file('StaffUser.photo')->store('staff_photos');
                // $data['photo'] = basename($path);
            } else {
                unset($data['photo']);
            }

            if ($id) {
                StaffUser::where('id', $id)->update($data);
                $msg = 'User has been updated successfully.';
            } else {
                StaffUser::create($data);
                $msg = 'User has been added successfully.';
            }

            return redirect('/staff_users/index')->with('success', $msg);
        }

        $record = $id ? StaffUser::find($id) : null;
        view()->share('data', $record);

        return view('legacy.staff_users.add', compact('listTitle', 'id', 'record'));
    }

    // ─── status (Toggle activation) ──────────────────────────────────────────
    public function status(Request $request, $id = null, $status = null)
    {
        $id = base64_decode($id);
        if (!empty($id)) {
            StaffUser::where('id', $id)->update(['status' => ($status == 1 ? 1 : 0)]);
        }

        return redirect()->back()->with('success', 'Staff user status has been changed.');
    }

    // ─── view ─────────────────────────────────────────────────────────────────
    public function view(Request $request, $id)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $id = base64_decode($id);
        $record = StaffUser::find($id);

        return view('legacy.staff_users.view', compact('record'));
    }

    // ─── delete ───────────────────────────────────────────────────────────────
    public function delete(Request $request, $id = null)
    {
        $id = base64_decode($id);
        if (!empty($id)) {
            StaffUser::where('id', $id)->delete();
        }

        return redirect('/staff_users/index')->with('success', 'Staff user has been deleted successfully.');
    }
}
