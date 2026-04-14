<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketplacePdealersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'marketplace_pdealers_limit';

    protected function basePath(): string
    {
        return '/admin/marketplace_pdealers';
    }

    /**
     * Cake `admin_index`: paginated list with keyword (name/address/phone) and status filter.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $adminUser = $this->getAdminUserid();
        if (empty($adminUser['administrator'])) {
            return redirect('/admin/linked_users/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        $limit = $this->resolveLimit($request);

        $keyword = trim((string) $request->input('Search.keyword', $request->query('keyword', '')));
        $show = (string) $request->input('Search.show', $request->query('showtype', ''));

        $dealers = null;
        if (Schema::hasTable('marketplace_pdealers')) {
            $q = DB::table('marketplace_pdealers');

            if ($keyword !== '') {
                $like = '%' . addcslashes($keyword, '%_\\') . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            }

            $statusFilter = $this->normalizeStatusFilter($show);
            if ($statusFilter !== null) {
                $q->where('status', $statusFilter);
            }

            $appendQuery = ['Record' => ['limit' => $limit]];
            if ($keyword !== '' || $show !== '') {
                $appendQuery['Search'] = array_filter([
                    'keyword' => $keyword !== '' ? $keyword : null,
                    'show' => $show !== '' ? $show : null,
                ]);
            }

            $dealers = $q->orderByDesc('id')
                ->paginate($limit)
                ->appends($appendQuery);
        }

        return view('admin.marketplace_pdealers.index', [
            'title_for_layout' => 'Marketplace Pending Dealers',
            'dealers' => $dealers,
            'keyword' => $keyword,
            'show' => $show,
            'limit' => $limit,
            'basePath' => $this->basePath(),
        ]);
    }

    /**
     * Cake `admin_status`: set status to 1 or 0 (base64 id).
     */
    public function status(Request $request, $id = null, $status = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $adminUser = $this->getAdminUserid();
        if (empty($adminUser['administrator'])) {
            return redirect('/admin/linked_users/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        $decoded = $this->decodeId((string) $id);
        if ($decoded !== null && Schema::hasTable('marketplace_pdealers')) {
            $exists = DB::table('marketplace_pdealers')->where('id', $decoded)->exists();
            if ($exists) {
                $update = ['status' => (int) $status === 1 ? 1 : 0];
                if (Schema::hasColumn('marketplace_pdealers', 'modified')) {
                    $update['modified'] = now()->toDateTimeString();
                }
                DB::table('marketplace_pdealers')->where('id', $decoded)->update($update);

                return redirect()->back()->with('success', 'Record status has been changed.');
            }
        }

        return redirect()->back()->with('error', 'Sorry, something went wrong.');
    }

    /**
     * Cake `admin_delete`: remove row (base64 id). Legacy Cake used wrong model on deleteAll.
     */
    public function delete(Request $request, $id = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $adminUser = $this->getAdminUserid();
        if (empty($adminUser['administrator'])) {
            return redirect('/admin/linked_users/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        $decoded = $this->decodeId((string) $id);
        if ($decoded !== null && Schema::hasTable('marketplace_pdealers')) {
            $deleted = DB::table('marketplace_pdealers')->where('id', $decoded)->delete();
            if ($deleted) {
                return redirect($this->basePath() . '/index')
                    ->with('success', 'Record has been deleted, succesfully');
            }
        }

        return redirect($this->basePath() . '/index')->with('error', 'Sorry, selected record not found');
    }

    private function normalizeStatusFilter(string $show): ?int
    {
        if ($show === '') {
            return null;
        }
        if ($show === 'Active' || $show === '1') {
            return 1;
        }
        if ($show === 'Deactive' || $show === '0') {
            return 0;
        }

        if (is_numeric($show)) {
            return ((int) $show) === 1 ? 1 : 0;
        }

        return null;
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

        return 50;
    }
}
