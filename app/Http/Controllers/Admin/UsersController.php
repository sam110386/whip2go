<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\User as LegacyUser;
use Illuminate\Http\Request;

class UsersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        $keyword = trim((string) ($request->query('keyword') ?? ''));
        $show = trim((string) ($request->query('show') ?? ''));
        $type = trim((string) ($request->query('type') ?? ''));

        $q = LegacyUser::query()
            ->where('is_admin', 0)
            ->orderByDesc('id');

        if ($show === 'Active') {
            $q->where('status', 1);
        } elseif ($show === 'Deactive') {
            $q->where('status', 0);
        }

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('business_name', 'like', $like);
            });
        }

        if ($type === '1') {
            $q->where('is_verified', 1);
        } elseif ($type === '2') {
            $q->where('is_verified', 0);
        } elseif ($type === '3') {
            $q->where('is_renter', 1);
        } elseif ($type === '4') {
            $q->where('is_driver', 1);
        } elseif ($type === '5') {
            $q->where('is_dealer', 1);
        } elseif ($type === '6') {
            $q->where('is_dealer', 2);
        }

        $users = $q->limit(50)->get();

        return view('admin.users.index', [
            'listTitle' => 'Manage Users',
            'users' => $users,
            'keyword' => $keyword,
            'show' => $show,
            'type' => $type,
        ]);
    }

    public function admin_status(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['status' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request);
    }

    public function admin_trash(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['trash' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request);
    }

    public function admin_verify(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update([
                'is_verified' => 1,
                'verify_token' => '',
            ]);
        }
        return $this->redirectBackOr('/admin/users/index', $request);
    }

    public function admin_driverstatus(Request $request, $id = null, $status = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->update(['is_driver' => ((string) $status === '1') ? 1 : 0]);
        }
        return $this->redirectBackOr('/admin/users/index', $request);
    }

    public function admin_view(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        $user = $userId ? LegacyUser::query()->find($userId) : null;
        if (!$user) {
            return redirect('/admin/users/index');
        }
        return view('admin.users.view', [
            'listTitle' => 'View User',
            'user' => $user,
        ]);
    }

    public function admin_delete(Request $request, $id = null)
    {
        $userId = $this->decodeId($id);
        if ($userId) {
            LegacyUser::query()->whereKey($userId)->delete();
        }
        return redirect('/admin/users/index');
    }

    public function admin_add(Request $request, $id = null)
    {
        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
        $userId = $this->decodeId($id);
        $user = $userId ? LegacyUser::query()->find($userId) : null;

        if (!$request->isMethod('POST')) {
            return view('admin.users.add', [
                'listTitle' => $user ? 'Update User' : 'Add User',
                'user' => $user,
                'error' => null,
                'formAction' => $user ? '/admin/users/add/' . $id : '/admin/users/add',
            ]);
        }

        $payload = $request->input('User', []);
        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $contact = trim((string) ($payload['contact_number'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $contact === '') {
            return view('admin.users.add', [
                'listTitle' => $user ? 'Update User' : 'Add User',
                'user' => $user,
                'error' => 'Please fill required fields.',
                'formAction' => $user ? '/admin/users/add/' . $id : '/admin/users/add',
            ]);
        }

        $data = [
            'first_name' => ucwords(strtolower($firstName)),
            'last_name' => ucwords(strtolower($lastName)),
            'email' => $email,
            'contact_number' => $contact,
        ];

        if (!$user) {
            $usernameDigits = preg_replace('/[^0-9]/', '', $contact);
            $data['username'] = (string) $usernameDigits;
            $data['is_verified'] = 1;
            $data['status'] = 1;
            $pwd = (string) ($payload['pwd'] ?? '');
            if ($pwd !== '') {
                $data['password'] = sha1($salt . $pwd);
            }
            LegacyUser::query()->create($data);
        } else {
            $pwd = (string) ($payload['pwd'] ?? '');
            if ($pwd !== '') {
                $data['password'] = sha1($salt . $pwd);
            }
            LegacyUser::query()->whereKey((int) $user->id)->update($data);
        }

        return redirect('/admin/users/index');
    }

    private function decodeId($id): ?int
    {
        if (is_numeric($id)) {
            return (int) $id;
        }
        if (is_string($id) && $id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int) $decoded;
            }
        }
        return null;
    }

    private function redirectBackOr(string $fallback, Request $request)
    {
        $referer = $request->headers->get('referer');
        if (!empty($referer)) {
            return redirect()->to($referer);
        }
        return redirect($fallback);
    }
}

