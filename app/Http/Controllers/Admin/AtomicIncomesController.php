<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\AtomicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AtomicIncomesController extends LegacyAppController
{
    public function income(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, required input is missing', 'result' => ''];
        $userid = base64_decode($request->input('user'));
        if (empty($userid)) {
            return response()->json($return);
        }
        $connectedAccounts = DB::table('atomic_users')
            ->where('user_id', $userid)
            ->where('income', 1)
            ->where('linkedAccount', '!=', '')
            ->where('trash', 0)
            ->get();
        if ($connectedAccounts->isEmpty()) {
            $return['message'] = 'Sorry, user is not linked any income account with Atomic';
            return response()->json($return);
        }
        $atomicObj = new AtomicService();
        $income = [];
        foreach ($connectedAccounts as $account) {
            $atomicUrl = config('legacy.Atomic.apiHost') . '/income?identifier=' . AtomicService::$_identifier . $account->user_id . '&linkedAccount=' . $account->linkedAccount;
            $resp = $atomicObj->sendHttpRequest($atomicUrl, []);
            $income[$account->id]['data'] = (array) $account;
            if (isset($resp['data'][0])) {
                $income[$account->id]['income'] = $resp['data'][0];
                $income[$account->id]['error'] = false;
            } else {
                $income[$account->id]['error'] = 'There are some error in pulling income data';
            }
        }
        return response()->json(['status' => true, 'message' => 'success', 'result' => $income]);
    }

    public function statement(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, required input is missing', 'result' => ''];
        $id = $request->input('id');
        if (empty($id)) {
            return response()->json($return);
        }
        $account = DB::table('atomic_users')
            ->where('id', $id)->where('income', 1)
            ->where('linkedAccount', '!=', '')->where('trash', 0)
            ->first();
        if (empty($account)) {
            $return['message'] = 'Sorry, user is not linked any income account with Atomic';
            return response()->json($return);
        }
        $atomicObj = new AtomicService();
        $atomicUrl = config('legacy.Atomic.apiHost') . '/statements?identifier=' . AtomicService::$_identifier . $account->user_id . '&linkedAccount=' . $account->linkedAccount;
        $resp = $atomicObj->sendHttpRequest($atomicUrl, []);
        if (!isset($resp['data'][0])) {
            return response()->json(['status' => false, 'message' => 'There are some error in pulling income data', 'result' => []]);
        }
        return response()->json(['status' => true, 'message' => 'success', 'result' => $resp['data'][0]]);
    }

    public function getrecord(Request $request)
    {
        $userid = $request->input('userid');
        $atomicObj = DB::table('atomic_users')->where('user_id', $userid)->where('income', 1)->get();
        $return = ['status' => false, 'message' => "Sorry, User didnt add his bank details yet"];
        if ($atomicObj->isNotEmpty()) {
            $atomicView = view('admin.atomic._atomiclist', ['atomics' => $atomicObj])->render();
            $return = ['status' => true, 'message' => '', 'view' => $atomicView, 'atomicObj' => $atomicObj->toArray()];
        }
        return response()->json($return);
    }

    public function getAtomicbalance(Request $request)
    {
        $userid = $request->input('userid');
        $linkedAccount = $request->input('linkedAccount');
        $atomicUrl = config('legacy.Atomic.apiHost') . '/income?identifier=' . AtomicService::$_identifier . $userid . '&linkedAccount=' . $linkedAccount;
        $atomicObj = new AtomicService();
        $resp = $atomicObj->sendHttpRequest($atomicUrl, []);
        if (isset($resp['data'][0])) {
            return response()->json(['status' => true, 'message' => '', 'result' => $resp['data'][0]]);
        }
        return response()->json(['status' => false, 'message' => 'Sorry, something went wrong or atomic didnt return anything', 'result' => []]);
    }

    public function empstatement(Request $request)
    {
        $userid = $request->input('userid');
        $linkedAccount = $request->input('linkedAccount');
        $atomicObj = new AtomicService();
        $atomicUrl = config('legacy.Atomic.apiHost') . '/statements?identifier=' . AtomicService::$_identifier . $userid . '&linkedAccount=' . $linkedAccount;
        $resp = $atomicObj->sendHttpRequest($atomicUrl, []);
        if (!isset($resp['data'][0])) {
            return response()->json(['status' => false, 'message' => 'There are some error in pulling income data', 'result' => []]);
        }
        $transactionView = view('admin.atomic._statement', ['statements' => $resp['data'][0]['statements']])->render();
        return response()->json(['status' => true, 'message' => '', 'statement' => $transactionView]);
    }

    public function pullEmployer(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, required input is missing', 'result' => ''];
        $recordid = $request->input('recordid');
        if (empty($recordid)) {
            return response()->json($return);
        }
        $account = DB::table('atomic_users')
            ->where('id', $recordid)->where('income', 1)
            ->where('linkedAccount', '!=', '')->where('trash', 0)->first();
        if (empty($account)) {
            $return['message'] = 'Sorry, user is not linked any income account with Atomic';
            return response()->json($return);
        }
        $atomicObj = new AtomicService();
        $atomicUrl = $account->user_id . '?linkedAccount=' . $account->linkedAccount;
        $resp = $atomicObj->pullEmployer($atomicUrl);
        return response()->json(['status' => true, 'message' => 'success', 'result' => $resp]);
    }

    public function pullEmployeIdentity(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, required input is missing', 'result' => ''];
        $recordid = $request->input('recordid');
        if (empty($recordid)) {
            return response()->json($return);
        }
        $account = DB::table('atomic_users')
            ->where('id', $recordid)->where('income', 1)
            ->where('linkedAccount', '!=', '')->where('trash', 0)->first();
        if (empty($account)) {
            $return['message'] = 'Sorry, user is not linked any income account with Atomic';
            return response()->json($return);
        }
        $atomicObj = new AtomicService();
        $atomicUrl = $account->user_id . '?linkedAccount=' . $account->linkedAccount;
        $resp = $atomicObj->pullEmployeIdentity($atomicUrl);
        return response()->json(['status' => true, 'message' => 'success', 'result' => $resp]);
    }
}
