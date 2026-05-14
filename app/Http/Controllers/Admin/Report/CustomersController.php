<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Legacy\ReportCustomer;

class CustomersController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Customer Cash flow';
        $sessionLimitKey = "report_customer_limit";
        $keyword = $request->input('keyword', $request->query('keyword', ''));
        $dealerid = $request->input('dealerid', $request->query('dealerid', ''));
        $renterid = $request->input('renterid', $request->query('renterid', ''));

        if ($request->filled('Record.limit')) {
            $limit = (int) $request->input('Record.limit');
            Session::put($sessionLimitKey, $limit);
        } elseif ($request->filled('limit')) {
            $limit = (int) $request->input('limit');
            Session::put($sessionLimitKey, $limit);
        } else {
            $limit = Session::get($sessionLimitKey, 50);
        }

        $lists = ReportCustomer::with(['user:id,first_name,last_name', 'vehicle:id,vehicle_name'])
            ->when($keyword, function ($query, $keyword) {
                $query->where('increment_id', 'LIKE', "%$keyword%");
            })
            ->when($dealerid, function ($query, $dealerid) {
                $query->where('user_id', $dealerid);
            })
            ->when($renterid, function ($query, $renterid) {
                $query->where('renter_id', $renterid);
            })
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        if ($request->ajax()) {
            return view('admin.report.customers.elements.index', compact('title', 'keyword', 'dealerid', 'renterid', 'lists', 'limit'));
        }

        return view('admin.report.customers.index', compact('title', 'keyword', 'dealerid', 'renterid', 'lists', 'limit'));
    }

    public function refresh(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'msg' => 'Unauthorized', 'result' => ''], 401);
        }

        $return = ["status" => false, "msg" => "", "result" => ""];
        $rowId = $request->input('rowid');

        if (empty($rowId)) {
            $return['msg'] = "Sorry, request data is not complete. Please try again.";
            return response()->json($return);
        }

        ReportCustomer::refreshReport($rowId);
        $return['status'] = true;
        $return['msg'] = 'Report data updated successfully.';
        $list = ReportCustomer::with(['user:id,first_name,last_name', 'vehicle:id,vehicle_name'])->find($rowId);

        if ($list) {
            $singleRowHtml = view('admin.report.elements.single_row', compact('list'))->render();
            $return['result'] = $singleRowHtml;
        }

        return response()->json($return);
    }
}
