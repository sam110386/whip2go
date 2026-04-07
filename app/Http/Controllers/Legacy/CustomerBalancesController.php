<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\CsUserBalance;
use App\Models\Legacy\CsUserBalanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerBalancesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private array $_balanceType = [
        "17" => "Car Damage", 
        "11" => "Parking Ticket", 
        "9"  => "Red Light Ticket", 
        "8"  => "Toll Violation",
        "10" => "Credit Card Chargebacks",
        "18" => "Hazardous Driving Fee",
        "19" => "Driver Bad Debt",
        "20" => "Vehicle Insurance Penalty",
        "21" => "Credit Deposit to Virtual Card"
    ];

    public function index(Request $request)
    {
        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }

        $keyword = $request->input('Search.keyword', '');
        $searchin = $request->input('Search.searchin', '');

        $title_for_layout = 'Customer Balances';

        $query = DB::table('cs_orders as CsOrder')
            ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
            ->leftJoin('cs_user_balances as CsUserBalance', 'CsUserBalance.user_id', '=', 'CsOrder.renter_id')
            ->where('CsOrder.user_id', $userId)
            ->select(
                'CsOrder.id as cs_order_id', 
                'User.id as user_id', 
                'User.first_name', 
                'User.last_name',
                'User.email',
                'User.contact_number', 
                'CsUserBalance.id as balance_id',
                'CsUserBalance.user_id as bal_user_id',
                'CsUserBalance.owner_id',
                'CsUserBalance.type',
                'CsUserBalance.credit',
                'CsUserBalance.debit',
                'CsUserBalance.balance',
                'CsUserBalance.chargetype',
                'CsUserBalance.installment_type',
                'CsUserBalance.installment_day',
                'CsUserBalance.installment',
                'CsUserBalance.last_processed',
                'CsUserBalance.note',
                'CsUserBalance.created',
                'CsUserBalance.status'
            )
            ->groupBy(
                'CsOrder.renter_id',
                'CsOrder.id',
                'User.id',
                'User.first_name',
                'User.last_name',
                'User.email',
                'User.contact_number',
                'CsUserBalance.id',
                'CsUserBalance.user_id',
                'CsUserBalance.owner_id',
                'CsUserBalance.type',
                'CsUserBalance.credit',
                'CsUserBalance.debit',
                'CsUserBalance.balance',
                'CsUserBalance.chargetype',
                'CsUserBalance.installment_type',
                'CsUserBalance.installment_day',
                'CsUserBalance.installment',
                'CsUserBalance.last_processed',
                'CsUserBalance.note',
                'CsUserBalance.created',
                'CsUserBalance.status'
            )
            ->orderBy('CsOrder.renter_id', 'DESC');

        if (!empty($keyword) && !empty($searchin)) {
            $query->where('User.' . $searchin, 'LIKE', '%' . $keyword . '%');
        }

        $limit = $request->input('Record.limit', session('CustomerBalances_limit', 20));
        session(['CustomerBalances_limit' => $limit]);

        $users = $query->paginate($limit);

        return view('legacy.customer_balances.index', compact('users', 'title_for_layout', 'keyword', 'searchin'));
    }

    public function update(Request $request, $id)
    {
        $idDecoded = base64_decode($id);
        if (empty($idDecoded)) {
            return redirect()->to('/customer_balances/index')->with('error', 'Sorry, please choose customer again');
        }

        $ownerId = session('userParentId');
        if (empty($ownerId) || $ownerId == 0) {
            $ownerId = session('userid');
        }

        $listTitle = 'Update Customer Balance';

        if ($request->isMethod('post')) {
            $data = $request->all();
            $customerData = CsUserBalance::where('user_id', $idDecoded)->first();
            if (!$customerData) {
                $customerData = new CsUserBalance();
                $customerData->user_id = $idDecoded;
            }

            $type = $data['UserBalance']['type'] ?? null;
            $note = $data['CsUserBalance']['note'] ?? '';
            $balance = $balancelog = (float)($data['UserBalance']['balance'] ?? 0);
            
            if (($data['UserBalance']['creditdebit'] ?? '') == 'credit') {
                if (!array_key_exists($type, $this->_balanceType)) {
                    return redirect()->back()->with('error', 'Sorry, please select the correct type');
                }

                $customerData->user_id = $idDecoded;
                $customerData->owner_id = $ownerId;

                if ($balance > 0) {
                    $debit = $customerData->debit ?: 0;
                    $credit = $customerData->credit ?: 0;
                    
                    if ($balance <= $debit && $debit > 0) {
                        $debit = $debit - $balance;
                    } elseif ($balance > $debit && $debit > 0) {
                        $balance = $balance - $debit;
                        $debit = 0;
                    } else {
                        $credit = $credit + $balance;
                    }

                    $customerData->note = $note;
                    $customerData->credit = $credit;
                    $customerData->balance = max(0, $customerData->balance - $balance);
                    $customerData->debit = $debit;
                    $customerData->type = $type;
                    $customerData->chargetype = $data['CsUserBalance']['chargetype'] ?? null;
                    $customerData->installment_type = $data['CsUserBalance']['installment_type'] ?? null;
                    $customerData->installment_day = $data['CsUserBalance']['installment_day'] ?? null;
                    $customerData->installment = $data['CsUserBalance']['installment'] ?? null;

                    CsUserBalanceLog::create([
                        'user_id' => $idDecoded,
                        'credit' => $balancelog,
                        'type' => $type,
                        'owner_id' => $ownerId,
                        'note' => $note,
                        'debit' => 0
                    ]);
                }
                $customerData->save();
                return redirect()->to('/customer_balances/index')->with('success', 'Customer balance updated successfully');
                
            } elseif (($data['UserBalance']['creditdebit'] ?? '') == 'debit') {
                if (!array_key_exists($type, $this->_balanceType)) {
                    return redirect()->back()->with('error', 'Sorry, please select the correct type');
                }

                $customerData->user_id = $idDecoded;
                $customerData->owner_id = $ownerId;
                $customerData->type = $type;
                $customerData->note = $note;

                if ($balance > 0) {
                    $debit = $customerData->debit ?: 0;
                    $credit = $customerData->credit ?: 0;
                    
                    if ($balance <= $credit && $credit > 0) {
                        $credit = $credit - $balance;
                    } elseif ($balance > $credit && $credit > 0) {
                        $balance = $balance - $credit;
                        $credit = 0;
                    } else {
                        $debit = $debit + $balance;
                    }

                    $customerData->debit = $debit;
                    $customerData->balance = $customerData->balance + $balance;
                    $customerData->credit = $credit;

                    CsUserBalanceLog::create([
                        'user_id' => $idDecoded,
                        'debit' => $balancelog,
                        'type' => $type,
                        'owner_id' => $ownerId,
                        'note' => $note,
                        'credit' => 0
                    ]);
                }

                $customerData->chargetype = $data['CsUserBalance']['chargetype'] ?? null;
                $customerData->installment_type = $data['CsUserBalance']['installment_type'] ?? null;
                $customerData->installment_day = $data['CsUserBalance']['installment_day'] ?? null;
                $customerData->installment = $data['CsUserBalance']['installment'] ?? null;
                $customerData->save();

                return redirect()->to('/customer_balances/index')->with('success', 'Customer balance updated successfully');
            } else {
                return redirect()->back()->with('error', 'Sorry, please select the correct credit/debit type');
            }
        }
        
        $customerData = CsUserBalance::where('user_id', $idDecoded)->first();
        $balanceTypes = $this->_balanceType;
        
        return view('legacy.customer_balances.update', compact('listTitle', 'customerData', 'balanceTypes', 'id'));
    }

    public function balance_logs(Request $request, $userid)
    {
        $useridDecoded = base64_decode($userid);
        if (empty($useridDecoded)) {
            return redirect()->to('/customer_balances/index')->with('error', 'Sorry, please choose customer again');
        }

        $ownerId = session('userParentId');
        if (empty($ownerId) || $ownerId == 0) {
            $ownerId = session('userid');
        }

        $title_for_layout = 'Customer Balance Logs';

        $query = CsUserBalanceLog::where('owner_id', $ownerId)->where('user_id', $useridDecoded);

        $creditdebit = $request->input('Search.creditdebit', '');
        $type = $request->input('Search.type', '');

        if (!empty($type)) {
            $query->where('type', $type);
        }
        if (!empty($creditdebit) && $creditdebit == 'credit') {
            $query->where('credit', '>', 0);
        }
        if (!empty($creditdebit) && $creditdebit == 'debit') {
            $query->where('debit', '>', 0);
        }

        $limit = $request->input('Record.limit', session('CustomerBalances_limit', 20));
        session(['CustomerBalances_limit' => $limit]);

        $logs = $query->orderBy('id', 'DESC')->paginate($limit);
        $balanceTypes = $this->_balanceType;

        return view('legacy.customer_balances.balance_logs', compact('logs', 'title_for_layout', 'creditdebit', 'type', 'balanceTypes', 'userid'));
    }
}
