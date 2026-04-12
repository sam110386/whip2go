<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\UserCcToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LinkedUsersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/users/index')->with('error', 'Sorry, you are not authorized user for this action');
        }
        $parentId = (int)($admin['parent_id'] ?? 0);

        $keyword = trim((string)$this->searchInput($request, 'keyword'));
        $searchin = trim((string)$this->searchInput($request, 'searchin'));
        $type = trim((string)$this->searchInput($request, 'type'));
        $limit = $this->resolveLimit($request);

        $q = DB::table('admin_user_associations as aua')
            ->join('users as u', 'u.id', '=', 'aua.user_id')
            ->where('aua.admin_id', $parentId)
            ->select(['u.*', 'aua.admin_id'])
            ->groupBy('u.id')
            ->orderByDesc('u.id');

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            if ($searchin === 'All' || $searchin === '') {
                $q->where(function ($qq) use ($like) {
                    $qq->where('u.first_name', 'like', $like)
                        ->orWhere('u.username', 'like', $like)
                        ->orWhere('u.email', 'like', $like);
                });
            } else {
                $q->where('u.' . $searchin, 'like', $like);
            }
        }
        if ($type === '1') {
            $q->where('u.is_driver', 1);
        } elseif ($type === '2') {
            $q->where('u.is_dealer', 1);
        }

        $users = $q->paginate($limit)->withQueryString();

        return view('admin.linked_users.index', compact('users', 'keyword', 'searchin', 'type', 'limit'));
    }

    public function cloud_view(Request $request)
    {
        $id = $this->decodeId((string)$request->input('userid', ''));
        if (!$id) {
            return response('Invalid user id', 400);
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return response('Unauthorized', 403);
        }
        $parentId = (int)($admin['parent_id'] ?? 0);
        $assoc = DB::table('admin_user_associations')->where('user_id', $id)->where('admin_id', $parentId)->first();
        if (!$assoc) {
            return response('User not linked', 404);
        }
        $user = DB::table('users')->where('id', $id)->first();

        return response()->view('admin.linked_users.view_modal', compact('user', 'assoc'));
    }

    public function cloud_edit(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/users/index')->with('error', 'Sorry, you are not authorized user for this action');
        }
        $parentId = (int)($admin['parent_id'] ?? 0);

        $userId = $this->decodeId((string)$id);
        $user = $userId ? DB::table('users')->where('id', $userId)->first() : null;

        if ($request->isMethod('POST')) {
            $payload = (array)$request->input('User', []);
            $save = $this->filterUserPayload($payload);
            if (!empty($payload['password'])) {
                $save['password'] = Hash::make((string)$payload['password']);
            }

            if ($userId) {
                DB::table('users')->where('id', $userId)->update($save);
            } else {
                $save['created'] = now()->toDateTimeString();
                $userId = (int)DB::table('users')->insertGetId($save);
                DB::table('admin_user_associations')->insert([
                    'admin_id' => $parentId,
                    'user_id' => $userId,
                ]);
            }

            return redirect('/cloud/linked_users/index')->with('success', 'User saved successfully');
        }

        return view('admin.linked_users.edit', ['user' => $user]);
    }

    public function cloud_ccindex($userid)
    {
        $userId = $this->decodeId((string)$userid);
        if (!$userId) {
            return redirect('/cloud/linked_users/index');
        }
        $cards = UserCcToken::query()->where('user_id', $userId)->orderByDesc('id')->get();

        return view('admin.linked_users.ccindex', ['cards' => $cards, 'userId' => $userId]);
    }

    public function cloud_ccdelete($id = null, $userid = null)
    {
        $cardId = $this->decodeId((string)$id);
        $userId = $this->decodeId((string)$userid);
        if ($cardId) {
            UserCcToken::query()->whereKey($cardId)->delete();
        }

        return redirect('/cloud/linked_users/ccindex/' . base64_encode((string)$userId))->with('success', 'Card deleted');
    }

    public function cloud_makeccdefault($ccid, $userid)
    {
        $cardId = $this->decodeId((string)$ccid);
        $userId = $this->decodeId((string)$userid);
        if ($cardId && $userId) {
            UserCcToken::query()->where('user_id', $userId)->update(['is_default' => 0]);
            UserCcToken::query()->whereKey($cardId)->update(['is_default' => 1]);
        }

        return redirect('/cloud/linked_users/ccindex/' . base64_encode((string)$userId))->with('success', 'Default card updated');
    }

    public function cloud_ccadd(Request $request, $userid = null)
    {
        $userId = $this->decodeId((string)$userid);
        if (!$userId) {
            return redirect('/cloud/linked_users/index');
        }
        if ($request->isMethod('POST')) {
            $data = (array)$request->input('UserCcToken', []);
            UserCcToken::query()->create([
                'user_id' => $userId,
                'card_name' => (string)($data['card_name'] ?? ''),
                'card_number' => (string)($data['card_number'] ?? ''),
                'expiry_month' => (string)($data['expiry_month'] ?? ''),
                'expiry_year' => (string)($data['expiry_year'] ?? ''),
                'is_default' => !empty($data['is_default']) ? 1 : 0,
            ]);

            return redirect('/cloud/linked_users/ccindex/' . base64_encode((string)$userId))->with('success', 'Card added');
        }

        return view('admin.linked_users.ccadd', ['userId' => $userId]);
    }

    public function cloud_customer(Request $request)
    {
        return $this->cloud_index($request);
    }

    public function cloud_updatetargetscore(Request $request): JsonResponse
    {
        $userId = (int)$request->input('pk', 0);
        $score = (float)$request->input('value', 0);
        if ($userId <= 0 || $score <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, something went wrong']);
        }
        $exists = DB::table('cs_user_convertibilities')->where('user_id', $userId)->exists();
        if ($exists) {
            DB::table('cs_user_convertibilities')->where('user_id', $userId)->update(['target_score' => $score]);
        } else {
            DB::table('cs_user_convertibilities')->insert(['user_id' => $userId, 'target_score' => $score]);
        }

        return response()->json(['status' => 'success', 'message' => 'Record updated successfully']);
    }

    public function cloud_dynamicfares($userId)
    {
        $id = $this->decodeId((string)$userId);
        if (!$id) {
            return redirect('/cloud/linked_users/index');
        }
        $fares = DB::table('dynamic_fares')->where('user_id', $id)->orderByDesc('id')->get();

        return view('admin.linked_users.dynamicfares', ['rows' => $fares, 'userId' => $id]);
    }

    private function filterUserPayload(array $payload): array
    {
        $allowed = [
            'first_name', 'last_name', 'email', 'username', 'contact_number', 'status',
            'is_driver', 'is_dealer', 'address', 'city', 'state', 'zip',
        ];
        $out = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $payload)) {
                $out[$k] = $payload[$k];
            }
        }
        $out['modified'] = now()->toDateTimeString();

        return $out;
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string)$v;
        }

        return $request->input($key);
    }

    private function resolveLimit(Request $request): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['linked_users_limit' => $lim]);
            }
        }
        $limit = (int)session('linked_users_limit', 50);

        return $limit > 0 ? $limit : 50;
    }

    private function decodeId(string $id): ?int
    {
        if (is_numeric($id)) {
            return (int)$id;
        }
        if ($id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int)$decoded;
            }
        }

        return null;
    }
}

