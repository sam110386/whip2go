<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legacy\PlaidUser;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PlaidUsersController extends Controller
{
    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => 0,
            'message' => "AdminPlaidUsers::{$action} pending migration",
            'result' => (object)[],
        ]);
    }

    public function index(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('admin.users.index');
        }

        $plaids = PlaidUser::where('user_id', $userid)->get();

        return view('admin.plaidusers.index', compact('plaids', 'userid'));
    }

    public function balance(Request $request)
    {
        $id = $request->input('id');
        $accountId = $request->input('token');
        $plaidObj = PlaidUser::find($id);

        if (empty($plaidObj)) {
            return response('something went wrong', 400);
        }

        // Placeholder for Plaid service call
        // $BalanceObj = $this->plaidService->getBalance($plaidObj->token, [], [$accountId]);
        
        return view('admin.plaidusers.balance', ['accounts' => []]);
    }

    public function transactions(Request $request)
    {
        $id = $request->input('id');
        $accountId = $request->input('token');
        $plaidObj = PlaidUser::find($id);

        if (empty($plaidObj)) {
            return response('something went wrong', 400);
        }

        // Placeholder for Plaid service call
        // $TransactionObj = $this->plaidService->getTransactionHistory($plaidObj->token, now()->subDays(30)->toDateString(), now()->toDateString(), [], [$accountId]);
        
        return view('admin.plaidusers.transactions', ['transactions' => []]);
    }

    public function downloadpaystub(Request $request, $verificationid)
    {
        // Placeholder for Plaid service call
        // return $this->plaidService->downloadPaystub(['income_verification_id' => $verificationid]);
        
        return response('Plaid service not yet migrated', 501);
    }

    public function pullPlaidBank(Request $request)
    {
        $userid = base64_decode($request->input('userid'));
        $plaids = PlaidUser::where('user_id', $userid)->where('paystub', 0)->get();

        return view('admin.elements.plaidusers.banks', compact('plaids', 'userid'))->with('modal', 'statementModal');
    }

    public function pullPlaidPaystub(Request $request)
    {
        $userid = base64_decode($request->input('userid'));
        $plaids = PlaidUser::where('user_id', $userid)->where('paystub', 1)->get();

        return view('admin.elements.plaidusers.paystub', compact('plaids', 'userid'))->with('modal', 'statementModal');
    }

    public function income(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function payrollincome(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function combinedincome(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function pullBankDetail(Request $request) { return $this->pendingResponse(__FUNCTION__); }

    public function admin_index(Request $request, $userid = null) { return $this->index($request, $userid); }
    public function admin_balance(Request $request) { return $this->balance($request); }
    public function admin_transactions(Request $request) { return $this->transactions($request); }
    public function admin_downloadpaystub(Request $request, $verificationid) { return $this->downloadpaystub($request, $verificationid); }
    public function admin_pullPlaidBank(Request $request) { return $this->pullPlaidBank($request); }
    public function admin_pullPlaidPaystub(Request $request) { return $this->pullPlaidPaystub($request); }
    public function admin_income(Request $request) { return $this->income($request); }
    public function admin_payrollincome(Request $request) { return $this->payrollincome($request); }
    public function admin_combinedincome(Request $request) { return $this->combinedincome($request); }
    public function admin_pullBankDetail(Request $request) { return $this->pullBankDetail($request); }
}
