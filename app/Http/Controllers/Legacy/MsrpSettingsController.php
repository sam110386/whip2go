<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cake `MsrpSettingsController` — dealer Path-to-Ownership / MSRP banding and equity.
 *
 * Parity notes:
 * - POST bodies use Cake `data[...]` naming from legacy forms.
 * - `syncDayRentalToVehicle` mirrors `DynamicFare::calculateDynamicFare($vehicle, true)` (updates `vehicles.day_rent` / `vehicles.rent_opt` only, matching Cake).
 */
class MsrpSettingsController extends LegacyAppController
{
    private const DEFAULT_PTO_DOWNPAYMENT_RATE = 60;

    private const LEGACY_CREDIT_SCORE_FOR_PTO = 650;

    /**
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();

        if ($request->isMethod('post') && $request->has('data')) {
            $this->persistMsrpSettingsFromRequest($request, $userId);
            session()->flash('success', 'Updated successfully.');

            return redirect()->back();
        }

        $rows = DB::table('cs_msrp_settings')->where('user_id', $userId)->get();
        $msrpRequestData = [];
        foreach ($rows as $row) {
            $msrpRequestData[] = ['CsMsrpSetting' => (array) $row];
        }

        $equity = DB::table('cs_equity_settings')->where('user_id', $userId)->first();

        return view('msrp_settings.index', [
            'title_for_layout' => 'Path To Ownership Setting',
            'msrpRequestData' => $msrpRequestData,
            'share' => $equity !== null ? (string) ($equity->share ?? '') : '',
            'other_vhshare' => $equity !== null ? (string) ($equity->other_vhshare ?? '') : '',
        ]);
    }

    public function equaitysave(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();

        if ($request->isMethod('post') && $request->filled('data')) {
            $eq = $request->input('data.EquitySetting', []);
            $share = isset($eq['share']) ? (int) $eq['share'] : 0;
            $exists = DB::table('cs_equity_settings')->where('user_id', $userId)->first();
            $otherVh = array_key_exists('other_vhshare', $eq)
                ? (string) $eq['other_vhshare']
                : ($exists !== null ? (string) ($exists->other_vhshare ?? '0.00') : '0.00');

            $payload = [
                'user_id' => $userId,
                'share' => $share,
                'other_vhshare' => $otherVh,
            ];

            if ($exists) {
                DB::table('cs_equity_settings')->where('id', (int) $exists->id)->update($payload);
            } else {
                DB::table('cs_equity_settings')->insert($payload);
            }

            session()->flash('success', 'Updated successfully.');
        }

        return redirect()->back();
    }

    /**
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function pto(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();

        if ($request->isMethod('post') && $request->has('data')) {
            $this->persistPtoSettingsFromRequest($request, $userId);
            session()->flash('success', 'Updated successfully.');

            return redirect()->back();
        }

        $rows = DB::table('pto_settings')->where('user_id', $userId)->get();
        $ptoRequestData = [];
        foreach ($rows as $row) {
            $ptoRequestData[] = ['PtoSetting' => (array) $row];
        }

        $equity = DB::table('cs_equity_settings')->where('user_id', $userId)->first();

        return view('msrp_settings.pto', [
            'title_for_layout' => 'PTO Setting',
            'ptoRequestData' => $ptoRequestData,
            'share' => $equity !== null ? (string) ($equity->share ?? '') : '',
            'other_vhshare' => $equity !== null ? (string) ($equity->other_vhshare ?? '') : '',
        ]);
    }

    public function syncDayRentalToVehicle(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = $this->effectiveUserId();

        if ($request->ajax()) {
            $vehicles = DB::table('vehicles')
                ->where('user_id', $userId)
                ->get();

            foreach ($vehicles as $vehicle) {
                $this->calculateDynamicFareForVehicle((array) $vehicle, true);
            }
        }

        return response()->json(['status' => true, 'message' => 'Vehicle synched successfully']);
    }

    private function persistMsrpSettingsFromRequest(Request $request, int $userId): void
    {
        $data = $request->input('data', []);
        if (!is_array($data)) {
            return;
        }

        $toKeep = [];

        foreach ($data as $dt) {
            if (!is_array($dt) || !isset($dt['CsMsrpSetting']) || !is_array($dt['CsMsrpSetting'])) {
                continue;
            }
            $row = $dt['CsMsrpSetting'];
            $id = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : 0;

            $payload = [
                'user_id' => $userId,
                'msrp_from' => (int) ($row['msrp_from'] ?? 0),
                'msrp_to' => (int) ($row['msrp_to'] ?? 0),
                'credit_score_from' => (int) ($row['credit_score_from'] ?? 0),
                'credit_score_to' => (int) ($row['credit_score_to'] ?? 0),
                'downpayment' => (int) ($row['downpayment'] ?? 0),
            ];

            if ($id > 0) {
                $updated = DB::table('cs_msrp_settings')
                    ->where('id', $id)
                    ->where('user_id', $userId)
                    ->update($payload);
                if ($updated) {
                    $toKeep[] = $id;
                }
            } else {
                $toKeep[] = (int) DB::table('cs_msrp_settings')->insertGetId($payload);
            }
        }

        if ($toKeep !== []) {
            DB::table('cs_msrp_settings')
                ->where('user_id', $userId)
                ->whereNotIn('id', $toKeep)
                ->delete();
        }
    }

    private function persistPtoSettingsFromRequest(Request $request, int $userId): void
    {
        $data = $request->input('data', []);
        if (!is_array($data)) {
            return;
        }

        $toKeep = [];

        foreach ($data as $dt) {
            if (!is_array($dt) || !isset($dt['PtoSetting']) || !is_array($dt['PtoSetting'])) {
                continue;
            }
            $row = $dt['PtoSetting'];
            $id = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : 0;

            $payload = [
                'user_id' => $userId,
                'msrp_from' => (int) ($row['msrp_from'] ?? 0),
                'msrp_to' => (int) ($row['msrp_to'] ?? 0),
                'credit_score_from' => (int) ($row['credit_score_from'] ?? 0),
                'credit_score_to' => (int) ($row['credit_score_to'] ?? 0),
                'downpayment' => (int) ($row['downpayment'] ?? 0),
            ];

            if ($id > 0) {
                $updated = DB::table('pto_settings')
                    ->where('id', $id)
                    ->where('user_id', $userId)
                    ->update($payload);
                if ($updated) {
                    $toKeep[] = $id;
                }
            } else {
                $toKeep[] = (int) DB::table('pto_settings')->insertGetId($payload);
            }
        }

        if ($toKeep !== []) {
            DB::table('pto_settings')
                ->where('user_id', $userId)
                ->whereNotIn('id', $toKeep)
                ->delete();
        }
    }

    /**
     * Port of Cake `DynamicFare::calculateDynamicFare` when `$force === true` (main calculation path).
     */
    private function calculateDynamicFareForVehicle(array $vehicleData, bool $force = true): ?array
    {
        if (!$force && !empty($vehicleData['day_rent'])) {
            $rentOpt = !empty($vehicleData['rent_opt']) ? json_decode((string) $vehicleData['rent_opt'], true) : [];
            if (!empty($rentOpt) && is_array($rentOpt) && count($rentOpt) === 2) {
                $tier1Obj = $rentOpt[array_key_first($rentOpt)];
                $tier2Obj = $rentOpt[array_key_last($rentOpt)];

                return [
                    'day_rent' => $vehicleData['day_rent'],
                    'rent_opt' => [
                        ['after_day' => $tier1Obj['after_day'], 'amount' => $tier1Obj['amount']],
                        ['after_day' => $tier2Obj['after_day'], 'amount' => $tier2Obj['amount']],
                    ],
                    'rent_opt_des' => [
                        '0 to ' . sprintf('%d', $tier1Obj['after_day'] / 30) . ' months $' . $vehicleData['day_rent'] . ' per day',
                        '20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime',
                    ],
                ];
            }

            return [
                'day_rent' => $vehicleData['day_rent'],
                'rent_opt' => [],
                'rent_opt_des' => [
                    '0 to 1 months $' . $vehicleData['day_rent'] . ' per day',
                    '20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime',
                ],
            ];
        }

        $vehicleId = (int) ($vehicleData['id'] ?? 0);
        if ($vehicleId <= 0) {
            return null;
        }

        $rule = DB::table('cs_deposit_rules')->where('vehicle_id', $vehicleId)->first();
        if ($rule === null) {
            return null;
        }

        $price = !empty($vehicleData['msrp']) ? (float) $vehicleData['msrp'] : 10000.0;
        $ownerid = (int) ($vehicleData['user_id'] ?? 0);
        $vehicleCostInclRecon = (float) ($vehicleData['vehicleCostInclRecon'] ?? 0);

        $writeDown = (float) ($rule->write_down_allocation ?? 0);
        if ($writeDown > 0) {
            $downpaymentRate = $writeDown;
        } else {
            $downpaymentRow = DB::table('pto_settings')
                ->where('user_id', $ownerid)
                ->where('msrp_from', '<=', $price)
                ->where('msrp_to', '>=', $price)
                ->where('credit_score_from', '<=', self::LEGACY_CREDIT_SCORE_FOR_PTO)
                ->where('credit_score_to', '>=', self::LEGACY_CREDIT_SCORE_FOR_PTO)
                ->orderByDesc('id')
                ->first();

            $downpaymentRate = $downpaymentRow !== null
                ? (float) ($downpaymentRow->downpayment ?? self::DEFAULT_PTO_DOWNPAYMENT_RATE)
                : (float) self::DEFAULT_PTO_DOWNPAYMENT_RATE;
        }

        $goalLength = (int) ($rule->program_length ?? 365);
        if ($goalLength <= 0) {
            $goalLength = 365;
        }

        $maintenance = ((float) ($rule->monthly_maintenance ?? 0)) / 30;
        $financing = (float) ($rule->financing ?? 0);
        $financingType = (string) ($rule->financing_type ?? 'F');
        $dispositionfee = (float) ($rule->disposition_fee ?? 0);

        $downpayment = sprintf('%0.2f', ($price * $downpaymentRate / 100));

        $rev = DB::table('rev_settings')->where('user_id', $ownerid)->orderByDesc('id')->first();
        $revshare = ($rev !== null && !empty($rev->rental_rev))
            ? (float) $rev->rental_rev
            : (float) config('legacy.owner_part', 85);
        $diAFee = $revshare * 1;

        $financingPart = ($financingType === 'P')
            ? ((($vehicleCostInclRecon * $financing / 100) / 365))
            : $financing;

        $totalProgramFee = (float) $downpayment
            + ($maintenance * $goalLength)
            + $dispositionfee
            + ($financingPart * $goalLength);

        $financingFlag = (int) ($vehicleData['financing'] ?? 0);
        $totalProgramFeeWithDia = $diAFee > 0
            ? sprintf('%0.2f', (($financingFlag === 1 ? 105 : 0) + ($totalProgramFee * 100 / $diAFee)))
            : (string) ($totalProgramFee + ($financingFlag === 1 ? 105 : 0));

        if (($vehicleData['fare_type'] ?? '') === 'D') {
            $dailyFee = sprintf('%0.2f', ((float) $totalProgramFeeWithDia) / $goalLength);
        } else {
            $dailyFee = sprintf('%0.2f', (float) ($vehicleData['day_rent'] ?? 0));
        }

        $return = [
            'day_rent' => $dailyFee,
            'downpayment' => sprintf('%0.2f', ($price * $downpaymentRate / 100)),
            'rent_opt' => [],
            'rent_opt_des' => [
                '0 to ' . sprintf('%d', $goalLength) . ' days $' . $dailyFee . ' per day',
                '20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime',
            ],
        ];

        DB::table('vehicles')->where('id', $vehicleId)->update([
            'day_rent' => $return['day_rent'],
            'rent_opt' => '',
        ]);

        return $return;
    }

    private function effectiveUserId(): int
    {
        $parent = (int) session()->get('userParentId', 0);

        return $parent > 0 ? $parent : (int) session()->get('userid', 0);
    }
}
