<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\User;
use App\Models\Legacy\UserReport;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\CsReservationPayment;
use App\Models\Legacy\CsWallet;
use App\Services\Legacy\CheckrApiClient;
use App\Services\Legacy\PaymentProcessor;
use App\Http\Controllers\Traits\DriverBackgroundReport;
use App\Http\Controllers\Legacy\LegacyAppController;


class MvrReportsController extends LegacyAppController
{
    use DriverBackgroundReport;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'User MVR Reports';
        $sessionLimitName = 'mvr_reports_limit';
        $keyword = $request->input('Search.keyword', $request->query('keyword', ''));
        $searchin = $request->input('Search.searchin', $request->query('searchin', ''));

        $query = User::where('is_admin', 0)->with('report');

        if (!empty($keyword) && !empty($searchin)) {
            $query->where("{$searchin}", 'LIKE', "%{$keyword}%");
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            $request->session()->put($sessionLimitName, $limit);
        } else {
            $limit = $request->session()->get($sessionLimitName, $this->recordsPerPage);
        }

        $users = $query->orderBy('id', 'desc')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.mvr_reports.elements.index', compact('users', 'keyword', 'searchin', 'limit'));
        }

        return view('admin.mvr_reports.index', compact('title', 'users', 'keyword', 'searchin', 'limit'));
    }
    public function checkr_status($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = $this->decodeId($id);

        if (!$userId) {
            return back()->with('error', 'Invalid User ID');
        }

        $userReport = UserReport::where('user_id', $userId)->first();

        if (!$userReport) {
            $checkrStatus = $this->addCandidateToDriverBackgroundReport($userId);

            if ($checkrStatus['status']) {
                return back()->with('success', "User is added to Checkr API for processing");
            } else {
                return back()->with('error', $checkrStatus['message']);
            }

        } elseif ($userReport->status && !empty($userReport->checkr_reportid)) {
            $report = $this->pullBackgroundReport($userId);

            if ($report['status']) {
                return back()->with('success', "User Report is Ready");
            } else {
                return back()->with('error', $report['message'] ?? 'Report not ready');
            }

        } elseif ($userReport && $userReport->status == 0) {
            $checkrReport = $this->createBackgroundReport($userId);

            if ($checkrReport['status']) {
                return back()->with('success', "User Report is requested");
            } else {
                return back()->with('error', $checkrReport['message']);
            }

        } else {
            return back()->with('error', "User Report is not ready");
        }
    }
    public function report(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $reportId = $request->input('reportid');

        if (!$reportId) {
            return back()->with('error', 'Report ID missing');
        }

        $checkrApi = new CheckrApiClient();
        $report = $checkrApi->getReport($reportId);

        return response('<pre>' . print_r($report, true) . '</pre>', 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
    public function vehiclereport(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $reportId = $request->input('reportid');

        if (!$reportId) {
            return back()->with('error', 'Report ID missing');
        }

        $checkrApi = new CheckrApiClient();
        $report = $checkrApi->getMotorVehicleReport($reportId);

        return response('<pre>' . print_r($report, true) . '</pre>', 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
    public function loadactivebooking(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userid = $this->decodeId($request->input('userid'));

        $bookings = CsOrder::where('renter_id', $userid)
            ->whereIn('status', [0, 1])
            ->orderByDesc('id')
            ->get();

        $reservations = VehicleReservation::with('vehicle:id,vehicle_name')
            ->where('renter_id', $userid)
            ->where('status', 0)
            ->orderByDesc('id')
            ->get();

        return view('admin.mvr_reports.loadactivebooking', compact('bookings', 'reservations'));
    }
    public function cancelMvrBooking(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $bookingId = $this->decodeId($request->input('bookingid'));

        return response()->json([
            'status' => 'error',
            'message' => 'Sorry, booking not found, please refresh your page and try again.',
        ]);
    }
    public function cancelMvrResevationBooking(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $bookingId = $this->decodeId($request->input('bookingid'));

        if (empty($bookingId)) {
            return response()->json([
                'status' => 'error',
                'message' => "Sorry, pending booking not found, please refresh your page and try again."
            ]);
        }

        try {

            DB::beginTransaction();

            $reservation = VehicleReservation::find($bookingId);

            if (!$reservation) {
                return response()->json(['status' => 'error', 'message' => "Reservation not found."]);
            }

            $reservation->update(['status' => 2]);
            Vehicle::where('id', $reservation->vehicle_id)->update(['booked' => 0]);
            $paymentProcessor = new PaymentProcessor();
            $csWallet = new CsWallet();
            $deposits = CsReservationPayment::getDepositTransaction($bookingId);

            foreach ($deposits as $deposit) {
                if ($deposit->txntype == 'C') {
                    $csWallet->addBalance(
                        $deposit->amount,
                        $reservation->renter_id,
                        $deposit->transaction_id,
                        "deposit is refunded from pending booking",
                        $bookingId,
                        $deposit->created
                    );
                } else {
                    $paymentProcessor->ReservationReleaseAuthorizePayment($deposit->toArray(), $reservation->user_id);
                }
                $deposit->update(['status' => 2]);
            }

            $initialFees = CsReservationPayment::where('reservation_id', $bookingId)->where('type', 3)->where('status', 1)->get();
            foreach ($initialFees as $initialFee) {
                if ($initialFee->txntype == 'C') {
                    $csWallet->addBalance(
                        $initialFee->amount,
                        $reservation->renter_id,
                        $initialFee->transaction_id,
                        "initial fee is refunded from pending booking",
                        $bookingId,
                        $initialFee->created
                    );
                } else {
                    $paymentProcessor->ReservationReleaseAuthorizePayment($initialFee->toArray(), $reservation->user_id);
                }
                $initialFee->update(['status' => 2]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => "Your request processed successfully",
                "orderid" => $bookingId
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("cancelMvrResevationBooking error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => "An error occurred while processing your request: " . $e->getMessage()]);
        }
    }
    public function requestagain($userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = $this->decodeId($userid);

        if (!$userId) {
            return back()->with('error', 'Invalid User ID');
        }

        $userReport = UserReport::where('user_id', $userId)->first();

        if ($userReport) {
            $checkrStatus = $this->addCandidateToDriverBackgroundReport($userId);

            if ($checkrStatus['status']) {
                $userReport->update([
                    'checkr_reportid' => null,
                    'motor_vehicle_report_id' => null,
                    'status' => 0
                ]);
                return back()->with('success', "User is added to Checkr API for processing");
            } else {
                return back()->with('error', $checkrStatus['message']);
            }

        }

        return back()->with('error', 'Report record not found');
    }
    public function ajaxrequestagain(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Invalid Request']);
        }

        $userId = $this->decodeId($request->input('userid'));

        if (!$userId) {
            return response()->json(['status' => false, 'message' => 'Invalid User ID']);
        }

        $userReport = UserReport::where('user_id', $userId)->first();
        if (!$userReport) {
            return response()->json([
                'status' => false,
                'message' => "Sorry existing records could not be found, so we cant process your request."
            ]);
        }

        $checkrStatus = $this->addCandidateToDriverBackgroundReport($userId);

        if ($checkrStatus['status']) {
            $userReport->update([
                'checkr_reportid' => null,
                'motor_vehicle_report_id' => null,
                'status' => 0,
                'created' => now()->toDateTimeString()
            ]);
            return response()->json([
                'status' => true,
                'message' => "User is added to Checkr API for processing"
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => $checkrStatus['message']
            ]);
        }
    }
}
