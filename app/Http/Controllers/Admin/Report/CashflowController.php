<?php
namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\RevSetting;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;

class CashflowController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Cash Flow - Report';
        $date_from = $request->input('Search.date_from', $request->query('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->query('date_to', ''));
        $user_id = $request->input('Search.user_id', $request->query('user_id', ''));

        $vehicles = [];
        $rev_share = 85;
        $taxIncluded = false;
        $rental_rev = 85;

        if (!empty($user_id)) {
            $vehicles = Vehicle::where('user_id', $user_id)->get();
            $revSetting = RevSetting::where('user_id', $user_id)
                ->select('rev', 'tax_included', 'rental_rev')
                ->first();

            if ($revSetting) {
                $rev_share = $revSetting->rev;
                $taxIncluded = $revSetting->tax_included == 0 ? false : true;
                $rental_rev = $revSetting->rental_rev;
            }
        }

        return view('admin.report.cashflow.index', compact(
            'title',
            'vehicles',
            'date_from',
            'date_to',
            'user_id',
            'rev_share',
            'taxIncluded',
            'rental_rev'
        ));
    }
}
