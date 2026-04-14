<?php

namespace App\Http\Controllers\Cloud\Report;

use App\Http\Controllers\Cloud\Report\Concerns\CloudReportAccess;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SummaryController extends LegacyAppController
{
    use CloudReportAccess;

    protected int $recordsPerPage = 50;

    public function index(Request $request)
    {
        if ($redirect = $this->guardCloudReportAccess()) {
            return $redirect;
        }

        [$dealers, $dealerIds] = $this->managedOwnerDealersQuery($this->cloudParentAdminId());

        $dealerid = $request->input('Search.dealerid', $request->query('dealerid', ''));

        $sessKey = 'summary_limit';
        $limit = $this->getPageLimit($request, $sessKey, $this->recordsPerPage);

        $query = DB::table('report_customers as ReportCustomer')
            ->leftJoin('users as User', 'User.id', '=', 'ReportCustomer.user_id')
            ->select(
                'ReportCustomer.user_id',
                'User.first_name',
                'User.last_name',
                DB::raw('(select count(DISTINCT vehicle_id) from report_customers as rc2 where rc2.user_id = ReportCustomer.user_id) as activevehicles'),
                DB::raw('SUM(ReportCustomer.days) as days'),
                DB::raw('SUM(ReportCustomer.miles) as miles'),
                DB::raw('SUM(ReportCustomer.total_rent + ReportCustomer.fixed_amt) as total_rent'),
                DB::raw('SUM(ReportCustomer.uncollected) as uncollected'),
                DB::raw('SUM(ReportCustomer.total_collected) as total_collected'),
                DB::raw('SUM(ReportCustomer.revpart) as revpart'),
                DB::raw('SUM(ReportCustomer.insurance) as insurance'),
                DB::raw('SUM(ReportCustomer.total_net_pay) as total_net_pay'),
                DB::raw('SUM(ReportCustomer.transferred - ReportCustomer.insurance) as transferred'),
                DB::raw('SUM(ReportCustomer.pending) as pending')
            )
            ->groupBy('ReportCustomer.user_id', 'User.first_name', 'User.last_name')
            ->orderByRaw('MAX(ReportCustomer.id) DESC');

        if ($dealerid !== '' && $dealerid !== null) {
            $query->where('ReportCustomer.user_id', (int) $dealerid);
        } elseif ($dealerIds !== []) {
            $query->whereIn('ReportCustomer.user_id', $dealerIds);
        } else {
            $query->whereRaw('0 = 1');
        }

        $lists = $query->paginate($limit)->withQueryString();

        return view('cloud.report.summary.index', [
            'title_for_layout' => 'Summary Report',
            'lists' => $lists,
            'dealerid' => $dealerid,
            'dealers' => $dealers,
            'Record' => ['limit' => $limit],
        ]);
    }
}
