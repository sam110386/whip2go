<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Report\ReportCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomersController extends LegacyAppController
{
    use UsesReportPageLimit;

    protected ReportCustomerService $reportCustomerService;

    public function __construct(ReportCustomerService $reportCustomerService)
    {
        parent::__construct();
        $this->reportCustomerService = $reportCustomerService;
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Customer Cash flow';
        $keyword = '';
        $dealerid = '';
        $renterid = '';

        if ($request->filled('Search') || $request->query->count() > 0) {
            $dealerid = $request->input('Search.dealerid', $request->query('dealerid', ''));
            $renterid = $request->input('Search.renterid', $request->query('renterid', ''));
            $keyword = $request->input('Search.keyword', $request->query('keyword', ''));
        }

        $limit = $this->getPageLimit($request, 'customers_limit', 25);

        $query = DB::table('report_customers as rc')
            ->leftJoin('users as u', 'u.id', '=', 'rc.renter_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'rc.vehicle_id')
            ->select('rc.*', 'u.first_name', 'u.last_name', 'v.vehicle_name')
            ->orderByDesc('rc.id');

        if ($keyword !== '') {
            $query->where('rc.increment_id', 'like', '%'.$keyword.'%');
        }
        if ($dealerid !== '') {
            $query->where('rc.user_id', $dealerid);
        }
        if ($renterid !== '') {
            $query->where('rc.renter_id', $renterid);
        }

        $lists = $query->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.report.elements.admin_index', compact('lists', 'keyword', 'dealerid', 'renterid', 'title'));
        }

        return view('admin.report.customers.index', compact('title', 'lists', 'keyword', 'dealerid', 'renterid'));
    }

    public function refresh(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'msg' => 'Unauthorized', 'result' => ''], 401);
        }

        $return = ['status' => false, 'msg' => '', 'result' => ''];
        $rowid = $request->input('rowid');
        if (empty($rowid)) {
            $return['msg'] = 'Sorry, request data is not complete. Please try again.';

            return response()->json($return);
        }

        $this->reportCustomerService->refreshReport($rowid);

        $return['status'] = true;
        $return['msg'] = 'Report data updated successfully.';

        $rc = DB::table('report_customers')->where('id', $rowid)->first();
        if ($rc) {
            $u = DB::table('users')->where('id', $rc->renter_id)->first();
            $v = DB::table('vehicles')->where('id', $rc->vehicle_id)->first();
            $list = [
                'ReportCustomer' => (array) $rc,
                'User' => ['first_name' => $u->first_name ?? '', 'last_name' => $u->last_name ?? ''],
                'Vehicle' => ['vehicle_name' => $v->vehicle_name ?? ''],
            ];
            $return['result'] = view('admin.report.elements.admin_single_row', compact('list'))->render();
        }

        return response()->json($return);
    }
}
