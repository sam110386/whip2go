<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LoadsMvrActiveBookings;
use App\Http\Controllers\Traits\DriverBackgroundReport;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\UserReport;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\CsReservationPayment;
use App\Models\Legacy\CsWallet;
use App\Services\Legacy\CheckrApiClient;
use App\Services\Legacy\PaymentProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * CakePHP `MvrReportsController` — admin_* actions (unprefixed methods).
 */
class MvrReportsController extends LegacyAppController
{
    use LoadsMvrActiveBookings, DriverBackgroundReport;

    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'mvr_reports_limit';

    protected function adminBasePath(): string
    {
        return '/admin/mvr_reports';
    }

    protected function resolveLimit(Request $request): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int) $fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put(self::SESSION_LIMIT_KEY, $lim);

                return $lim;
            }
        }
        $sess = (int) session()->get(self::SESSION_LIMIT_KEY, 0);
        if (in_array($sess, $allowed, true)) {
            return $sess;
        }

        return 25;
    }

    /**
     * Cake `admin_index`: users + user_reports, search, paginate; AJAX returns listing fragment.
     */
    public function index(Request $request): View|Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = $this->resolveLimit($request);

        $keyword = trim((string) $request->input('Search.keyword', $request->query('keyword', '')));
        $searchin = (string) $request->input('Search.searchin', $request->query('searchin', ''));

        if ($request->isMethod('post') && $request->has('Search')) {
            return redirect()->to($this->adminBasePath() . '/index?' . http_build_query([
                'keyword' => $keyword,
                'searchin' => $searchin,
                'Record' => ['limit' => $limit],
            ]));
        }

        $allowedColumns = ['first_name', 'last_name', 'contact_number'];
        $searchColumn = in_array($searchin, $allowedColumns, true) ? $searchin : '';

        $usersPaginator = null;
        if (Schema::hasTable('users') && Schema::hasTable('user_reports')) {
            $base = DB::table('users as u')
                ->leftJoin('user_reports as ur', 'ur.user_id', '=', 'u.id')
                ->where('u.is_admin', 0);

            if ($searchColumn !== '' && $keyword !== '') {
                $base->where('u.' . $searchColumn, 'like', '%' . $keyword . '%');
            }

            $usersPaginator = $base
                ->select([
                    'u.id',
                    'u.first_name',
                    'u.last_name',
                    'u.email',
                    'u.contact_number',
                    'ur.checkr_reportid',
                    'ur.motor_vehicle_report_id',
                ])
                ->orderByDesc('u.id')
                ->paginate($limit)
                ->appends([
                    'keyword' => $keyword,
                    'searchin' => $searchin,
                    'Record' => ['limit' => $limit],
                ]);
        }

        $viewData = [
            'title_for_layout' => 'User MVR Reports',
            'users' => $usersPaginator,
            'keyword' => $keyword,
            'searchin' => $searchin,
            'limit' => $limit,
            'basePath' => $this->adminBasePath(),
        ];

        if ($request->ajax()) {
            return response()->view('admin.mvr_reports.partials.listing', $viewData);
        }

        return view('admin.mvr_reports.index', $viewData);
    }

    public function checkr_status(Request $request, ?string $id = null): RedirectResponse
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
            // If user is not added to Checkr API yet or report not requested
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

    public function report(Request $request): Response|RedirectResponse
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

    public function vehiclereport(Request $request): Response|RedirectResponse
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

    public function loadactivebooking(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $rawUserId = $request->input('userid', $request->input('data.userid'));
        $userId = $this->decodeId(is_string($rawUserId) ? $rawUserId : (string) $rawUserId);
        if ($userId === null) {
            $userId = 0;
        }

        [$bookings, $reservations] = $this->mvrActiveBookingsForRenter($userId);

        return view('admin.mvr_reports.loadactivebooking', [
            'bookings' => $bookings,
            'reservations' => $reservations,
            'formatMvrDt' => fn(?string $v, ?string $tz) => $this->mvrFormatDateTime($v, $tz),
        ]);
    }

    public function cancelMvrBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $bookingId = $this->decodeId($request->input('bookingid'));
        return response()->json([
            'status' => 'error',
            'message' => 'Sorry, booking not found, please refresh your page and try again.',
        ]);
    }

    public function cancelMvrResevationBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $bookingId = $this->decodeId($request->input('bookingid'));
        if (empty($bookingId)) {
            return response()->json(['status' => 'error', 'message' => "Sorry, pending booking not found, please refresh your page and try again."]);
        }

        try {
            DB::beginTransaction();

            $reservation = VehicleReservation::find($bookingId);
            if (!$reservation) {
                return response()->json(['status' => 'error', 'message' => "Reservation not found."]);
            }

            // Update reservation as canceled
            $reservation->update(['status' => 2]);

            // Release vehicle
            Vehicle::where('id', $reservation->vehicle_id)->update(['booked' => 0]);

            $paymentProcessor = new PaymentProcessor();
            $csWallet = new CsWallet();

            // Release/refund deposits transactions
            $deposits = CsReservationPayment::where('reservation_id', $bookingId)->where('type', 1)->where('status', 1)->get();
            foreach ($deposits as $deposit) {
                if ($deposit->txntype == 'C') {
                    $csWallet->addBalance($deposit->amount, $reservation->renter_id, $deposit->transaction_id, "deposit is refunded from pending booking", $bookingId, $deposit->created);
                } else {
                    $paymentProcessor->ReservationReleaseAuthorizePayment($deposit->toArray(), $reservation->user_id);
                }
                $deposit->update(['status' => 2]);
            }

            // Release/refund initial fee transactions
            $initialFees = CsReservationPayment::where('reservation_id', $bookingId)->where('type', 3)->where('status', 1)->get();
            foreach ($initialFees as $initialFee) {
                if ($initialFee->txntype == 'C') {
                    $csWallet->addBalance($initialFee->amount, $reservation->renter_id, $initialFee->transaction_id, "initial fee is refunded from pending booking", $bookingId, $initialFee->created);
                } else {
                    $paymentProcessor->ReservationReleaseAuthorizePayment($initialFee->toArray(), $reservation->user_id);
                }
                $initialFee->update(['status' => 2]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => "Your request processed successfully", "orderid" => $bookingId]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("cancelMvrResevationBooking error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => "An error occurred while processing your request: " . $e->getMessage()]);
        }
    }

    public function requestagain(Request $request, ?string $userid = null): RedirectResponse
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

    public function ajaxrequestagain(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Invalid Request']);
        }

        $rawUserId = $request->input('userid');
        $userId = $this->decodeId(is_string($rawUserId) ? $rawUserId : (string) $rawUserId);

        if (!$userId) {
            return response()->json(['status' => false, 'message' => 'Invalid User ID']);
        }

        $userReport = UserReport::where('user_id', $userId)->first();
        if (!$userReport) {
            return response()->json(['status' => false, 'message' => "Sorry existing records could not be found, so we cant process your request."]);
        }

        $checkrStatus = $this->addCandidateToDriverBackgroundReport($userId);
        if ($checkrStatus['status']) {
            $userReport->update([
                'checkr_reportid' => null,
                'motor_vehicle_report_id' => null,
                'status' => 0,
                'created' => now()->toDateTimeString()
            ]);
            return response()->json(['status' => true, 'message' => "User is added to Checkr API for processing"]);
        } else {
            return response()->json(['status' => false, 'message' => $checkrStatus['message']]);
        }
    }
}
