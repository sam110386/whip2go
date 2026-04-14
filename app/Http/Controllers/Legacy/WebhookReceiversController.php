<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Cake `WebhookReceiversController` — Checkr, Geotab, Intercom webhooks and MVR reservation hooks.
 */
class WebhookReceiversController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private string $reportStatusForLog = '';

    private const INTERCOM_WEBHOOK_TOKEN = 'GJHGJHGHG788768UYT';

    public function CheckrRecieve(Request $request)
    {
        $rawInput = $request->getContent();
        $logLine = "\n" . date('Y-m-d H:i:s') . '=' . $rawInput;
        file_put_contents(
            storage_path('logs/checkr_' . date('Y-m-d') . '.log'),
            $logLine,
            FILE_APPEND | LOCK_EX
        );

        $postData = json_decode($rawInput, true);
        if ($rawInput === '' || ! is_array($postData)) {
            return response('finished', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $type = $postData['type'] ?? null;

        if ($type === 'candidate.created') {
            Log::info('Checkr webhook candidate.created (no-op stub)', ['payload_keys' => array_keys($postData)]);
        }

        if ($type === 'report.created' && Schema::hasTable('user_reports')) {
            $candidateId = $postData['data']['object']['candidate_id'] ?? null;
            $reportId = $postData['data']['object']['id'] ?? null;
            if ($candidateId !== null && $candidateId !== '') {
                $obj = $postData['data']['object'] ?? [];
                $motorVehicleReportId = $obj['motor_vehicle_report_id']
                    ?? (! empty($obj['international_motor_vehicle_report_ids'][0])
                        ? $obj['international_motor_vehicle_report_ids'][0]
                        : '');
                DB::table('user_reports')->where('checkr_id', (string) $candidateId)->update([
                    'checkr_reportid' => (string) $reportId,
                    'motor_vehicle_report_id' => (string) $motorVehicleReportId,
                    'status' => 1,
                ]);
            }
        }

        if ($type === 'report.completed' && Schema::hasTable('user_reports')) {
            $candidateId = $postData['data']['object']['candidate_id'] ?? null;
            if ($candidateId !== null && $candidateId !== '') {
                $reportStatsu = (string) ($postData['data']['object']['result'] ?? '');
                $this->reportStatusForLog = $reportStatsu;
                $reportPayload = json_encode($postData['data']['object'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE);

                DB::table('user_reports')->where('checkr_id', (string) $candidateId)->update([
                    'status' => 2,
                    'report' => $reportPayload,
                ]);

                $userReport = DB::table('user_reports')->where('checkr_id', (string) $candidateId)->first();

                if ($userReport !== null && Schema::hasTable('users')) {
                    $uid = (int) $userReport->user_id;
                    $lower = strtolower($reportStatsu);
                    if ($lower === 'clear') {
                        DB::table('users')->where('id', $uid)->update(['checkr_status' => 1]);
                        $this->activateBooking($uid, 1);
                    } elseif ($lower === 'consider') {
                        DB::table('users')->where('id', $uid)->update(['checkr_status' => 3]);
                        $this->cancelBooking($uid, 3);
                    } elseif ($lower !== 'pending' && $lower !== '') {
                        DB::table('users')->where('id', $uid)->update(['checkr_status' => 4]);
                        $this->cancelBooking($uid, 4);
                    }
                    $this->notifyToAdmin($uid, $reportStatsu);
                }
            }
        }

        if ($type === 'report.suspended' && Schema::hasTable('user_reports')) {
            $candidateId = $postData['data']['object']['candidate_id'] ?? null;
            if ($candidateId !== null && $candidateId !== '') {
                DB::table('user_reports')->where('checkr_id', (string) $candidateId)->update(['status' => 3]);
                $userReport = DB::table('user_reports')->where('checkr_id', (string) $candidateId)->first();
                if ($userReport !== null && Schema::hasTable('users')) {
                    $uid = (int) $userReport->user_id;
                    DB::table('users')->where('id', $uid)->update(['checkr_status' => 4]);
                    $this->cancelBooking($uid, 4);
                }
            }
        }

        if ($type === 'report.disputed' && Schema::hasTable('user_reports')) {
            $candidateId = $postData['data']['object']['candidate_id'] ?? null;
            if ($candidateId !== null && $candidateId !== '') {
                DB::table('user_reports')->where('checkr_id', (string) $candidateId)->update(['status' => 4]);
                $userReport = DB::table('user_reports')->where('checkr_id', (string) $candidateId)->first();
                if ($userReport !== null && Schema::hasTable('users')) {
                    $uid = (int) $userReport->user_id;
                    DB::table('users')->where('id', $uid)->update(['checkr_status' => 4]);
                    $this->cancelBooking($uid, 4);
                }
            }
        }

        return response('finished', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function cancelBooking(int $renterid, int $checkr_status): void
    {
        $this->cancelReservation($renterid, $checkr_status);
        Log::info('Checkr cancelBooking: payment/refund stub', ['renter_id' => $renterid, 'checkr_status' => $checkr_status]);
    }

    public function cancelReservation(int $renterid, int $checkr_status): void
    {
        if (! Schema::hasTable('vehicle_reservations')) {
            return;
        }

        $reservation = DB::table('vehicle_reservations')
            ->where('renter_id', $renterid)
            ->where('status', 0)
            ->orderByDesc('id')
            ->first(['id']);

        if ($reservation === null) {
            return;
        }

        DB::table('vehicle_reservations')->where('id', (int) $reservation->id)->update([
            'checkr_status' => $checkr_status,
            'modified' => now()->toDateTimeString(),
        ]);

        if (Schema::hasTable('vehicle_reservation_logs')) {
            DB::table('vehicle_reservation_logs')->insert([
                'reservation_id' => (int) $reservation->id,
                'user_id' => 0,
                'status' => 10,
                'note' => 'Status changed by MVR webhook, MVR status=' . $this->reportStatusForLog,
                'created' => now()->toDateTimeString(),
                'updated' => now()->toDateTimeString(),
            ]);
        }
    }

    public function activateBooking(int $renterid, int $checkr_status): void
    {
        $this->activateVehicleReservation($renterid, $checkr_status);
        Log::info('Checkr activateBooking: payment capture stub', ['renter_id' => $renterid, 'checkr_status' => $checkr_status]);
    }

    public function activateVehicleReservation(int $renterid, int $checkr_status): void
    {
        if (! Schema::hasTable('vehicle_reservations')) {
            return;
        }

        $reservation = DB::table('vehicle_reservations')
            ->where('renter_id', $renterid)
            ->where('status', 0)
            ->orderByDesc('id')
            ->first(['id']);

        if ($reservation === null) {
            return;
        }

        DB::table('vehicle_reservations')->where('id', (int) $reservation->id)->update([
            'checkr_status' => $checkr_status,
            'modified' => now()->toDateTimeString(),
        ]);

        if (Schema::hasTable('vehicle_reservation_logs')) {
            DB::table('vehicle_reservation_logs')->insert([
                'reservation_id' => (int) $reservation->id,
                'user_id' => 0,
                'status' => 10,
                'note' => 'Status changed by MVR webhook, MVR status=' . $this->reportStatusForLog,
                'created' => now()->toDateTimeString(),
                'updated' => now()->toDateTimeString(),
            ]);
        }
    }

    public function notifyToAdmin(int $renterid, string $status): void
    {
        if (! Schema::hasTable('cs_orders')) {
            return;
        }

        $order = DB::table('cs_orders')
            ->where('renter_id', $renterid)
            ->orderByDesc('id')
            ->first(['increment_id']);

        if ($order === null) {
            return;
        }

        Log::info('Checkr notifyToAdmin stub', [
            'renter_id' => $renterid,
            'increment_id' => $order->increment_id ?? null,
            'checkr_status' => $status,
        ]);
    }

    public function geotab(Request $request)
    {
        $postData = $request->getContent();
        file_put_contents(
            storage_path('logs/geotab_webhook' . date('Y-m-d') . '.log'),
            "\n" . date('Y-m-d H:i:s') . '=' . $postData,
            FILE_APPEND | LOCK_EX
        );

        return response('', 204);
    }

    public function intercomReciever(Request $request)
    {
        $authToken = $this->intercomAuthTokenFromRequest($request);
        $raw = $request->getContent();
        $postData = json_decode($raw, true);

        if ($authToken !== self::INTERCOM_WEBHOOK_TOKEN || empty($postData) || (int) ($postData['form_id'] ?? 0) !== 1) {
            return response('sorry, wrong attempt', 403)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $email = isset($postData['3']) ? (string) $postData['3'] : '';
        $phone = isset($postData['4']) ? (string) $postData['4'] : '';
        if ($email === '' || $phone === '') {
            return response('sorry, wrong attempt', 403)->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        Log::info('Intercom WordPress webhook stub (sync not ported)', [
            'keys' => array_keys($postData),
        ]);

        return response('finished', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function intercomLeadReciever(Request $request)
    {
        $authToken = $this->intercomAuthTokenFromRequest($request);
        $postData = json_decode($request->getContent(), true);

        if ($authToken !== self::INTERCOM_WEBHOOK_TOKEN || empty($postData)) {
            return response()->json(['status' => false, 'message' => 'sorry, wrong attempt']);
        }

        if (empty($postData['email'])) {
            return response()->json(['status' => false, 'message' => 'sorry, wrong attempt']);
        }

        Log::info('Intercom lead webhook stub (create/search not ported)', [
            'email' => $postData['email'],
            'name' => $postData['name'] ?? null,
        ]);

        return response()->json(['status' => true, 'message' => 'success']);
    }

    private function intercomAuthTokenFromRequest(Request $request): string
    {
        $authorization = (string) $request->header('Authorization', $request->header('authorization', ''));

        return trim(str_ireplace('Basic', '', $authorization));
    }
}
