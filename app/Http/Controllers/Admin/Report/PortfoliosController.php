<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortfoliosController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Fleet - P&L';
        $date_from = $request->input('Search.date_from', $request->query('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->query('date_to', ''));
        $user_id = $request->input('Search.user_id', $request->query('user_id', ''));

        $vehicles = [];
        $rev_share = '';
        $taxIncluded = false;
        $rental_rev = 85;

        if (! empty($user_id)) {
            $vehicles = DB::table('vehicles')
                ->where('user_id', $user_id)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();

            $revShareRow = DB::table('rev_settings')->where('user_id', $user_id)->first(['rev', 'tax_included', 'rental_rev']);
            if ($revShareRow) {
                $rev_share = $revShareRow->rev ?? 85;
                $taxIncluded = isset($revShareRow->tax_included) && (int) $revShareRow->tax_included !== 0;
                $rental_rev = $revShareRow->rental_rev ?? 85;
            } else {
                $rev_share = 85;
                $taxIncluded = false;
                $rental_rev = 85;
            }
        }

        return view('admin.report.portfolios.index', compact(
            'title',
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
