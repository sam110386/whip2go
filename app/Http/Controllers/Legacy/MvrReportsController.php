<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Concerns\LoadsMvrActiveBookings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CakePHP `MvrReportsController` — user-facing actions (non-admin prefix).
 */
class MvrReportsController extends LegacyAppController
{
    use LoadsMvrActiveBookings;

    protected bool $shouldLoadLegacyModules = true;

    private const CHECKR_STUB_MESSAGE = 'Checkr API integration not yet ported to Laravel';

    private const RESERVATION_CANCEL_STUB_MESSAGE = 'Reservation cancel / payment release logic not yet ported to Laravel';

    private function legacySessionUserId(): int
    {
        return (int) session()->get('userid', 0);
    }

    public function checkr_status(Request $request, ?string $id = null): RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $decoded = $this->decodeId((string) $id);
        if ($decoded === null || $decoded !== $this->legacySessionUserId()) {
            return back()->with('error', 'You are not authorized to perform this action.');
        }

        return back()->with('error', self::CHECKR_STUB_MESSAGE);
    }

    public function report(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        return response(self::CHECKR_STUB_MESSAGE, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function vehiclereport(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        return response(self::CHECKR_STUB_MESSAGE, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function loadactivebooking(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $rawUserId = $request->input('userid', $request->input('data.userid'));
        $userId = $this->decodeId(is_string($rawUserId) ? $rawUserId : (string) $rawUserId);
        if ($userId === null || $userId !== $this->legacySessionUserId()) {
            $bookings = collect();
            $reservations = collect();
        } else {
            [$bookings, $reservations] = $this->mvrActiveBookingsForRenter($userId);
        }

        return view('mvr_reports.loadactivebooking', [
            'bookings' => $bookings,
            'reservations' => $reservations,
            'formatMvrDt' => fn (?string $v, ?string $tz) => $this->mvrFormatDateTime($v, $tz),
        ]);
    }

    public function cancelMvrBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Sorry, booking not found, please refresh your page and try again.',
        ]);
    }

    public function cancelMvrResevationBooking(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized', 'result' => []], 403);
        }

        return response()->json([
            'status' => 'error',
            'message' => self::RESERVATION_CANCEL_STUB_MESSAGE,
            'result' => [],
        ]);
    }
}
