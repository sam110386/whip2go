<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\StaffUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CakePHP `StaffUsersController` — dealer staff sub-accounts (`users` rows: is_staff=1).
 */
class StaffUsersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const PASSWORD_SALT = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

    private function ownerUserId(): int
    {
        $parent = (int)session()->get('userParentId', 0);

        return $parent !== 0 ? $parent : (int)session()->get('userid', 0);
    }

    private function resolveLimit(Request $request, string $sessionKey): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int)$fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put($sessionKey, $lim);

                return $lim;
            }
        }
        $sess = (int)session()->get($sessionKey, 0);
        if (in_array($sess, $allowed, true)) {
            return $sess;
        }

        return 50;
    }

    private function decodeId(?string $b64): ?int
    {
        if ($b64 === null || $b64 === '') {
            return null;
        }
        $raw = base64_decode($b64, true);
        if ($raw === false || !ctype_digit((string)$raw)) {
            return null;
        }

        return (int)$raw;
    }

    private function userPicWebPath(): string
    {
        return '/img/user_pic/';
    }

    private function userPicDiskDir(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'webroot'
            . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'user_pic';
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $ownerId = $this->ownerUserId();
        if ($ownerId <= 0) {
            return redirect('/dashboard/index');
        }

        $keyword = trim((string)$request->input('Search.keyword', $request->query('keyword', '')));
        $show = trim((string)$request->input('Search.show', $request->query('showtype', '')));

        $limit = $this->resolveLimit($request, 'staff_users_limit');

        $q = StaffUser::query()
            ->where('is_staff', 1)
            ->where('staff_parent', $ownerId)
            ->orderByDesc('id');

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }
        if ($show !== '') {
            if (strcasecmp($show, 'Active') === 0) {
                $q->where('status', 1);
            } elseif (strcasecmp($show, 'Deactive') === 0 || strcasecmp($show, 'Inactive') === 0) {
                $q->where('status', 0);
            }
        }

        $subusers = $q->paginate($limit)->withQueryString();

        return view('staff_users.index', [
            'subusers' => $subusers,
            'keyword' => $keyword,
            'show' => $show,
            'limit' => $limit,
        ]);
    }

    public function status(Request $request, $id = null, $status = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $ownerId = $this->ownerUserId();
        $uid = $this->decodeId($id !== null ? (string)$id : '');
        if ($uid && Schema::hasTable('users')) {
            $affected = DB::table('users')
                ->where('id', $uid)
                ->where('staff_parent', $ownerId)
                ->where('is_staff', 1)
                ->update(['status' => (int)$status === 1 ? 1 : 0]);
            if ($affected) {
                session()->flash('success', 'Staff user status has been changed.');
            }
        }

        $ref = $request->headers->get('referer');

        return redirect()->to($ref && $ref !== '' ? $ref : '/staff_users/index');
    }

    public function delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $ownerId = $this->ownerUserId();
        $uid = $this->decodeId($id !== null ? (string)$id : '');
        if ($uid && Schema::hasTable('users')) {
            DB::table('users')
                ->where('id', $uid)
                ->where('staff_parent', $ownerId)
                ->where('is_staff', 1)
                ->delete();
            session()->flash('success', 'Staff user has been deleted, succesfully');
        }

        return redirect('/staff_users/index');
    }

    public function view(Request $request, $id = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $ownerId = $this->ownerUserId();
        $uid = $this->decodeId($id !== null ? (string)$id : '');
        if (!$uid) {
            return redirect('/staff_users/index');
        }

        $staff = DB::table('users')
            ->where('id', $uid)
            ->where('staff_parent', $ownerId)
            ->where('is_staff', 1)
            ->first();
        if (!$staff) {
            return redirect('/staff_users/index');
        }

        return view('staff_users.view', [
            'listTitle' => 'View User',
            'staff' => $staff,
            'userPicBase' => $this->userPicWebPath(),
        ]);
    }

    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }
        $ownerId = $this->ownerUserId();
        if ($ownerId <= 0) {
            return redirect('/dashboard/index');
        }

        $editId = $this->decodeId($id !== null ? (string)$id : '');
        $listTitle = $editId ? 'Update Staff User' : 'Add Staff User';

        $columns = Schema::hasTable('users') ? array_flip(Schema::getColumnListing('users')) : [];

        if ($request->isMethod('post')) {
            $input = (array)$request->input('StaffUser', []);
            $rowId = (int)($input['id'] ?? 0);
            $existingRow = null;
            if ($rowId > 0) {
                $existingRow = DB::table('users')
                    ->where('id', $rowId)
                    ->where('staff_parent', $ownerId)
                    ->where('is_staff', 1)
                    ->first();
                if (!$existingRow) {
                    return redirect('/staff_users/index');
                }
            }

            $first = trim((string)($input['first_name'] ?? ''));
            $last = trim((string)($input['last_name'] ?? ''));
            $email = trim((string)($input['email'] ?? ''));
            $contact = preg_replace('/[^0-9]/', '', (string)($input['contact_number'] ?? ''));
            $pwd = (string)($input['pwd'] ?? '');

            if ($first === '' || !preg_match('/^[a-zA-Z0-9 ]{1,50}$/', $first)) {
                session()->flash('error', 'Please enter valid first name');

                return redirect()->back()->withInput();
            }
            if ($last === '' || $email === '') {
                session()->flash('error', 'Please complete required fields.');

                return redirect()->back()->withInput();
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                session()->flash('error', 'Please enter valid email address');

                return redirect()->back()->withInput();
            }

            $emailTaken = DB::table('users')
                ->where('email', $email)
                ->when($rowId > 0, fn ($q) => $q->where('id', '!=', $rowId))
                ->exists();
            if ($emailTaken) {
                session()->flash('error', 'Email already exists.');

                return redirect()->back()->withInput();
            }

            if ($rowId === 0) {
                if ($contact === '') {
                    session()->flash('error', 'Please enter phone number.');

                    return redirect()->back()->withInput();
                }
                $username = $contact;
                $phoneTaken = DB::table('users')->where('username', $username)->exists();
                if ($phoneTaken) {
                    session()->flash('error', 'Enetered phone number already registered.');

                    return redirect()->back()->withInput();
                }
                if ($pwd === '') {
                    session()->flash('error', 'Please enter password.');

                    return redirect()->back()->withInput();
                }
            }

            $photoName = $existingRow->photo ?? null;
            if ($request->hasFile('StaffUser.photo')) {
                $file = $request->file('StaffUser.photo');
                if ($file && $file->isValid()) {
                    $dir = $this->userPicDiskDir();
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                        $ext = 'jpg';
                    }
                    $photoName = 'staff_' . ($rowId ?: 'new') . '_' . uniqid('', true) . '.' . $ext;
                    $file->move($dir, $photoName);
                }
            }

            $now = date('Y-m-d H:i:s');
            if ($rowId === 0) {
                $insert = [
                    'username' => $username,
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $email,
                    'contact_number' => $contact,
                    'password' => sha1(self::PASSWORD_SALT . $pwd),
                    'status' => 1,
                    'is_staff' => 1,
                    'is_verified' => 1,
                    'is_owner' => 1,
                    'is_driver' => 0,
                    'staff_parent' => $ownerId,
                    'dealer_id' => $ownerId,
                    'is_dealer' => 1,
                    'is_admin' => 0,
                    'photo' => $photoName,
                    'created' => $now,
                    'modified' => $now,
                ];
                if ($columns !== []) {
                    $insert = array_intersect_key($insert, $columns);
                }
                DB::table('users')->insert($insert);
                session()->flash('success', 'User has been added successfully.');
            } else {
                $update = [
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $email,
                    'modified' => $now,
                ];
                if ($pwd !== '') {
                    $update['password'] = sha1(self::PASSWORD_SALT . $pwd);
                }
                if ($photoName !== null) {
                    $update['photo'] = $photoName;
                }
                if ($columns !== []) {
                    $update = array_intersect_key($update, $columns);
                }
                DB::table('users')->where('id', $rowId)->update($update);
                session()->flash('success', 'User has been updated successfully.');
            }

            return redirect('/staff_users/index');
        }

        $staff = null;
        if ($editId) {
            $staff = DB::table('users')
                ->where('id', $editId)
                ->where('staff_parent', $ownerId)
                ->where('is_staff', 1)
                ->first();
            if (!$staff) {
                return redirect('/staff_users/index');
            }
        }

        return view('staff_users.add', [
            'listTitle' => $listTitle,
            'staff' => $staff,
            'userPicBase' => $this->userPicWebPath(),
        ]);
    }
}
