<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsUserBalance;
use App\Models\Legacy\CsUserBalanceLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerBalancesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    /** @see app/Controller/CustomerBalancesController::$_balanceType */
    private static function balanceTypes(): array
    {
        return [
            '17' => 'Car Damage',
            '11' => 'Parking Ticket',
            '9' => 'Red Light Ticket',
            '8' => 'Toll Violation',
            '10' => 'Credit Card Chargebacks',
            '18' => 'Hazardous Driving Fee',
            '19' => 'Driver Bad Debt',
            '20' => 'Vehicle Insurance Penalty',
            '21' => 'Credit Deposit to Virtual Card',
        ];
    }

    private static function weekdays(): array
    {
        return [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];
    }

    /** Cake admin_addsubscription balance type list (GeoTab / chargebacks). */
    private static function subscriptionBalanceTypes(): array
    {
        return [
            9 => 'GeoTab Fee',
            10 => 'Credit Card Chargebacks',
        ];
    }

    private function adminTimezone(): string
    {
        $tz = $this->getAdminUserid()['timezone'] ?? null;

        return $tz !== null && $tz !== '' ? (string)$tz : (string)config('app.timezone');
    }

    private function formatAdminDateTime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return Carbon::parse($value)->timezone($this->adminTimezone())->format('Y-m-d h:i A');
        } catch (\Throwable $e) {
            return (string)$value;
        }
    }

    /**
     * Cake CustomerBalancesController::admin_index
     */
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['customer_balances_limit' => $lim]);
            }
        }

        $limit = (int)session('customer_balances_limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }

        $keyword = trim((string)$request->input('Search.keyword', ''));
        $type = trim((string)$request->input('Search.type', ''));
        $status = $request->input('Search.status');
        $statusStr = $status === null || $status === '' ? '' : (string)$status;

        $query = DB::table('cs_user_balances as cub')
            ->leftJoin('users as u', 'u.id', '=', 'cub.user_id')
            ->select([
                'cub.id',
                'cub.user_id',
                'cub.type',
                'cub.credit',
                'cub.debit',
                'cub.balance',
                'cub.chargetype',
                'cub.installment_type',
                'cub.installment',
                'cub.last_processed',
                'cub.note',
                'cub.created',
                'cub.status',
                'u.first_name',
                'u.last_name',
                'u.id as linked_user_id',
            ])
            ->orderByDesc('cub.id');

        if ($keyword !== '') {
            $query->where('u.first_name', 'LIKE', '%' . $keyword . '%');
        }
        if ($statusStr !== '') {
            $query->where('cub.status', (int)$statusStr);
        }
        if ($type === '1') {
            $query->where('u.is_driver', 1);
        }
        if ($type === '2') {
            $query->where('u.is_dealer', 1);
        }

        $records = $query->paginate($limit)->withQueryString();

        $balanceTypes = self::balanceTypes();
        $formatDt = fn ($v) => $this->formatAdminDateTime($v !== null ? (string)$v : null);

        if ($request->ajax()) {
            return view('admin.customer_balances._listing', [
                'records' => $records,
                'balanceTypes' => $balanceTypes,
                'formatDt' => $formatDt,
                'subscriptionMode' => false,
                'subscriptionUserId' => null,
            ]);
        }

        return view('admin.customer_balances.index', [
            'records' => $records,
            'keyword' => $keyword,
            'type' => $type,
            'status' => $statusStr,
            'limit' => $limit,
            'balanceTypes' => $balanceTypes,
            'formatDt' => $formatDt,
        ]);
    }

    /**
     * Cake CustomerBalancesController::admin_status
     */
    public function admin_status($id = null, $status = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $raw = (string)$id;
        $decoded = base64_decode($raw, true);
        $pk = ($decoded !== false && $decoded !== '') ? $decoded : $raw;
        if ($pk !== '' && ctype_digit((string)$pk)) {
            $newStatus = ((int)$status === 1) ? 1 : 0;
            CsUserBalance::where('id', (int)$pk)->update(['status' => $newStatus]);
        }

        return redirect()->back()->with('success', 'Record status is changed successfully.');
    }

    /**
     * Cake CustomerBalancesController::admin_relatedpayments
     */
    public function admin_relatedpayments($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $raw = (string)$id;
        $decoded = base64_decode($raw, true);
        $pk = ($decoded !== false && $decoded !== '') ? $decoded : $raw;
        if ($pk === '' || !ctype_digit((string)$pk)) {
            return redirect('/admin/customer_balances/index')->with('error', 'Sorry, wrong attempt');
        }

        $row = CsUserBalance::find((int)$pk);
        if ($row === null) {
            return redirect('/admin/customer_balances/index')->with('error', 'Sorry, respective record is not found');
        }

        $userId = (int)$row->user_id;

        $balances = CsUserBalance::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        $payments = DB::table('cs_order_payments as cop')
            ->leftJoin('cs_orders as o', 'o.id', '=', 'cop.cs_order_id')
            ->where('cop.type', 6)
            ->where('cop.status', 1)
            ->whereNotNull('o.id')
            ->where('o.renter_id', $userId)
            ->orderByDesc('cop.id')
            ->select(['cop.*', 'o.increment_id'])
            ->get();

        $formatDt = fn ($v) => $this->formatAdminDateTime($v !== null ? (string)$v : null);

        return view('admin.customer_balances.relatedpayments', [
            'listTitle' => 'Credit/Debit Payment Details',
            'balances' => $balances,
            'payments' => $payments,
            'formatDt' => $formatDt,
        ]);
    }

    /**
     * Cake CustomerBalancesController::admin_subscription
     */
    public function admin_subscription(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $raw = (string)$userid;
        $decoded = base64_decode($raw, true);
        $uid = ($decoded !== false && $decoded !== '') ? $decoded : $raw;
        if ($uid === '' || !ctype_digit((string)$uid)) {
            return redirect('/admin/customer_balances/index')->with('error', 'Invalid user.');
        }
        $userId = (int)$uid;

        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['customer_balances_limit' => $lim]);
            }
        }

        $limit = (int)session('customer_balances_limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }

        $query = DB::table('cs_user_balances as cub')
            ->where('cub.user_id', $userId)
            ->select([
                'cub.id',
                'cub.user_id',
                'cub.type',
                'cub.credit',
                'cub.debit',
                'cub.balance',
                'cub.chargetype',
                'cub.installment_type',
                'cub.installment',
                'cub.last_processed',
                'cub.note',
                'cub.created',
                'cub.status',
            ])
            ->orderByDesc('cub.id');

        $records = $query->paginate($limit)->withQueryString();

        $balanceTypes = self::balanceTypes();
        $formatDt = fn ($v) => $this->formatAdminDateTime($v !== null ? (string)$v : null);

        if ($request->ajax()) {
            return view('admin.customer_balances._listing', [
                'records' => $records,
                'balanceTypes' => $balanceTypes,
                'formatDt' => $formatDt,
                'subscriptionMode' => true,
                'subscriptionUserId' => $userId,
            ]);
        }

        return view('admin.customer_balances.subscription', [
            'records' => $records,
            'userid' => $userId,
            'useridB64' => base64_encode((string)$userId),
            'limit' => $limit,
            'balanceTypes' => $balanceTypes,
            'formatDt' => $formatDt,
        ]);
    }

    /**
     * Cake CustomerBalancesController::admin_addsubscription
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function admin_addsubscription(Request $request, $userid = null, $id = '')
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $rawUser = (string)$userid;
        $decodedUser = base64_decode($rawUser, true);
        $userKey = ($decodedUser !== false && $decodedUser !== '') ? $decodedUser : $rawUser;
        if ($userKey === '' || !ctype_digit((string)$userKey)) {
            return redirect('/admin/users/index')->with('error', 'Sorry, please choose customer again');
        }
        $userId = (int)$userKey;

        $rawBal = (string)$id;
        $balancePk = null;
        if ($rawBal !== '') {
            $decodedBal = base64_decode($rawBal, true);
            $bid = ($decodedBal !== false && $decodedBal !== '') ? $decodedBal : $rawBal;
            if ($bid !== '' && ctype_digit((string)$bid)) {
                $balancePk = (int)$bid;
            }
        }

        $balanceTypes = self::subscriptionBalanceTypes();
        $weekdays = self::weekdays();

        if ($request->isMethod('POST')) {
            return $this->processAdminAddsubscriptionPost($request, $userId, $balanceTypes);
        }

        $balance = null;
        if ($balancePk !== null) {
            $balance = CsUserBalance::query()
                ->where('user_id', $userId)
                ->where('id', $balancePk)
                ->first();
            if ($balance === null) {
                return redirect('/admin/customer_balances/subscription/' . base64_encode((string)$userId))
                    ->with('error', 'Record not found.');
            }
        }

        return view('admin.customer_balances.addsubscription', [
            'listTitle' => 'Dealer Charges',
            'balance' => $balance,
            'userid' => $userId,
            'useridB64' => base64_encode((string)$userId),
            'balanceTypes' => $balanceTypes,
            'weekdays' => $weekdays,
        ]);
    }

    private function processAdminAddsubscriptionPost(Request $request, int $userId, array $balanceTypes): RedirectResponse
    {
        $row = $request->input('CsUserBalance', []);
        if (!is_array($row)) {
            $row = [];
        }

        $type = isset($row['type']) ? (int)$row['type'] : 0;
        if (!array_key_exists($type, $balanceTypes)) {
            return redirect()->back()->with('error', 'Invalid charge type.');
        }

        $note = isset($row['note']) ? (string)$row['note'] : '';
        $amount = isset($row['balance']) ? (float)$row['balance'] : 0.0;

        $postBalId = isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : 0;
        $model = null;
        if ($postBalId > 0) {
            $model = CsUserBalance::query()
                ->where('user_id', $userId)
                ->where('id', $postBalId)
                ->first();
        }
        if ($model === null) {
            $model = new CsUserBalance();
            $model->user_id = $userId;
        }

        $model->owner_id = 0;
        $model->user_id = $userId;

        if ($amount > 0) {
            $debit = (float)($model->debit ?: 0);
            $credit = (float)($model->credit ?: 0);
            $bal = $amount;

            if ($type !== 9) {
                if ($bal <= $debit && $debit > 0) {
                    $debit = $debit - $bal;
                } elseif ($bal > $debit && $debit > 0) {
                    $bal = $bal - $debit;
                    $debit = 0;
                } else {
                    $credit = $credit + $bal;
                }
                $model->credit = $credit;
                $oldBalance = (float)($model->balance ?: 0);
                $model->balance = (($oldBalance - $bal) > 0 ? 0 : $bal);
                $model->debit = $debit;
                $model->installment = isset($row['installment']) ? (float)$row['installment'] : 0;
            } else {
                $model->balance = $amount;
                $model->installment = $amount;
            }

            $model->type = $type;
            $model->note = $note;
            $model->chargetype = isset($row['chargetype']) ? (string)$row['chargetype'] : 'subscription';
            $model->installment_type = isset($row['installment_type']) ? (string)$row['installment_type'] : 'daily';
            $model->installment_day = isset($row['installment_day']) ? (string)$row['installment_day'] : 'sun';
        }

        if (!$model->exists) {
            $model->status = (int)($model->status ?? 1);
        }

        $this->primeBalanceFields($model);
        $model->save();

        return redirect('/admin/customer_balances/subscription/' . base64_encode((string)$userId))
            ->with('success', 'Customer balance updated successfully.');
    }

    /**
     * Cake CustomerBalancesController::admin_add
     */
    public function admin_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $balanceTypes = self::balanceTypes();
        $weekdays = self::weekdays();

        $rawPath = $id !== null ? (string)$id : '';
        $decodedId = $rawPath !== '' ? base64_decode($rawPath, true) : false;
        $balancePk = ($decodedId !== false && $decodedId !== '' && ctype_digit((string)$decodedId))
            ? (int)$decodedId
            : null;

        if ($request->isMethod('POST')) {
            return $this->processAdminAddPost($request, $balanceTypes);
        }

        $balance = $balancePk !== null
            ? CsUserBalance::find($balancePk)
            : null;

        if ($balancePk !== null && $balance === null) {
            return redirect('/admin/customer_balances/index')->with('error', 'Balance record not found.');
        }

        return view('admin.customer_balances.add', [
            'listTitle' => 'Add Credit & Debit Charge',
            'balance' => $balance,
            'balanceTypes' => $balanceTypes,
            'weekdays' => $weekdays,
        ]);
    }

    private function processAdminAddPost(Request $request, array $balanceTypes): RedirectResponse
    {
        $row = $request->input('CsUserBalance', []);
        if (!is_array($row)) {
            $row = [];
        }

        $type = isset($row['type']) ? (string)$row['type'] : '';
        $note = isset($row['note']) ? (string)$row['note'] : '';
        $creditdebit = isset($row['creditdebit']) ? (string)$row['creditdebit'] : '';
        $amount = isset($row['balance']) ? (float)$row['balance'] : 0.0;
        $balancelog = $amount;

        $existingId = isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : 0;
        $model = $existingId > 0 ? CsUserBalance::find($existingId) : new CsUserBalance();
        if ($existingId > 0 && $model === null) {
            return redirect()->back()->with('error', 'Record not found.');
        }
        if ($model === null) {
            $model = new CsUserBalance();
        }

        if ($creditdebit === 'credit') {
            if (!array_key_exists($type, $balanceTypes)) {
                return redirect()->back()->with('error', 'Sorry, please select the correct type');
            }
            if ($amount > 0) {
                $debit = (float)($model->debit ?: 0);
                $credit = (float)($model->credit ?: 0);
                $bal = $amount;
                if ($bal <= $debit && $debit > 0) {
                    $debit = $debit - $bal;
                } elseif ($bal > $debit && $debit > 0) {
                    $bal = $bal - $debit;
                    $debit = 0;
                } else {
                    $credit = $credit + $bal;
                }

                $oldBalance = (float)($model->balance ?: 0);
                $model->note = $note;
                $model->credit = $credit;
                $model->balance = (($oldBalance - $bal) > 0 ? 0 : $bal);
                $model->debit = $debit;
                $model->type = (int)$type;
                $model->chargetype = isset($row['chargetype']) ? (string)$row['chargetype'] : 'lumpsum';
                $model->installment_type = isset($row['installment_type']) ? (string)$row['installment_type'] : 'daily';
                $model->installment_day = $row['installment_day'] ?? null;
                $model->installment = isset($row['installment']) ? (float)$row['installment'] : 0;

                $uidForLog = (int)($model->user_id ?: ($row['user_id'] ?? 0));
                if ($uidForLog > 0) {
                    CsUserBalanceLog::query()->insert([
                        'user_id' => $uidForLog,
                        'credit' => $balancelog,
                        'debit' => 0,
                        'type' => (int)$type,
                        'owner_id' => 0,
                        'note' => $note,
                    ]);
                }
            } else {
                $model->note = $note;
                $model->type = (int)$type;
                $model->chargetype = isset($row['chargetype']) ? (string)$row['chargetype'] : 'lumpsum';
                $model->installment_type = isset($row['installment_type']) ? (string)$row['installment_type'] : 'daily';
                $model->installment_day = $row['installment_day'] ?? null;
                $model->installment = isset($row['installment']) ? (float)$row['installment'] : 0;
            }
            $this->fillAdminAddMeta($model, $row);
            if ((int)$model->user_id < 1) {
                return redirect()->back()->with('error', 'Driver / customer user id is required.');
            }
            $this->primeBalanceFields($model);
            $model->save();

            return redirect('/admin/customer_balances/index')->with('success', 'Customer balance updated successfully.');
        }

        if ($creditdebit === 'debit') {
            if (!array_key_exists($type, $balanceTypes)) {
                return redirect()->back()->with('error', 'Sorry, please select the correct type');
            }
            $model->type = (int)$type;
            $model->note = $note;
            if ($amount > 0) {
                $debit = (float)($model->debit ?: 0);
                $credit = (float)($model->credit ?: 0);
                $bal = $amount;
                if ($bal <= $credit && $credit > 0) {
                    $credit = $credit - $bal;
                } elseif ($bal > $credit && $credit > 0) {
                    $bal = $bal - $credit;
                    $credit = 0;
                } else {
                    $debit = $debit + $bal;
                }

                $oldBalance = (float)($model->balance ?: 0);
                $model->debit = $debit;
                $model->balance = (($oldBalance - $bal) > 0 ? 0 : $bal);
                $model->credit = $credit;

                $uidForLog = (int)($model->user_id ?: ($row['user_id'] ?? 0));
                if ($uidForLog > 0) {
                    CsUserBalanceLog::query()->insert([
                        'user_id' => $uidForLog,
                        'credit' => 0,
                        'debit' => $balancelog,
                        'type' => (int)$type,
                        'owner_id' => 0,
                        'note' => $note,
                    ]);
                }
            }
            $model->chargetype = isset($row['chargetype']) ? (string)$row['chargetype'] : 'lumpsum';
            $model->installment_type = isset($row['installment_type']) ? (string)$row['installment_type'] : 'daily';
            $model->installment_day = $row['installment_day'] ?? null;
            $model->installment = isset($row['installment']) ? (float)$row['installment'] : 0;
            $this->fillAdminAddMeta($model, $row);
            if ((int)$model->user_id < 1) {
                return redirect()->back()->with('error', 'Driver / customer user id is required.');
            }
            $this->primeBalanceFields($model);
            $model->save();

            return redirect('/admin/customer_balances/index')->with('success', 'Customer balance updated successfully.');
        }

        return redirect()->back()->with('error', 'Sorry, please select the correct credit/debit type');
    }

    private function primeBalanceFields(CsUserBalance $model): void
    {
        $model->credit = $model->credit ?? 0;
        $model->debit = $model->debit ?? 0;
        $model->balance = $model->balance ?? 0;
    }

    private function fillAdminAddMeta(CsUserBalance $model, array $row): void
    {
        if (isset($row['user_id']) && (int)$row['user_id'] > 0) {
            $model->user_id = (int)$row['user_id'];
        }
        if (isset($row['status'])) {
            $model->status = (int)$row['status'];
        }
        $model->owner_id = 0;
    }
}
