<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TdkDealersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'tdk_dealers_limit';

    protected function basePath(): string
    {
        return '/admin/tdk_dealers';
    }

    /**
     * Cake `admin_index`: list dealers with user join, keyword + status filters, paginated.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = $this->resolveLimit($request);
        $request->merge(['Record' => ['limit' => $limit]]);

        $keyword = trim((string) $request->input('Search.keyword', $request->input('keyword', '')));
        $show = (string) $request->input('Search.show', $request->input('showtype', ''));

        $statusFilter = null;
        if ($show === 'Active') {
            $statusFilter = 1;
        } elseif ($show === 'Deactive') {
            $statusFilter = 0;
        }

        $sort = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id', 'status'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'id';
        }

        $query = DB::table('tdk_dealers as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->select('d.*', 'u.first_name', 'u.last_name', 'u.username', 'u.email')
            ->orderBy($sort === 'id' ? 'd.id' : 'd.' . $sort, $direction);

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('u.first_name', 'like', $like)
                    ->orWhere('u.username', 'like', $like)
                    ->orWhere('u.email', 'like', $like);
            });
        }
        if ($statusFilter !== null) {
            $query->where('d.status', $statusFilter);
        }

        $users = $query->paginate($limit)->withQueryString();

        return view('admin.tdk_dealers.index', [
            'title_for_layout' => 'TDK Dealers',
            'heading' => 'TDK Dealers',
            'keyword' => $keyword,
            'show' => $show,
            'users' => $users,
            'limit' => $limit,
            'basePath' => $this->basePath(),
        ]);
    }

    /**
     * Cake `admin_add`: GET loads dealer + user; POST saves `tdk_dealers`.
     */
    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id !== null ? (string) $id : '');
        $dealer = null;
        $userLabel = '';

        if ($decodedId !== null) {
            $dealer = DB::table('tdk_dealers as d')
                ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
                ->where('d.id', $decodedId)
                ->select('d.*', 'u.first_name', 'u.last_name')
                ->first();
            if ($dealer) {
                $userLabel = trim(($dealer->first_name ?? '') . ' ' . ($dealer->last_name ?? ''));
            }
        }

        $listTitle = ($decodedId !== null && $dealer) ? 'Update TDK Dealer' : 'Add TDK Dealer';

        if ($request->isMethod('post')) {
            $input = (array) $request->input('TdkDealer', []);

            $rowId = null;
            if (!empty($input['id'])) {
                $rowId = (int) $input['id'];
            } elseif ($decodedId !== null && $dealer) {
                $rowId = $decodedId;
            }

            $uniqueUser = Rule::unique('tdk_dealers', 'user_id');
            if ($rowId !== null) {
                $uniqueUser = $uniqueUser->ignore($rowId);
            }

            $validator = Validator::make($request->all(), [
                'TdkDealer.user_id' => ['required', 'integer', 'exists:users,id', $uniqueUser],
                'TdkDealer.metro_city' => ['required', 'string', 'max:50'],
                'TdkDealer.metro_state' => ['required', 'string', 'max:3'],
                'TdkDealer.status' => ['nullable', 'in:0,1'],
            ], [
                'TdkDealer.user_id.required' => 'Please select dealer',
                'TdkDealer.user_id.unique' => 'Dealer already added.',
                'TdkDealer.metro_city.required' => 'Please enter metro city',
                'TdkDealer.metro_state.required' => 'Please enter metro state',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $status = isset($input['status']) ? (int) $input['status'] : 1;
            $status = $status === 1 ? 1 : 0;

            $payload = [
                'user_id' => (int) $input['user_id'],
                'metro_city' => trim((string) $input['metro_city']),
                'metro_state' => strtoupper(substr(trim((string) $input['metro_state']), 0, 3)),
                'status' => $status,
                'updated' => now(),
            ];

            if ($rowId !== null) {
                DB::table('tdk_dealers')->where('id', $rowId)->update($payload);

                return redirect($this->basePath() . '/index')->with('success', 'Dealer record updated successfully');
            }

            $payload['created'] = now();
            DB::table('tdk_dealers')->insert($payload);

            return redirect($this->basePath() . '/index')->with('success', 'Dealer record created successfully');
        }

        return view('admin.tdk_dealers.add', [
            'title_for_layout' => $listTitle,
            'listTitle' => $listTitle,
            'dealer' => $dealer,
            'userLabel' => $userLabel,
            'basePath' => $this->basePath(),
        ]);
    }

    /**
     * Cake `admin_delete`.
     */
    public function delete(Request $request, $id): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decoded = $this->decodeId((string) $id);
        if ($decoded !== null) {
            DB::table('tdk_dealers')->where('id', $decoded)->delete();

            return redirect()->back()->with('success', 'Your request processed successfully.');
        }

        return redirect()->back()->with('error', 'Sorry, selected record not found');
    }

    protected function resolveLimit(Request $request): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int) $fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put(self::SESSION_LIMIT_KEY, $lim);

                return $lim;
            }
        }
        $sess = (int) session()->get(self::SESSION_LIMIT_KEY, 0);
        if (in_array($sess, $allowed, true)) {
            return $sess;
        }

        return 25;
    }
}
