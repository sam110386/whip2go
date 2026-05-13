<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\RevSetting;

class PortfoliosController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $vehicles = [];
        $rev_share = 85;
        $taxIncluded = false;
        $rental_rev = 85;
        $date_from = $request->input('Search.date_from', $request->query('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->query('date_to', ''));
        $user_id = $request->input('Search.user_id', $request->query('user_id', ''));

        if (!empty($user_id)) {
            $vehicles = Vehicle::where('user_id', $user_id)->get();
            $revSetting = RevSetting::where('user_id', $user_id)->first(['rev', 'tax_included', 'rental_rev']);

            if ($revSetting) {
                $rev_share = $revSetting->rev ?? 85;
                $taxIncluded = (bool) $revSetting->tax_included;
                $rental_rev = $revSetting->rental_rev ?? 85;
            }
        }

        return view('admin.report.portfolios.index', compact(
            'vehicles',
            'date_to',
            'date_from',
            'user_id',
            'rev_share',
            'taxIncluded',
            'rental_rev'
        ));
    }
}
