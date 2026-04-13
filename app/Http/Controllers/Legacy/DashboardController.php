<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends LegacyAppController
{
    public function index()
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();
        $inbounds = collect();
        $outbounds = collect();

        if (
            Schema::hasTable('cs_twilio_logs')
            && Schema::hasTable('cs_twilio_orders')
            && Schema::hasTable('cs_orders')
        ) {
            $inbounds = $this->fetchTwilioPanelRows($userId, 2);
            $outbounds = $this->fetchTwilioPanelRows($userId, 1);
        }

        return view('dashboard.index', [
            'title_for_layout' => 'My Dashboard',
            'inbounds' => $inbounds,
            'outbounds' => $outbounds,
        ]);
    }

    public function loadsalestatics(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'view' => ''], 401);
        }

        $userId = $this->effectiveUserId();
        $return = ['status' => true, 'message' => 'Sorry, something went wrong.', 'view' => ''];

        $key = (string) $request->input('key', 'month');
        $report = null;

        if (Schema::hasTable('report_customers')) {
            $q = DB::table('report_customers')->where('user_id', $userId);
            if ($key === 'month') {
                $q->where('start_datetime', '>', date('Y-m-d', strtotime('-1 month')));
            } elseif ($key === 'halfyear') {
                $q->where('start_datetime', '>', date('Y-m-d', strtotime('-6 months')));
            } elseif ($key === 'year') {
                $q->where('start_datetime', '>', date('Y-m-d', strtotime('-12 months')));
            }
            // lifetime: no extra date filter
            $report = $q->selectRaw('SUM(days) as days, SUM(total_billed) as total_billed, SUM(gross_revenue) as gross_revenue')->first();
        }

        $return['view'] = view('dashboard.elements.salestatics', ['report' => $report])->render();

        return response()->json($return);
    }

    public function loadvehiclesummary(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'view' => ''], 401);
        }

        $userId = $this->effectiveUserId();
        $return = ['status' => true, 'message' => 'Sorry, something went wrong.', 'view' => ''];

        $activeVehicles = 0;
        $availableVehicles = 0;
        $bookedVehicles = 0;
        $waitlistVehicles = 0;
        $totalVehicles = 0;

        if (Schema::hasTable('vehicles')) {
            $activeVehicles = (int) DB::table('vehicles')->where('user_id', $userId)->where('status', 1)->where('waitlist', 0)->count();
            $availableVehicles = (int) DB::table('vehicles')->where('user_id', $userId)->where('status', 1)->where('waitlist', 0)->where('booked', 0)->count();
            $bookedVehicles = (int) DB::table('vehicles')->where('user_id', $userId)->where('status', 1)->where('waitlist', 0)->where('booked', 1)->count();
            $waitlistVehicles = (int) DB::table('vehicles')->where('user_id', $userId)->where('status', 1)->where('waitlist', 1)->where('booked', 0)->count();
            $totalVehicles = (int) DB::table('vehicles')->where('user_id', $userId)->count();
        }

        $return['view'] = view('dashboard.elements.vehicle_summary', compact(
            'activeVehicles',
            'availableVehicles',
            'bookedVehicles',
            'waitlistVehicles',
            'totalVehicles'
        ))->render();

        return response()->json($return);
    }

    public function loadbookingsummary(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'view' => ''], 401);
        }

        $userId = $this->effectiveUserId();
        $return = ['status' => true, 'message' => 'Sorry, something went wrong.', 'view' => ''];

        $activeBooking = 0;
        if (Schema::hasTable('cs_orders')) {
            $activeBooking = (int) DB::table('cs_orders')->where('user_id', $userId)->where('status', 1)->count();
        }

        $pendingBooking = 0;
        if (Schema::hasTable('vehicle_reservations')) {
            $pendingBooking = (int) DB::table('vehicle_reservations')->where('user_id', $userId)->where('status', 0)->count();
        }

        $completed = 0;
        if (Schema::hasTable('cs_orders')) {
            $completed = (int) DB::table('cs_orders')->where('user_id', $userId)->whereIn('status', [2, 3])->count();
        }

        $return['view'] = view('dashboard.elements.booking_summary', compact(
            'activeBooking',
            'pendingBooking',
            'completed'
        ))->render();

        return response()->json($return);
    }

    public function loadvehiclereport(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'view' => ''], 401);
        }

        $userId = $this->effectiveUserId();
        $return = ['status' => true, 'message' => 'Sorry, something went wrong.', 'view' => ''];

        $cutoff = date('Y-m-d', strtotime('+30 days'));

        $InsuranceExpVehiles = [];
        $InspectionExpVehiles = [];
        $StateinspExpVehiles = [];
        $RegnameexpExpVehiles = [];
        $WaitingforServiceVehicles = [];
        $InServiceVehicles = [];

        if (Schema::hasTable('vehicles')) {
            $InsuranceExpVehiles = $this->vehicleExpiryList($userId, 'insurance_policy_exp_date', $cutoff);
            $InspectionExpVehiles = $this->vehicleExpiryList($userId, 'inspection_exp_date', $cutoff);
            $StateinspExpVehiles = $this->vehicleExpiryList($userId, 'state_insp_exp_date', $cutoff);
            $RegnameexpExpVehiles = $this->vehicleExpiryList($userId, 'reg_name_exp_date', $cutoff);

            $WaitingforServiceVehicles = DB::table('vehicles')
                ->where('user_id', $userId)
                ->whereIn('status', [4, 5])
                ->pluck('vehicle_name', 'id')
                ->toArray();

            $InServiceVehicles = DB::table('vehicles')
                ->where('user_id', $userId)
                ->whereIn('status', [2, 3])
                ->pluck('vehicle_name', 'id')
                ->toArray();
        }

        $return['view'] = view('dashboard.elements.vehicle_report', compact(
            'InsuranceExpVehiles',
            'InspectionExpVehiles',
            'StateinspExpVehiles',
            'RegnameexpExpVehiles',
            'WaitingforServiceVehicles',
            'InServiceVehicles'
        ))->render();

        return response()->json($return);
    }

    private function effectiveUserId(): int
    {
        $parent = (int) session()->get('userParentId', 0);
        if ($parent !== 0) {
            return $parent;
        }

        return (int) session()->get('userid', 0);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function fetchTwilioPanelRows(int $userId, int $type)
    {
        return DB::table('cs_twilio_logs as l')
            ->leftJoin('cs_twilio_orders as tw_ord', 'tw_ord.id', '=', 'l.cs_twilio_order_id')
            ->leftJoin('cs_orders as co', 'co.id', '=', 'tw_ord.cs_order_id')
            ->where('l.user_id', $userId)
            ->where('l.type', $type)
            ->orderBy('l.id', 'desc')
            ->limit(10)
            ->select(['l.id', 'l.renter_phone', 'l.msg', 'l.created', 'co.increment_id as increment_id'])
            ->get();
    }

    /**
     * Vehicles with a non-empty date field where parsed date is before the cutoff (Cake parity).
     *
     * @return array<string, string> vehicle_name => raw date
     */
    private function vehicleExpiryList(int $userId, string $column, string $cutoffYmd): array
    {
        $allowed = ['insurance_policy_exp_date', 'inspection_exp_date', 'state_insp_exp_date', 'reg_name_exp_date'];
        if (!in_array($column, $allowed, true)) {
            return [];
        }

        $rows = DB::table('vehicles')
            ->where('user_id', $userId)
            ->where($column, '!=', '')
            ->whereRaw('STR_TO_DATE(`'.$column.'`, \'%m/%d/%Y\') < ?', [$cutoffYmd])
            ->select(['vehicle_name', $column])
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $name = (string) ($row->vehicle_name ?? '');
            $out[$name] = (string) ($row->{$column} ?? '');
        }

        return $out;
    }
}
