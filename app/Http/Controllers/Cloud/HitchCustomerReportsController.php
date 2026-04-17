<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HitchCustomerReportsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $userid = session('SESSION_ADMIN.id');
        $keyword = $renterid = '';
        $conditions = [
            ['HitchLead.dealer_id', '=', $userid],
        ];

        if ($request->has('Search') || $request->hasAny(['keyword', 'renterid'])) {
            $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
            $renterid = $request->input('Search.renterid', $request->input('renterid', ''));

            if (!empty($renterid)) {
                $conditions[] = ['ReportCustomer.renter_id', '=', $renterid];
            }
        }

        $sessLimitKey = 'hitch_customer_reports_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitKey, $this->recordsPerPage);
        session([$sessLimitKey => $limit]);

        $query = DB::table('report_customers as ReportCustomer')
            ->leftJoin('users as User', 'User.id', '=', 'ReportCustomer.renter_id')
            ->leftJoin('hitch_leads as HitchLead', 'HitchLead.user_id', '=', 'ReportCustomer.renter_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'ReportCustomer.vehicle_id')
            ->whereNotNull('HitchLead.id')
            ->where($conditions)
            ->select('ReportCustomer.*', 'User.first_name', 'User.last_name', 'Vehicle.vehicle_name')
            ->orderByDesc('ReportCustomer.id');

        $lists = $query->paginate($limit)->withQueryString();

        $viewData = [
            'lists' => $lists,
            'keyword' => $keyword,
            'renterid' => $renterid,
            'title_for_layout' => 'Customer Report',
        ];

        if ($request->ajax()) {
            return response()->view('cloud.hitch.customer_reports._table', $viewData);
        }

        return view('cloud.hitch.customer_reports.index', $viewData);
    }

    public function refresh(Request $request): JsonResponse
    {
        $return = ['status' => false, 'msg' => '', 'result' => ''];
        $rowid = $request->input('rowid');

        if (empty($rowid)) {
            $return['msg'] = 'Sorry, request data is not complete. Please try again.';
            return response()->json($return);
        }

        // Trigger the legacy refreshReport logic
        DB::statement("CALL refresh_report_customer(?)", [$rowid]);

        $return['status'] = true;
        $return['msg'] = 'Report data updated successfully.';

        $list = DB::table('report_customers as ReportCustomer')
            ->leftJoin('users as User', 'User.id', '=', 'ReportCustomer.renter_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'ReportCustomer.vehicle_id')
            ->where('ReportCustomer.id', $rowid)
            ->select('ReportCustomer.*', 'User.first_name', 'User.last_name', 'Vehicle.vehicle_name')
            ->first();

        if (!empty($list)) {
            $html = view('cloud.hitch.customer_reports._single_row', ['list' => $list])->render();
            $return['result'] = $html;
        }

        return response()->json($return);
    }

    public function customerautocomplete(Request $request): JsonResponse
    {
        $searchTerm = $request->query('term', '');
        $userid = session('SESSION_ADMIN.id');

        if ($request->filled('renter_id')) {
            $userlists = DB::table('users')
                ->where('id', $request->query('renter_id'))
                ->select('id', 'first_name', 'contact_number')
                ->limit(1)
                ->get();
        } else {
            $like = '%' . addcslashes($searchTerm, '%_\\') . '%';
            $userlists = DB::table('users as User')
                ->leftJoin('hitch_leads as HitchLead', 'HitchLead.user_id', '=', 'User.id')
                ->where('HitchLead.dealer_id', $userid)
                ->whereNotNull('HitchLead.id')
                ->where('User.status', 1)
                ->where(function ($q) use ($like) {
                    $q->where('User.contact_number', 'like', $like)
                        ->orWhere('User.first_name', 'like', $like)
                        ->orWhere('User.email', 'like', $like)
                        ->orWhere('User.last_name', 'like', $like);
                })
                ->select('User.id', 'User.first_name', 'User.contact_number')
                ->orderBy('User.first_name')
                ->limit(10)
                ->get();
        }

        $users = [];
        foreach ($userlists as $value) {
            $users[] = [
                'id' => $value->id,
                'tag' => $value->first_name . ' - ' . $value->contact_number,
            ];
        }

        return response()->json($users);
    }
}
