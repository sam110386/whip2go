<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanManagersController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        $keyword = '';
        $conditions = [];
        $bindings = [];

        if ($request->filled('Search.keyword') || $request->filled('keyword')) {
            $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
        }

        $query = DB::table('user_incomes as UserIncome')
            ->leftJoin('users as User', 'User.id', '=', 'UserIncome.user_id')
            ->leftJoin('loans as Loan', 'Loan.user_id', '=', 'UserIncome.user_id')
            ->select('Loan.*', 'UserIncome.*', 'User.first_name', 'User.last_name', 'User.contact_number');

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('User.first_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('User.last_name', 'LIKE', "%{$keyword}%")
                  ->orWhere('User.email', 'LIKE', "%{$keyword}%")
                  ->orWhere('User.contact_number', 'LIKE', "%{$keyword}%");
            });
        }

        $sessLimitName = 'managers_limit';
        $limit = $request->input('Record.limit',
            session($sessLimitName, $this->recordsPerPage));
        if ($request->filled('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $lists = $query->orderByDesc('UserIncome.id')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.loan._admin_index', compact('lists', 'keyword'));
        }

        return view('admin.loan.index', compact('lists', 'keyword'));
    }

    public function detail(Request $request, $userid)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->url('admin/users/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        $detail = DB::table('users as User')
            ->leftJoin('user_incomes as UserIncome', 'UserIncome.user_id', '=', 'User.id')
            ->leftJoin('loans as Loan', 'Loan.user_id', '=', 'User.id')
            ->select('Loan.*', 'UserIncome.*', 'User.first_name', 'User.id as user_id', 'User.last_name', 'User.contact_number')
            ->where('User.id', $userid)
            ->first();

        return view('admin.loan.detail', compact('detail', 'userid'));
    }

    public function getplaidrecord(Request $request)
    {
        $userid = $request->input('userid');
        $plaidObj = DB::table('plaid_users')->where('user_id', $userid)->first();

        $return = ['status' => false, 'message' => "Sorry, User didnt add his bank details yet"];
        if (!empty($plaidObj) && !empty(json_decode($plaidObj->metadata, true))) {
            $plaid = $plaidObj;
            $plaidView = view('admin.loan._plaid', compact('plaid'))->render();
            $return = ['status' => true, 'message' => '', 'plaidtoken' => $plaidObj->token, 'view' => $plaidView];
        }

        return response()->json($return);
    }

    public function getplaidbalance(Request $request)
    {
        // Plaid balance check preserved as stub — requires Plaid component integration
        $return = ['status' => true, 'message' => 'Sorry, balance not returned', 'balance' => '$0'];

        return response()->json($return);
    }

    public function bankstatement(Request $request)
    {
        // Plaid transaction history preserved as stub — requires Plaid component integration
        $return = ['status' => true, 'message' => 'Sorry, statement is not returned', 'transactions' => []];

        return response()->json($return);
    }
}
