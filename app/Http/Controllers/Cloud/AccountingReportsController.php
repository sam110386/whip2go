<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Reportlib;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingReportsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        $keyword = $fieldname = $dateFrom = $dateTo = '';
        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $query = DB::table('reports as Report')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'Report.cs_order_id')
            ->select('Report.*', 'CsOrder.increment_id', 'CsOrder.timezone')
            ->where('Report.user_id', $userid);

        if ($request->has('Search') || $request->filled('keyword')) {
            $fieldname = $request->input('Search.searchin', $request->input('searchin', ''));
            $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
            $dateFrom = $request->input('Search.date_from', $request->input('date_from', ''));
            $dateTo = $request->input('Search.date_to', $request->input('date_to', ''));

            if (!empty($dateFrom) && empty($dateTo)) {
                $dateTo = date('Y-m-d');
            }

            if (!empty($keyword) && $fieldname == '1') {
                $query->where('Report.transaction_id', 'LIKE', "%{$keyword}%");
            }
            if (!empty($dateFrom)) {
                $parsedFrom = Carbon::parse($dateFrom)->format('Y-m-d');
                $query->where('Report.created', '>=', $parsedFrom);
            }
            if (!empty($dateTo)) {
                $parsedTo = Carbon::parse($dateTo)->format('Y-m-d');
                $query->where('Report.created', '<=', $parsedTo);
            }
        }

        $sessLimitName = 'reports_limit';
        $limit = $request->input('Record.limit',
            session($sessLimitName, $this->recordsPerPage));
        if ($request->filled('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $reportlists = $query->orderByDesc('Report.id')->paginate($limit);

        return view('cloud.accounting.index', compact(
            'reportlists', 'keyword', 'fieldname', 'dateFrom', 'dateTo'
        ));
    }
}
