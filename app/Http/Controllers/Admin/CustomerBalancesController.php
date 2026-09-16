<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsUserBalance;
use App\Models\Legacy\CsUserBalanceLog;
use App\Http\Controllers\Legacy\LegacyAppController;

class CustomerBalancesController extends LegacyAppController
{
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
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Customer Balance';
        $limitName = 'cs_user_balances_limit';
        $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
        $type = $request->input('Search.type', $request->input('type', ''));
        $status = $request->input('Search.status', $request->input('status', ''));

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$limitName => $limit]);
        } else {
            $limit = session($limitName, $this->recordsPerPage);
        }

        $query = CsUserBalance::with('user:id,first_name,last_name,is_driver,is_dealer');

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if (!empty($keyword) || $type == 1 || $type == 2) {
            $query->whereHas('user', function ($q) use ($keyword, $type) {
                if (!empty($keyword)) {
                    $q->where('first_name', 'LIKE', "%{$keyword}%");
                }

                if ($type == 1) {
                    $q->where('is_driver', 1);
                } elseif ($type == 2) {
                    $q->where('is_dealer', 1);
                }
            });
        }

        $csUserBalances = $query->orderBy('id', 'DESC')->paginate($limit);
        $balanceTypes = self::balanceTypes();

        if ($request->ajax()) {
            return view('admin.customer_balances.elements.index', compact('csUserBalances', 'keyword', 'type', 'status', 'balanceTypes', 'limit'));
        }

        return view('admin.customer_balances.index', compact('csUserBalances', 'keyword', 'type', 'status', 'balanceTypes', 'title', 'limit'));
    }
    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Add Credit & Debit Charge';
        $balanceTypes = self::balanceTypes();
        $weekdays = [
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
        ];
        $id = $this->decodeId($id);

        if ($request->isMethod('POST')) {
            $data = $request->input('CsUserBalance', []);
            $type = $data['type'] ?? null;
            $note = $data['note'] ?? null;
            $balance = (float) ($data['balance'] ?? 0);
            $balanceLog = $balance;
            $creditdebit = $data['creditdebit'] ?? '';

            if (!array_key_exists($type, $balanceTypes)) {
                return redirect()->back()->with('error', 'Sorry, please select the correct type');
            }

            $customerBalance = CsUserBalance::find($id) ?? new CsUserBalance();
            $debit = !empty($customerBalance->debit) ? (float) $customerBalance->debit : 0;
            $credit = !empty($customerBalance->credit) ? (float) $customerBalance->credit : 0;
            $currentBalance = !empty($customerBalance->balance) ? (float) $customerBalance->balance : 0;

            if ($creditdebit === 'credit') {
                if ($balance > 0) {
                    if ($balance <= $debit && $debit > 0) {
                        $debit = $debit - $balance;
                    } elseif ($balance > $debit && $debit > 0) {
                        $balance = $balance - $debit;
                        $debit = 0;
                    } else {
                        $credit = $credit + $balance;
                    }

                    CsUserBalanceLog::create([
                        'user_id' => $data['user_id'] ?? $customerBalance->user_id,
                        'credit' => $balanceLog,
                        'type' => $type,
                        'owner_id' => 0,
                        'note' => $note,
                    ]);
                }

                $customerBalance->fill([
                    'user_id' => $data['user_id'] ?? $customerBalance->user_id,
                    'type' => $type,
                    'note' => $note,
                    'credit' => $credit,
                    'debit' => $debit,
                    'balance' => ($currentBalance - $balance) > 0 ? ($currentBalance - $balance) : $balance,
                    'chargetype' => $data['chargetype'] ?? null,
                    'installment_type' => $data['installment_type'] ?? null,
                    'installment_day' => $data['installment_day'] ?? null,
                    'installment' => $data['installment'] ?? 0,
                ]);

                $customerBalance->save();

                return redirect('admin/customer_balances/index')->with('success', 'Customer balance updated successfully');

            } elseif ($creditdebit === 'debit') {
                if ($balance > 0) {
                    if ($balance <= $credit && $credit > 0) {
                        $credit = $credit - $balance;
                    } elseif ($balance > $credit && $credit > 0) {
                        $balance = $balance - $credit;
                        $credit = 0;
                    } else {
                        $debit = $debit + $balance;
                    }

                    CsUserBalanceLog::create([
                        'user_id' => $data['user_id'] ?? $customerBalance->user_id,
                        'debit' => $balanceLog,
                        'type' => $type,
                        'owner_id' => 0,
                        'note' => $note,
                    ]);
                }

                $customerBalance->fill([
                    'user_id' => $data['user_id'] ?? $customerBalance->user_id,
                    'type' => $type,
                    'note' => $note,
                    'credit' => $credit,
                    'debit' => $debit,
                    'balance' => ($currentBalance - $balance) > 0 ? 0 : $balance,
                    'chargetype' => $data['chargetype'] ?? null,
                    'installment_type' => $data['installment_type'] ?? null,
                    'installment_day' => $data['installment_day'] ?? null,
                    'installment' => $data['installment'] ?? 0,
                ]);

                $customerBalance->save();

                return redirect('admin/customer_balances/index')->with('success', 'Customer balance updated successfully');
            } else {
                return redirect()->back()->with('error', 'Sorry, please select the correct credit/debit type');
            }
        }

        $csUserBalance = CsUserBalance::find($id);
        return view('admin.customer_balances.add', compact('title', 'csUserBalance', 'balanceTypes', 'weekdays'));
    }
    public function subscription(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Dealer Charges';
        $sessLimitName = 'customer_balances_subscription_limit';
        $userid = $this->decodeId($userid);
        $balanceTypes = self::balanceTypes();

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } else {
            $limit = session($sessLimitName, $this->recordsPerPage);
        }

        $csUserBalances = CsUserBalance::where('user_id', $userid)
            ->orderBy('id', 'DESC')
            ->paginate($limit);


        if ($request->ajax()) {
            return view('admin.customer_balances.elements.subscription', compact('title', 'userid', 'csUserBalances', 'balanceTypes', 'limit'));
        }

        return view('admin.customer_balances.subscription', compact('title', 'userid', 'csUserBalances', 'balanceTypes', 'limit'));
    }
    public function addsubscription(Request $request, $userid = null, $id = '')
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $listTitle = 'Dealer Charges';
        $balanceTypes = [
            9 => "GeoTab Fee",
            10 => "Credit Card Chargebacks"
        ];
        $userid = $this->decodeId($userid);

        if (empty($userid)) {
            return redirect('admin/users/index')->with('error', 'Sorry, please choose customer again');
        }

        if ($request->isMethod('post')) {
            $data = $request->input('CsUserBalance', []);
            $type = $data['type'] ?? null;
            $note = $data['note'] ?? null;
            $balance = (float) ($data['balance'] ?? 0);
            $customerBalance = CsUserBalance::find($id) ?? new CsUserBalance();
            $customerBalance->user_id = $userid;

            if ($balance > 0) {
                $debit = !empty($customerBalance->debit) ? (float) $customerBalance->debit : 0;
                $credit = !empty($customerBalance->credit) ? (float) $customerBalance->credit : 0;
                $currentBalance = !empty($customerBalance->balance) ? (float) $customerBalance->balance : 0;

                if ($type != 9) {
                    if ($balance <= $debit && $debit > 0) {
                        $debit = $debit - $balance;
                    } elseif ($balance > $debit && $debit > 0) {
                        $balance = $balance - $debit;
                        $debit = 0;
                    } else {
                        $credit = $credit + $balance;
                    }

                    $customerBalance->credit = $credit;
                    $customerBalance->balance = ($currentBalance - $balance) > 0 ? 0 : $balance;
                    $customerBalance->debit = $debit;
                    $customerBalance->installment = $data['installment'] ?? 0;
                } else {
                    $customerBalance->balance = $balance;
                    $customerBalance->installment = $balance;
                }

                $customerBalance->type = $type;
                $customerBalance->note = $note;
                $customerBalance->chargetype = $data['chargetype'] ?? null;
                $customerBalance->installment_type = $data['installment_type'] ?? null;
                $customerBalance->installment_day = $data['installment_day'] ?? null;
            }

            $customerBalance->save();

            return redirect('admin/customer_balances/subscription/' . base64_encode($userid))->with('success', 'Customer balance updated successfully');
        }

        $csUserBalances = null;

        if (!empty($id)) {
            $csUserBalances = CsUserBalance::where('user_id', $userid)->where('id', $id)->first();
        }

        return view('admin.customer_balances.addsubscription', compact('csUserBalances', 'title', 'balanceTypes', 'userid', 'id'));
    }
    public function status($id = null, $status = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);

        if (!empty($id)) {
            $userBalance = CsUserBalance::find($id);

            if ($userBalance) {
                $userBalance->status = ((int) $status == 1) ? 1 : 0;
                $userBalance->save();
            }
        }

        return redirect()->back()->with('success', 'Record status is changed successfully.');
    }
    public function relatedpayments($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Credit/Debit Payment Details';
        $id = $this->decodeId($id);

        if (empty($id)) {
            return redirect('/admin/customer_balances/index')->with('error', 'Sorry, wrong attempt');
        }

        $csUserBalance = CsUserBalance::find($id);

        if (!$csUserBalance) {
            return redirect('/admin/customer_balances/index')->with('error', 'Sorry, respective record is not found');
        }

        $userId = (int) $csUserBalance->user_id;
        $csUserBalances = CsUserBalance::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        $csOrderPayments = CsOrderPayment::with('csOrder:id,renter_id,increment_id')
            ->where('type', 6)
            ->where('status', 1)
            ->whereHas('csOrder', fn($q) => $q->where('renter_id', $userId))
            ->orderByDesc('id')
            ->get();

        return view(
            'admin.customer_balances.relatedpayments',
            compact('title', 'csUserBalances', 'csOrderPayments')
        );
    }
}

