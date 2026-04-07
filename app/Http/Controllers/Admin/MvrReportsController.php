<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\DriverBackgroundReportTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\User;
use App\Models\Legacy\UserReport;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleReservation;
use Illuminate\Http\Request;

class MvrReportsController extends LegacyAppController
{
    use DriverBackgroundReportTrait;

    protected bool $shouldLoadLegacyModules = true;

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "MvrReports::{$action} pending migration.",
            'result' => [],
        ]);
    }

    // ─── admin_index ──────────────────────────────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $searchData = $request->input('Search', []);
        $namedData  = $request->query();

        $value    = $namedData['keyword']  ?? $searchData['keyword']  ?? '';
        $searchin = $namedData['searchin'] ?? $searchData['searchin'] ?? '';

        $query = User::query()
            ->from('users as User')
            ->leftJoin('user_reports as UserReport', 'UserReport.user_id', '=', 'User.id')
            ->select('User.*', 'UserReport.*')
            ->where('User.is_admin', 0);

        if ($value !== '' && !empty($searchin)) {
            $v = strip_tags($value);
            $query->where("User.{$searchin}", 'LIKE', "%{$v}%");
        }

        $sessionLimitKey  = 'MvrReports_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $users = $query->orderBy('User.id', 'DESC')->paginate($limit)->withQueryString();

        $viewData = [
            'title_for_layout' => 'User MVR Reports',
            'keyword'          => $value,
            'searchin'         => $searchin,
            'users'            => $users,
        ];

        if ($request->ajax()) {
            return view('admin.mvr_reports.elements.index_ajax', $viewData);
        }

        return view('admin.mvr_reports.index', $viewData);
    }

    // ─── admin_checkr_status ─────────────────────────────────────────────────
    public function admin_checkr_status(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId     = base64_decode($id);
        $userReport = UserReport::where('user_id', $userId)->first();

        if (empty($userReport)) {
            $result = $this->addCandidateToDriverBackgroundReport($userId);
            $flash  = $result['status'] ? 'success' : 'error';
            return redirect()->back()->with($flash, $result['message']);
        }

        if ($userReport->status && !empty($userReport->checkr_reportid)) {
            $result = $this->pullBackgroundReport($userId);
            $flash  = $result['status'] ? 'success' : 'error';
            return redirect()->back()->with($flash, $result['message']);
        }

        if (!empty($userReport) && $userReport->status == 0) {
            $result = $this->createBackgroundReport($userId);
            $flash  = $result['status'] ? 'success' : 'error';
            return redirect()->back()->with($flash, $result['message']);
        }

        return redirect()->back()->with('error', 'User Report is not ready');
    }

    // ─── admin_requestagain ───────────────────────────────────────────────────
    public function admin_requestagain(Request $request, $userid)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId     = base64_decode($userid);
        $userReport = UserReport::where('user_id', $userId)->first();

        if (!empty($userReport)) {
            $result = $this->addCandidateToDriverBackgroundReport($userId);
            if ($result['status']) {
                UserReport::where('user_id', $userId)->update([
                    'checkr_reportid'         => null,
                    'motor_vehicle_report_id' => null,
                ]);
                return redirect()->back()->with('success', 'User is added to Checkr API for processing');
            }
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back();
    }

    // ─── admin_ajaxrequestagain (AJAX) ────────────────────────────────────────
    public function admin_ajaxrequestagain(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Invalid Request']);
        }

        $userId     = base64_decode($request->input('userid', ''));
        $userReport = UserReport::where('user_id', $userId)->first();

        if (empty($userReport)) {
            return response()->json(['status' => false, 'message' => 'Sorry existing records could not be found, so we cant process your request.']);
        }

        $result = $this->addCandidateToDriverBackgroundReport($userId);

        if ($result['status']) {
            UserReport::where('user_id', $userId)->update([
                'checkr_reportid'         => null,
                'motor_vehicle_report_id' => null,
                'status'                  => 0,
                'created_at'              => now(),
            ]);
            return response()->json(['status' => true, 'message' => 'User is added to Checkr API for processing']);
        }

        return response()->json(['status' => false, 'message' => $result['message']]);
    }

    // ─── admin_loadactivebooking ──────────────────────────────────────────────
    public function admin_loadactivebooking(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = base64_decode($request->input('userid', ''));

        $bookings = CsOrder::where('renter_id', $userId)
            ->whereIn('status', [0, 1])
            ->get();

        $reservations = VehicleReservation::query()
            ->from('vehicle_reservations as VehicleReservation')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->select('VehicleReservation.*', 'Vehicle.vehicle_name')
            ->where('VehicleReservation.renter_id', $userId)
            ->where('VehicleReservation.status', 0)
            ->get();

        return view('admin.mvr_reports.load_active_booking', compact('bookings', 'reservations'));
    }

    // ─── admin_cancelMvrBooking (DISABLED — preserved as-is) ─────────────────
    public function admin_cancelMvrBooking(Request $request)
    {
        // Intentionally disabled — cancel action is locked in production
        return response()->json([
            'status'  => 'error',
            'message' => 'Sorry, booking not found, please refresh your page and try again.',
        ]);
    }

    // ─── admin_cancelMvrResevationBooking ─────────────────────────────────────
    public function admin_cancelMvrResevationBooking(Request $request)
    {
        $return    = ['status' => 'error', 'message' => 'Sorry, pending booking not found, please refresh your page and try again.', 'result' => []];
        $bookingId = base64_decode($request->input('bookingid', ''));

        if (empty($bookingId)) {
            return response()->json($return);
        }

        VehicleReservation::where('id', $bookingId)->update(['status' => 2]);
        $reservation = VehicleReservation::find($bookingId);

        if ($reservation) {
            Vehicle::where('id', $reservation->vehicle_id)->update(['booked' => 0]);
        }

        // Release deposit/initial fee payments via PaymentProcessor
        $processorClass = '\\App\\Lib\\Legacy\\PaymentProcessor';
        $walletClass    = '\\App\\Models\\Legacy\\CsWallet';
        $paymentClass   = '\\App\\Models\\Legacy\\CsReservationPayment';

        if (class_exists($paymentClass)) {
            $paymentModel    = new $paymentClass();
            $deposits        = $paymentModel->getDepositTransaction($bookingId);
            $initialFees     = $paymentModel->getInitialFeeTransaction($bookingId);
            $processor       = class_exists($processorClass) ? new $processorClass() : null;

            foreach (array_merge($deposits ?? [], $initialFees ?? []) as $txn) {
                if ($txn['txntype'] === 'C' && class_exists($walletClass)) {
                    (new $walletClass())->addBalance(
                        $txn['amount'],
                        $reservation->renter_id,
                        $txn['transaction_id'],
                        'refunded from pending booking',
                        '',
                        $txn['created']
                    );
                } elseif ($processor) {
                    $processor->ReservationReleaseAuthorizePayment($txn, $reservation->user_id ?? null);
                }

                if (class_exists($paymentClass)) {
                    $paymentModel->where('id', $txn['id'])->update(['status' => 2]);
                }
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Your request processed successfully',
            'orderid' => $bookingId,
        ]);
    }

    // Cake action parity wrappers
    public function admin_report(Request $request) { return $this->admin_index($request); }
    public function admin_vehiclereport(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function report(Request $request) { return $this->admin_report($request); }
    public function vehiclereport(Request $request) { return $this->admin_vehiclereport($request); }
    public function checkr_status(Request $request, $id) { return $this->admin_checkr_status($request, $id); }
    public function loadactivebooking(Request $request) { return $this->admin_loadactivebooking($request); }
    public function cancelMvrBooking(Request $request) { return $this->admin_cancelMvrBooking($request); }
    public function cancelMvrResevationBooking(Request $request) { return $this->admin_cancelMvrResevationBooking($request); }
}
