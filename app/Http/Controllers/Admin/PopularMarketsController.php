<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
class PopularMarketsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'popular_markets_limit';

    protected function basePath(): string
    {
        return '/admin/popular_markets';
    }

    /**
     * Cake `admin_index`: paginated list; AJAX returns listing HTML for `#listing`.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = $this->resolveLimit($request);
        $request->merge(['Record' => ['limit' => $limit]]);

        $popularMarkets = null;
        if (Schema::hasTable('popular_markets')) {
            $popularMarkets = DB::table('popular_markets')
                ->orderByDesc('id')
                ->paginate($limit)
                ->appends(['Record' => ['limit' => $limit]])
                ->withQueryString();
        }

        $viewData = [
            'title_for_layout' => 'Manage Popular Markets',
            'popularMarkets' => $popularMarkets,
            'limit' => $limit,
            'basePath' => $this->basePath(),
        ];

        if ($request->ajax()) {
            return response()->view('admin.popular_markets.partials.listing', $viewData);
        }

        return view('admin.popular_markets.index', $viewData);
    }

    /**
     * Cake `admin_add`: create/update using `PopularMarket[field]` POST names.
     */
    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!Schema::hasTable('popular_markets')) {
            return redirect($this->basePath() . '/index')->with('error', 'Popular markets table is not available.');
        }

        $decodedId = $this->decodeId($id !== null ? (string) $id : '');
        $record = null;
        if ($decodedId !== null) {
            $record = DB::table('popular_markets')->where('id', $decodedId)->first();
        }

        $listTitle = ($decodedId !== null && $record) ? 'Edit' : 'Add';

        if ($request->isMethod('post')) {
            $input = (array) $request->input('PopularMarket', []);

            $rowId = null;
            if (!empty($input['id'])) {
                $rowId = (int) $input['id'];
            } elseif ($decodedId !== null) {
                $rowId = $decodedId;
            }

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'PopularMarket.id' => ['nullable', 'integer'],
                'PopularMarket.name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('popular_markets', 'name')->ignore($rowId),
                ],
                'PopularMarket.lat' => ['required', 'string', 'max:20'],
                'PopularMarket.lng' => ['required', 'string', 'max:20'],
                'PopularMarket.radius' => ['required', 'integer', 'min:0', 'max:999999'],
                'PopularMarket.status' => ['nullable', 'in:0,1'],
            ], [
                'PopularMarket.name.required' => 'Please enter location title',
                'PopularMarket.name.unique' => 'This records already exists',
                'PopularMarket.lat.required' => 'Please enter location latitute',
                'PopularMarket.lng.required' => 'Please enter locaation longitude',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $status = isset($input['status']) ? (int) $input['status'] : 1;
            $status = $status === 1 ? 1 : 0;

            $payload = [
                'name' => trim((string) $input['name']),
                'lat' => trim((string) $input['lat']),
                'lng' => trim((string) $input['lng']),
                'radius' => (int) $input['radius'],
                'status' => $status,
            ];

            $now = now()->toDateTimeString();

            if ($rowId !== null) {
                $exists = DB::table('popular_markets')->where('id', $rowId)->first();
                if (!$exists) {
                    return redirect($this->basePath() . '/index')->with('error', 'Sorry, you are not authorized user for this action');
                }
                $update = $payload;
                if (Schema::hasColumn('popular_markets', 'updated')) {
                    $update['updated'] = $now;
                }
                DB::table('popular_markets')->where('id', $rowId)->update($update);
                $message = 'Record data updated successfully';
            } else {
                $insert = $payload + ['created' => $now];
                if (Schema::hasColumn('popular_markets', 'updated')) {
                    $insert['updated'] = $now;
                }
                DB::table('popular_markets')->insert($insert);
                $message = 'Record data saved successfully';
            }

            return redirect($this->basePath() . '/index')->with('success', $message);
        }

        if ($decodedId !== null && !$record) {
            return redirect($this->basePath() . '/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        return view('admin.popular_markets.add', [
            'listTitle' => $listTitle,
            'record' => $record,
            'basePath' => $this->basePath(),
        ]);
    }

    /**
     * Cake `admin_status`: toggle Active/Inactive.
     */
    public function status(Request $request, $id = null, $status = 0): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decoded = $this->decodeId((string) $id);
        if ($decoded !== null && Schema::hasTable('popular_markets')) {
            $row = DB::table('popular_markets')->where('id', $decoded)->first();
            if ($row) {
                $update = ['status' => (int) $status === 1 ? 1 : 0];
                if (Schema::hasColumn('popular_markets', 'updated')) {
                    $update['updated'] = now()->toDateTimeString();
                }
                DB::table('popular_markets')->where('id', $decoded)->update($update);

                return back()->with('success', 'Your request processed successfully.');
            }
        }

        return back()->with('error', 'Sorry, something went wrong.');
    }

    /**
     * Cake `admin_delete`.
     */
    public function delete(Request $request, $id = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decoded = $this->decodeId((string) $id);
        if ($decoded !== null && Schema::hasTable('popular_markets')) {
            $row = DB::table('popular_markets')->where('id', $decoded)->first();
            if ($row) {
                DB::table('popular_markets')->where('id', $decoded)->delete();

                return back()->with('success', 'Your request processed successfully.');
            }
        }

        return back()->with('error', 'Sorry, selected record not found');
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
