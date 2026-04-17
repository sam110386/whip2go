<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use App\Models\Legacy\User as LegacyUser;
use App\Models\Legacy\AdminRole as LegacyAdminRole;
use App\Models\Legacy\AdminUserRole as LegacyAdminUserRole;

trait AdminStaffsTrait
{
    public function staffs_index(Request $request, string $prefix)
    {
        $adminUser = $this->getAdminUserid();
        $userId = $adminUser['admin_id'] ?? 0;
        $isSuperAdmin = !empty($adminUser['administrator']);
        
        $q = LegacyUser::query()
            ->with('role')  // In Laravel 'role' relationship on User replaces Cake's INNER JOIN
            ->where('is_admin', 1)
            ->where('id', '!=', $userId);

        if (!$isSuperAdmin) {
            $q->where('parent_id', $adminUser['parent_id'] ?? 0);
        } else {
            $q->where('parent_id', '!=', 0);
        }

        $search = $request->input('Search', []);
        $keyword = $search['keyword'] ?? $request->query('keyword', '');
        $searchin = $search['searchin'] ?? $request->query('searchin', '');
        $showtype = $search['show'] ?? $request->query('showtype', '');

        if ($showtype !== '') {
            if (strcasecmp($showtype, 'Active') === 0) {
                $q->where('status', 1);
            } elseif (strcasecmp($showtype, 'Deactive') === 0) {
                $q->where('status', 0);
            }
        }

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $q->where(function ($qq) use ($like, $searchin) {
                if ($searchin === '' || strcasecmp($searchin, 'All') === 0) {
                    $qq->where('username', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                } else {
                    $fieldMap = [
                        'username' => 'username',
                        'first_name' => 'first_name',
                        'email' => 'email',
                    ];
                    $field = $fieldMap[$searchin] ?? 'username';
                    $qq->where($field, 'like', $like);
                }
            });
        }

        $limit = session()->get($prefix . '_admin_staffs_limit', 50);
        if ($request->has('Record.limit')) {
            $limit = (int) $request->input('Record.limit');
            session()->put($prefix . '_admin_staffs_limit', $limit);
        }

        $users = $q->orderByDesc('id')->paginate($limit)->map(function (LegacyUser $u) {
            $row = $u->toArray();
            $row['role_name'] = (!empty($u->role) && isset($u->role->name)) ? $u->role->name : '';
            return clone $u->fill($row); // Simple array projection emulation
        });

        return view($prefix . '.admin_staffs.index', [
            'users' => $users,
            'keyword' => $keyword,
            'searchin' => $searchin,
            'showtype' => $showtype,
            'heading' => 'Admin Users',
            'showArr' => ['Active' => 'Active', 'Deactive' => 'Inactive'],
            'options' => [
                'username' => 'Username',
                'first_name' => 'Firstname',
                'email' => 'Email',
            ],
            'limit' => $limit,
            'title_for_layout' => 'Staff Users'
        ]);
    }

    public function staffs_multiplAction(Request $request, string $prefix)
    {
        $statusAction = (string)($request->input('User.status') ?? '');
        $selected = $request->input('select', []);
        
        if (!is_array($selected)) {
            $selected = [];
        }
        
        $ids = array_values(array_filter(array_map('intval', array_keys(array_filter($selected)))));

        if (!empty($ids)) {
            if ($statusAction === 'active') {
                LegacyUser::query()->whereIn('id', $ids)->update(['status' => 1]);
                session()->flash('success', 'Selected records have been updated successfully.');
            } elseif ($statusAction === 'inactive') {
                LegacyUser::query()->whereIn('id', $ids)->update(['status' => 0]);
                session()->flash('success', 'Selected records have been updated successfully.');
            } elseif ($statusAction === 'del') {
                LegacyUser::query()->whereIn('id', $ids)->delete();
                session()->flash('success', 'Selected records have been deleted successfully.');
            }
        }
        
        $search = $request->input('Search', []);
        $keyword = $search['keyword'] ?? $request->query('keyword', '');
        $searchin = $search['searchin'] ?? $request->query('searchin', '');
        $show = $search['show'] ?? $request->query('showtype', '');

        if ($keyword !== '' && $searchin !== '' && $show !== '') {
            return redirect("/{$prefix}/admin_staffs/index?keyword=" . urlencode($keyword) . '&searchin=' . urlencode($searchin) . '&showtype=' . urlencode($show));
        }

        return redirect("/{$prefix}/admin_staffs/index");
    }

