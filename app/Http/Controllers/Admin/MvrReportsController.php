<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LoadsMvrActiveBookings;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * CakePHP `MvrReportsController` — admin_* actions (unprefixed methods).
 */
class MvrReportsController extends LegacyAppController
{
    use LoadsMvrActiveBookings;

    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'mvr_reports_limit';

    private const CHECKR_STUB_MESSAGE = 'Checkr API integration not yet ported to Laravel';

    private const RESERVATION_CANCEL_STUB_MESSAGE = 'Reservation cancel / payment release logic not yet ported to Laravel';

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

        return back()->with('error', self::CHECKR_STUB_MESSAGE);
    }

    public function report(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return response(self::CHECKR_STUB_MESSAGE, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function vehiclereport(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return response(self::CHECKR_STUB_MESSAGE, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
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
            'formatMvrDt' => fn (?string $v, ?string $tz) => $this->mvrFormatDateTime($v, $tz),
        ]);
    }

    public function cancelMvrBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Sorry, booking not found, please refresh your page and try again.',
        ]);
    }

    public function cancelMvrResevationBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized', 'result' => []], 403);
        }

        return response()->json([
            'status' => 'error',
            'message' => self::RESERVATION_CANCEL_STUB_MESSAGE,
            'result' => [],
        ]);
    }

    public function requestagain(Request $request, ?string $userid = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return back()->with('error', self::CHECKR_STUB_MESSAGE);
    }

    public function ajaxrequestagain(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Invalid Request']);
        }

        return response()->json([
            'status' => false,
            'message' => self::CHECKR_STUB_MESSAGE,
        ]);
    }
}
