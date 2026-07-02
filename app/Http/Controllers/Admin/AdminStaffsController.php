<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\Legacy\Security;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminUserRole;
use App\Models\Legacy\User;

class AdminStaffsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = "Staff Users";
        $sessionLimitKey = "admin_users_limit";
        $fieldname = $request->input('Search.searchin', $request->input('searchin', ''));
        $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
        $show = $request->input('Search.show', $request->input('show', ''));
        $adminUser = $this->getAdminUserid();
        $currentAdminId = $adminUser['admin_id'];
        $options = [
            'username' => 'Username',
            'first_name' => 'Firstname',
            'email' => 'Email'
        ];

        $query = User::with('role:id,name')
            ->where('is_admin', 1)
            ->where('id', '!=', $currentAdminId);

        if (!$adminUser['administrator']) {
            $query->where('parent_id', $adminUser['parent_id']);
        } else {
            $query->where('parent_id', '!=', 0);
        }

        if (!empty($keyword)) {
            if ($fieldname === 'All' || empty($fieldname)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('first_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('username', 'LIKE', "%{$keyword}%")
                        ->orWhere('email', 'LIKE', "%{$keyword}%");
                });
            } else {
                $query->where("{$fieldname}", 'LIKE', "%{$keyword}%");
            }
        }

        if (!empty($show) && $show !== 'All') {
            $matchStatus = ($show === 'Active') ? 1 : 0;
            $query->where('status', $matchStatus);
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitKey => $limit]);
        } else {
            $limit = session($sessionLimitKey, $this->recordsPerPage);
        }

        $sort = $request->query('sort', 'id');
        $direction = $request->query('direction', 'desc');
        $users = $query->orderBy($sort, $direction)->paginate($limit);

        if ($request->ajax()) {
            return view('admin.admin_staffs.elements.index', compact('title', 'keyword', 'show', 'options', 'fieldname', 'users', 'limit'));
        }

        return view('admin.admin_staffs.index', compact('title', 'keyword', 'show', 'options', 'fieldname', 'users', 'limit'));
    }
    public function multiplAction(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $status = $request->input('User.status');
        $selectedIds = (array) $request->input('select', []);
        $ids = array_values(array_filter(array_map('intval', $selectedIds)));

        if (!empty($ids)) {
            if ($status === 'active') {
                User::whereIn('id', $ids)->update(['status' => 1]);
                session()->flash('success', 'Updated successfully.');
            } elseif ($status === 'inactive') {
                User::whereIn('id', $ids)->update(['status' => 0]);
                session()->flash('success', 'Updated successfully.');
            } elseif ($status === 'del') {
                AdminUserRole::whereIn('user_id', $ids)->delete();
                User::whereIn('id', $ids)->delete();
                session()->flash('success', 'Deleted successfully.');
            }
        }

        $keyword = $request->input('Search.keyword', $request->query('keyword'));
        $searchin = $request->input('Search.searchin', $request->query('searchin'));
        $showtype = $request->input('Search.show', $request->query('showtype'));

        if (!empty($keyword) || !empty($searchin) || !empty($showtype)) {
            $queryParams = http_build_query(array_filter([
                'keyword' => $keyword,
                'searchin' => $searchin,
                'showtype' => $showtype
            ]));
            return redirect("admin/admins/index?$queryParams");
        }

        return redirect('admin/admin_staffs/index');
    }
    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Staff User' : 'Add Staff User';
        $message = !empty($id) ? 'Admin user updated successfully.' : 'Admin user created successfully.';
        $adminUser = $this->getAdminUserId();

        if ($adminUser['administrator']) {
            return redirect('admin/admin_staffs/index')->with('error', 'Sorry, you are not an authorized user for this action.');
        }

        $parentId = $adminUser['parent_id'];

        if ($request->isMethod('post') || $request->isMethod('put')) {

            $rules = [
                'User.first_name' => ['required', 'regex:/^[a-zA-Z0-9 ]{1,50}$/'],
                'User.email' => ['required', 'email', 'unique:users,email,' . $id],
                'User.username' => ['required', 'unique:users,username,' . $id],
                'User.contact_number' => [
                    'required',
                    'unique:users,contact_number,' . $id,
                    function ($attribute, $value, $fail) {
                        $clean = preg_replace("/[^0-9]/", "", $value);
                        if (strlen($clean) < 10) {
                            $fail('Please enter a valid phone number.');
                        }
                    },
                ],
            ];

            $messages = [
                'User.first_name.required' => 'Please enter your first name.',
                'User.first_name.regex' => 'Please enter a valid first name.',
                'User.email.required' => 'Please enter your email.',
                'User.email.email' => 'Please enter a valid email address.',
                'User.email.unique' => 'Email already exists, please choose another email.',
                'User.username.required' => 'Please enter a username.',
                'User.username.unique' => 'Username already exists, please choose another.',
                'User.contact_number.required' => 'Please enter phone number.',
                'User.contact_number.unique' => 'Phone number already exists, please choose another.',
            ];

            $request->validate($rules, $messages);

            $userData = $request->input('User');
            $userData['first_name'] = Str::title(strtolower($userData['first_name']));
            $userData['last_name'] = Str::title(strtolower($userData['last_name']));
            $userData['parent_id'] = $parentId;

            if (!empty($userData['npwd'])) {
                $userData['password'] = Security::hash($userData['npwd'], null, true);
            } elseif (!empty($userData['newpassword']) && !empty($userData['cnfpassword'])) {
                $userData['password'] = Security::hash($userData['newpassword'], null, true);
            }

            unset($userData['npwd'], $userData['newpassword'], $userData['cnfpassword']);

            $user = User::updateOrCreate(
                ['id' => $id],
                $userData
            );

            return redirect('admin/admin_staffs/index')->with('success', $message);
        }

        $user = collect();

        if (!empty($id) && !$request->isMethod('post')) {
            $user = User::findOrFail($id);
        }

        $roles = AdminUserRole::where('user_id', $parentId)
            ->with('role')
            ->get()
            ->pluck('role.name', 'role.id')
            ->toArray();

        return view('admin.admin_staffs.add', compact('user', 'title', 'roles'));
    }
    public function status($id = null, $status = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);

        if (!empty($id)) {
            $newStatus = ($status == 1) ? 1 : 0;
            User::where('id', $id)->update(['status' => $newStatus]);
        }

        return redirect('/admin/admin_staffs/index')->with('success', 'Status updated.');
    }
    public function delete($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!empty($id)) {
            User::where('id', $id)->delete();
        }

        return redirect('/admin/admin_staffs/index')->with('success', 'User deleted.');
    }
}
