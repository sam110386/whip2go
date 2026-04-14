<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class SmslogsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'smslogs_limit';

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!Schema::hasTable('cs_twilio_logs')) {
            return view('admin.smslogs.index', [
                'title_for_layout' => 'SMS Logs',
                'smslogs' => null,
                'keyword' => '',
                'date_from' => '',
                'date_to' => '',
                'status_type' => '',
            ]);
        }

        $keyword = trim((string) $request->input('keyword', $request->input('Search.keyword', '')));
        $dateFromIn = trim((string) $request->input('date_from', $request->input('Search.date_from', '')));
        $dateToIn = trim((string) $request->input('date_to', $request->input('Search.date_to', '')));
        $statusType = trim((string) $request->input('status_type', $request->input('Search.status_type', '')));

        $dateFrom = '';
        $dateTo = '';
        if ($dateFromIn !== '') {
            try {
                $dateFrom = Carbon::createFromFormat('m/d/Y', $dateFromIn)->format('Y-m-d');
            } catch (\Throwable $e) {
                $dateFrom = '';
            }
        }
        if ($dateToIn !== '') {
            try {
                $dateTo = Carbon::createFromFormat('m/d/Y', $dateToIn)->format('Y-m-d');
            } catch (\Throwable $e) {
                $dateTo = '';
            }
        }
        if ($dateFrom !== '' && $dateTo === '') {
            $dateTo = date('Y-m-d');
        }

        $limit = $this->resolveLimit($request);
        $request->merge(['Record' => ['limit' => $limit]]);

        $q = DB::table('cs_twilio_logs');
        if ($dateFrom !== '') {
            $q->where('created', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $q->where('created', '<=', $dateTo . ' 23:59:59');
        }
        if ($statusType !== '' && ctype_digit($statusType)) {
            $q->where('type', (int) $statusType);
        }
        if ($keyword !== '') {
            $q->where('renter_phone', 'like', '%' . addcslashes($keyword, '%_\\') . '%');
        }

        $smslogs = $q->orderByDesc('id')->paginate($limit)->appends([
            'keyword' => $keyword,
            'date_from' => $dateFromIn,
            'date_to' => $dateToIn,
            'status_type' => $statusType,
            'Record' => ['limit' => $limit],
        ]);

        return view('admin.smslogs.index', [
            'title_for_layout' => 'SMS Logs',
            'smslogs' => $smslogs,
            'keyword' => $keyword,
            'date_from' => $dateFromIn,
            'date_to' => $dateToIn,
            'status_type' => $statusType,
            'limit' => $limit,
        ]);
    }

    /**
     * HTML fragment for admin colorbox (POST from `admin_booking.js`).
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function details(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decoded = $this->decodeId($id);
        $smslog = null;
        if ($decoded !== null && Schema::hasTable('cs_twilio_logs')) {
            $smslog = DB::table('cs_twilio_logs')->where('id', $decoded)->first();
        }

        return view('admin.smslogs.details_modal', ['smslog' => $smslog]);
    }

    public function delete(Request $request, $id = null): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'msg' => 'Unauthorized'], 401);
        }

        $decoded = $this->decodeId($id);
        if ($decoded === null || !Schema::hasTable('cs_twilio_logs')) {
            return response()->json(['status' => 'error', 'msg' => 'Something went wrong']);
        }

        DB::table('cs_twilio_logs')->where('id', $decoded)->delete();

        return response()->json([
            'status' => 'success',
            'msg' => 'Record deleted successfully',
            'recordid' => $decoded,
        ]);
    }

    private function resolveLimit(Request $request): int
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
}
