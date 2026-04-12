<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CakePHP `OrderDepositRulesController` — payment / deposit rule edits on a booking.
 */
class OrderDepositRulesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    /** @return array<int, string> */
    protected function insurancePayerOptions(): array
    {
        return [
            0 => 'Driveitaway Fleet',
            1 => 'Dealer Direct',
            2 => 'Dealer Fleet',
            3 => 'BYOI via Driver',
            4 => 'BYOI via DIA',
            5 => 'BYOI via Driver Financed',
            6 => 'BYOI via broker DIA financed',
            7 => 'DIA Fleet Back Up',
        ];
    }

    public function admin_update(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (empty($admin['administrator'])) {
            return redirect('/admin/linked_bookings/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        return $this->runDepositRuleUpdate(
            $request,
            $id,
            '/admin/order_deposit_rules/admin_update/',
            '/admin/bookings/index'
        );
    }

    /**
     * Shared update handler (used by {@see admin_update()} and Cloud controller).
     */
    /**
     * @return RedirectResponse|View
     */
    protected function runDepositRuleUpdate(
        Request $request,
        ?string $idB64,
        string $formActionPrefix,
        string $cancelUrl
    ) {
        $orderId = $this->decodeB64Id($idB64);
        if (!$orderId) {
            return redirect($cancelUrl)->with('error', 'Invalid booking.');
        }

        $row = DB::table('cs_order_deposit_rules')->where('cs_order_id', $orderId)->first();
        if (!$row) {
            return redirect($cancelUrl)->with('error', 'Deposit rule not found for this booking.');
        }

        if ($request->isMethod('POST')) {
            $this->persistAggregatedRule($request, $orderId, (int)$row->id);

            return redirect()->back()->with('success', 'Payment setting is updated successfully.');
        }

        $rule = $this->hydrateRuleForForm((array)$row);
        $promo = $this->loadPromoForRenter((int)DB::table('cs_orders')->where('id', $orderId)->value('renter_id'));

        return view('admin.order_deposit_rules.form_update', [
            'rule' => $rule,
            'orderId' => $orderId,
            'orderIdB64' => base64_encode((string)$orderId),
            'formAction' => $formActionPrefix . base64_encode((string)$orderId),
            'cancelUrl' => $cancelUrl,
            'insurancePayers' => $this->insurancePayerOptions(),
            'promo' => $promo,
        ]);
    }

    protected function persistAggregatedRule(Request $request, int $csOrderId, int $ruleId): void
    {
        $input = array_merge(
            (array)$request->input('data.OrderDepositRule', []),
            (array)$request->input('OrderDepositRule', [])
        );

        $existing = (array)DB::table('cs_order_deposit_rules')->where('id', $ruleId)->first();
        unset($input['start_datetime']);

        $merged = $existing;
        foreach ($input as $key => $value) {
            if (in_array($key, ['id', 'created', 'cs_order_id'], true)) {
                continue;
            }
            $merged[$key] = $value;
        }

        $depositOpt = $this->asOptArray($merged['deposit_opt'] ?? []);
        $initialFeeOpt = $this->asOptArray($merged['initial_fee_opt'] ?? []);
        $rentalOpt = $this->asOptArray($merged['rental_opt'] ?? []);
        $durationOpt = $this->asOptArray($merged['duration_opt'] ?? []);

        $sumDepositOpt = $this->sumKeyed($depositOpt, 'amount');
        $sumInitialOpt = $this->sumKeyed($initialFeeOpt, 'amount');
        $sumRentalOpt = $this->sumKeyed($rentalOpt, 'amount');
        $sumDurationOpt = $this->sumKeyed($durationOpt, 'duration');

        $merged['deposit_opt'] = $sumDepositOpt !== 0.0 ? json_encode(array_values($depositOpt)) : '';
        $merged['initial_fee_opt'] = $sumInitialOpt !== 0.0 ? json_encode(array_values($initialFeeOpt)) : '';
        $merged['rental_opt'] = $sumRentalOpt !== 0.0 ? json_encode(array_values($rentalOpt)) : '';
        $merged['duration_opt'] = $sumDurationOpt > 0 ? json_encode(array_values($durationOpt)) : '';

        $merged['total_deposit_amt'] = (float)($merged['deposit_amt'] ?? 0) + $sumDepositOpt;
        $merged['total_initial_fee'] = (float)($merged['initial_fee'] ?? 0) + $sumInitialOpt;

        $merged['modified'] = now()->toDateTimeString();

        $cols = Schema::getColumnListing('cs_order_deposit_rules');
        $save = [];
        foreach ($cols as $col) {
            if (in_array($col, ['id', 'created'], true)) {
                continue;
            }
            if (array_key_exists($col, $merged)) {
                $save[$col] = $merged[$col];
            }
        }

        DB::table('cs_order_deposit_rules')->where('id', $ruleId)->update($save);
    }

    protected function hydrateRuleForForm(array $row): array
    {
        foreach (['deposit_opt', 'initial_fee_opt', 'rental_opt', 'duration_opt'] as $k) {
            if (!empty($row[$k]) && is_string($row[$k])) {
                $decoded = json_decode($row[$k], true);
                $row[$k] = is_array($decoded) ? $decoded : [];
            } else {
                $row[$k] = [];
            }
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function loadPromoForRenter(int $renterId): ?array
    {
        if ($renterId <= 0) {
            return null;
        }
        // Promo plugin not ported — return null so UI omits promo block.
        return null;
    }

    protected function decodeB64Id(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $tmp = base64_decode($value, true);
        if ($tmp !== false && ctype_digit((string)$tmp)) {
            return (int)$tmp;
        }
        if (ctype_digit((string)$value)) {
            return (int)$value;
        }

        return null;
    }

    /**
     * @param mixed $v
     * @return array<int, array<string, mixed>>
     */
    protected function asOptArray($v): array
    {
        if (is_array($v)) {
            return array_values($v);
        }
        if (is_string($v) && $v !== '') {
            $decoded = json_decode($v, true);

            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function sumKeyed(array $rows, string $key): float
    {
        $s = 0.0;
        foreach ($rows as $r) {
            if (is_array($r) && array_key_exists($key, $r)) {
                $s += (float)$r[$key];
            }
        }

        return $s;
    }
}