    public function staffs_add(Request $request, string $prefix, $id = null)
    {
        $isEditing = !empty($id);
        $decodedId = null;
        if (is_string($id) && $id !== '') {
            $tmp = base64_decode($id, true);
            $decodedId = $tmp !== false ? $tmp : null;
        } elseif (is_numeric($id)) {
            $decodedId = (string)$id;
        }

        $adminUser = $this->getAdminUserid();
        if (!$adminUser['administrator']) {
            $parentId = $adminUser['parent_id'];
        } else {
            return redirect("/{$prefix}/admin_staffs/index")->with('error', 'Sorry, you are not authorized user for this action');
        }

        $user = null;
        if ($isEditing && $decodedId !== null) {
            $user = LegacyUser::query()->whereKey((int)$decodedId)->first();
        }

        if (!$request->isMethod('POST')) {
            $roles = LegacyAdminUserRole::query()
                ->where('user_id', $parentId)
                ->with('role')
                ->get()
                ->mapWithKeys(function ($r) {
                    return $r->role ? [(string)$r->role->id => $r->role->name] : [];
                })->toArray();

            return view("{$prefix}.admin_staffs.add", [
                'listTitle' => $isEditing ? 'Update Staff User' : 'Add Staff User',
                'user' => $user,
                'roles' => $roles,
            ]);
        }

        $payload = $request->input('User', []);
        
        $data = [
            'first_name' => ucwords(strtolower((string)($payload['first_name'] ?? ''))),
            'last_name' => ucwords(strtolower((string)($payload['last_name'] ?? ''))),
            'parent_id' => $parentId,
            'username' => $payload['username'] ?? '',
            'email' => $payload['email'] ?? '',
        ];

        // Status checkbox or select logic
        if (isset($payload['status'])) {
            $data['status'] = $payload['status'];
        }

        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
        
        if ($isEditing) {
            $newPassword = (string)($payload['newpassword'] ?? '');
            $cnfPassword = (string)($payload['cnfpassword'] ?? '');
            if ($newPassword !== '' && $newPassword === $cnfPassword) {
                $data['password'] = sha1($salt . $newPassword);
            }
            if ($decodedId) {
                LegacyUser::query()->whereKey((int)$decodedId)->update($data);
                return redirect("/{$prefix}/admin_staffs/index")->with('success', 'Admin user updated successfully');
            }
        } else {
            $passwordPlain = (string)($payload['npwd'] ?? '');
            if ($passwordPlain !== '') {
                $data['password'] = sha1($salt . $passwordPlain);
            }
            LegacyUser::query()->create($data);
            return redirect("/{$prefix}/admin_staffs/index")->with('success', 'Admin user created successfully');
        }

        return redirect("/{$prefix}/admin_staffs/index");
    }

    public function staffs_status(Request $request, string $prefix, $id = null, $status = null)
    {
        $decodedId = null;
        if (is_string($id) && $id !== '') {
            $tmp = base64_decode($id, true);
            $decodedId = $tmp !== false ? $tmp : null;
        } elseif (is_numeric($id)) {
            $decodedId = (string)$id;
        }

        if ($decodedId !== null && $decodedId !== '') {
            $newStatus = ((string)$status === '1') ? 1 : 0;
            LegacyUser::query()->whereKey((int)$decodedId)->update(['status' => $newStatus]);
        }

        $referer = $request->headers->get('referer');
        if (!empty($referer)) {
            return redirect()->to($referer)->with('success', 'Status has been updated successfully.');
        }

        return redirect("/{$prefix}/admin_staffs/index")->with('success', 'Status has been updated successfully.');
    }

    public function staffs_delete(Request $request, string $prefix, $id = null)
    {
        if ($id !== null && $id !== '') {
            LegacyUser::query()->whereKey((int)$id)->delete();
        }
        
        return redirect("/{$prefix}/admin_staffs/index")->with('success', 'Record deleted successfully.');
    }
}
