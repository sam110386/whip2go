<?php

namespace App\Http\Controllers\Cloud;

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
            'message' => "CloudPlaidUsers::{$action} pending migration",
            'result' => (object)[],
        ]);
    }

    public function index(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect()->route('cloud.users.index');
        }

        $plaids = PlaidUser::where('user_id', $userid)->get();

        return view('cloud.plaidusers.index', compact('plaids', 'userid'));
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
        return view('cloud.plaidusers.balance', ['accounts' => []]);
    }

    public function downloadpaystub(Request $request, $verificationid)
    {
        // Placeholder for Plaid service call
        return response('Plaid service not yet migrated', 501);
    }

    public function transactions(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function income(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function payrollincome(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function pullPlaidBank(Request $request) { return $this->pendingResponse(__FUNCTION__); }
    public function pullPlaidPaystub(Request $request) { return $this->pendingResponse(__FUNCTION__); }

    public function cloud_balance(Request $request) { return $this->balance($request); }
    public function cloud_transactions(Request $request) { return $this->transactions($request); }
    public function cloud_downloadpaystub(Request $request, $verificationid) { return $this->downloadpaystub($request, $verificationid); }
    public function cloud_income(Request $request) { return $this->income($request); }
    public function cloud_payrollincome(Request $request) { return $this->payrollincome($request); }
    public function cloud_pullPlaidBank(Request $request) { return $this->pullPlaidBank($request); }
    public function cloud_pullPlaidPaystub(Request $request) { return $this->pullPlaidPaystub($request); }
}
