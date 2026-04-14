<?php

namespace App\Http\Controllers\Cloud\Report;

use App\Http\Controllers\Cloud\Report\Concerns\CloudReportAccess;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashflowController extends LegacyAppController
{
    use CloudReportAccess;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $adminUser = $this->getAdminUserid();
        if (! empty($adminUser['administrator'])) {
            return redirect()->back()->with('error', 'Sorry, you are not authorized user for this action');
        }

        [$dealers] = $this->managedDealerAccountsQuery($this->cloudParentAdminId());

        $dateFrom = $request->input('Search.date_from', $request->query('date_from', ''));
        $dateTo = $request->input('Search.date_to', $request->query('date_to', ''));
        $userIdRaw = $request->input('Search.user_id', $request->query('user_id', ''));

        $vehicles = collect();
        $revShare = '';

        if ($userIdRaw !== '' && $userIdRaw !== null) {
            $userId = (int) $userIdRaw;
            $vehicles = DB::table('vehicles')
                ->where('user_id', $userId)
                ->get();

            $revRow = DB::table('rev_settings')
                ->where('user_id', $userId)
                ->select('rev')
                ->first();
            $revShare = $revRow && isset($revRow->rev) ? $revRow->rev : 85;
        }

        return view('cloud.report.cashflow.index', [
            'title_for_layout' => 'Portfolio Reports',
            'vehicles' => $vehicles,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => $userIdRaw,
            'rev_share' => $revShare,
            'dealers' => $dealers,
        ]);
    }
}
