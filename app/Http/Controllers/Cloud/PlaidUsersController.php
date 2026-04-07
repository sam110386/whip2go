<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Controller;
use App\Models\Legacy\PlaidUser;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PlaidUsersController extends Controller
{
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
}
