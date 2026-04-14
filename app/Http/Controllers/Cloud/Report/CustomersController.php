<?php

namespace App\Http\Controllers\Cloud\Report;

use App\Http\Controllers\Cloud\Report\Concerns\CloudReportAccess;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Report\ReportCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomersController extends LegacyAppController
{
    use CloudReportAccess;

    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        if ($redirect = $this->guardCloudReportAccess()) {
            return $redirect;
        }

        [$dealers, $dealerIds] = $this->managedOwnerDealersQuery($this->cloudParentAdminId());

        $keyword = $request->input('Search.keyword', $request->query('keyword', ''));
        $dealerid = $request->input('Search.dealerid', $request->query('dealerid', ''));
        $renterid = $request->input('Search.renterid', $request->query('renterid', ''));

        $sessKey = 'customers_limit';
        $limit = $this->getPageLimit($request, $sessKey, $this->recordsPerPage);

        $query = DB::table('report_customers as ReportCustomer')
            ->leftJoin('users as User', 'User.id', '=', 'ReportCustomer.renter_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'ReportCustomer.vehicle_id')
            ->select('ReportCustomer.*', 'User.first_name', 'User.last_name', 'Vehicle.vehicle_name')
            ->orderByDesc('ReportCustomer.id');

        if ($dealerid !== '' && $dealerid !== null) {
            $query->where('ReportCustomer.user_id', (int) $dealerid);
        } elseif ($dealerIds !== []) {
            $query->whereIn('ReportCustomer.user_id', $dealerIds);
        } else {
            $query->whereRaw('0 = 1');
        }

        if ($renterid !== '' && $renterid !== null) {
            $query->where('ReportCustomer.renter_id', (int) $renterid);
        }

        $lists = $query->paginate($limit)->withQueryString();

        return view('cloud.report.customers.index', [
            'title_for_layout' => 'Customer Cash flow',
            'lists' => $lists,
            'keyword' => $keyword,
            'dealerid' => $dealerid,
            'renterid' => $renterid,
            'dealers' => $dealers,
            'Record' => ['limit' => $limit],
        ]);
    }

    public function refresh(Request $request, ReportCustomerService $reportCustomerService): JsonResponse
    {
        if ($this->ensureCloudAdminSession() !== null) {
            return response()->json(['status' => false, 'msg' => 'Session expired', 'result' => ''], 401);
        }

        $return = ['status' => false, 'msg' => '', 'result' => ''];
        $rowid = $request->input('rowid');
        if (empty($rowid)) {
            $return['msg'] = 'Sorry, request data is not complete. Please try again.';

            return response()->json($return);
        }

        $reportCustomerService->refreshReport($rowid);

        $return['status'] = true;
        $return['msg'] = 'Report data updated successfully.';

        $list = DB::table('report_customers as ReportCustomer')
            ->leftJoin('users as User', 'User.id', '=', 'ReportCustomer.renter_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'ReportCustomer.vehicle_id')
            ->where('ReportCustomer.id', $rowid)
            ->select('ReportCustomer.*', 'User.first_name', 'User.last_name', 'Vehicle.vehicle_name')
            ->first();

        if ($list !== null) {
            $return['result'] = view('cloud.report.customers.cloud_single_row', ['list' => $list])->render();
        }

        return response()->json($return);
    }
}
