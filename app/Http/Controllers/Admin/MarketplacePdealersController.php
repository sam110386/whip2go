<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplacePdealersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $adminUser = session('AdminUser');
        if (empty($adminUser['administrator'])) {
            return redirect('/admin/linked_users/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $searchData = $request->input('Search', []);
        $namedData  = $request->query();

        $value    = $namedData['keyword']  ?? $searchData['keyword']  ?? '';
        $showtype = $namedData['showtype'] ?? $searchData['show']     ?? '';

        $query = DB::table('marketplace_pdealers as MarketplacePdealer');

        if ($value !== '') {
            $v = strip_tags($value);
            $query->where(function ($q) use ($v) {
                $q->where('MarketplacePdealer.name',    'LIKE', "%{$v}%")
                  ->orWhere('MarketplacePdealer.address', 'LIKE', "%{$v}%")
                  ->orWhere('MarketplacePdealer.phone',   'LIKE', "%{$v}%");
            });
        }

        if ($showtype !== '') {
            $query->where('MarketplacePdealer.status', $showtype);
        }

        $sessionLimitKey  = 'MarketplacePdealers_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $users = $query->orderBy('MarketplacePdealer.id', 'DESC')->paginate($limit)->withQueryString();

        return view('admin.marketplace_pdealers.index', [
            'title_for_layout' => 'Marketplace Pending Dealers',
            'keyword'          => $value,
            'show'             => $showtype,
            'users'            => $users,
        ]);
    }

    public function admin_status(Request $request, $id = null, $status = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = base64_decode($id);
        if (!empty($decodedId)) {
            DB::table('marketplace_pdealers')
                ->where('id', $decodedId)
                ->update(['status' => $status == 1 ? 1 : 0]);
        }

        return redirect()->back()->with('success', 'Record status has been changed.');
    }

    public function admin_delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = base64_decode($id);
        DB::table('marketplace_pdealers')->where('id', $decodedId)->delete();

        return redirect('/admin/marketplace_pdealers/index')->with('success', 'Record has been deleted, succesfully');
    }
}
