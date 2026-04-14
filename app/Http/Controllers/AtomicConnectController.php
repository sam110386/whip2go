<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\AtomicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AtomicConnectController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index($userid, $active = true)
    {
        $ouruser = DB::table('users')->where('id', base64_decode($userid))->count();
        if (!$ouruser) {
            return redirect('/atomic/atomicconnect/error');
        }
        $tokenObj = $this->refreshToken(base64_decode($userid));
        if (!$tokenObj['status']) {
            return redirect('/atomic/atomicconnect/error');
        }
        $token = $tokenObj['token'];

        return view('atomic.connect.index', compact('userid', 'token', 'active'));
    }

    public function error()
    {
        return view('atomic.connect.error');
    }

    public function success()
    {
        return view('atomic.connect.success');
    }

    public function saveUser(Request $request)
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];
        if (!$request->input('isAjax')) {
            $return['msg'] = 'sorry wrong page';
            return response()->json($return);
        }

        $userId = !empty($request->input('userId')) ? base64_decode($request->input('userId')) : '';
        $customerId = trim($request->input('customerId'));
        $company = trim($request->input('company'));
        $companyId = trim($request->input('companyId'));
        $payrollId = trim($request->input('payrollId'));

        $userObj = DB::table('users')->where('id', $userId)->select('id')->first();
        if (empty($userObj)) {
            return response()->json($return);
        }

        $dataToSave = [
            'user_id' => $userId,
            'company' => $company,
            'customerId' => $customerId,
            'payrollId' => $payrollId,
            'companyId' => $companyId,
        ];

        $exists = DB::table('atomic_users')
            ->where('user_id', $userId)
            ->where('companyId', $companyId)
            ->select('id', 'income')
            ->first();

        if (!empty($exists)) {
            DB::table('atomic_users')->where('id', $exists->id)->update($dataToSave);
            $recordid = $exists->id;
        } else {
            $recordid = DB::table('atomic_users')->insertGetId($dataToSave);
        }

        $this->syncAccounts($userId);

        return response()->json([
            'status' => true,
            'msg' => 'You are successfully connected now',
            'recordid' => base64_encode($recordid),
        ]);
    }

    private function syncAccounts($userid): void
    {
        $atomicObj = new AtomicService();
        $allConnectedAccounts = $atomicObj->pullConnectedAccount($userid);
        if (isset($allConnectedAccounts['status']) && $allConnectedAccounts['status'] == false) {
            return;
        }
        foreach ($allConnectedAccounts['data'] ?? [] as $account) {
            $exists = DB::table('atomic_users')
                ->where('user_id', $userid)
                ->where('companyId', $account['company']['_id'])
                ->whereNull('linkedAccount')
                ->first();
            if (empty($exists)) {
                continue;
            }
            DB::table('atomic_users')->where('id', $exists->id)->update([
                'income' => 1,
                'linkedAccount' => $account['_id'],
            ]);
        }
    }

    public function saveTask(Request $request)
    {
        return response()->json(['status' => true, 'msg' => 'You are successfully connected now']);
    }

    private function refreshToken($userid): array
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];
        $requestBody = ['identifier' => AtomicService::$_identifier . $userid];
        $url = config('legacy.Atomic.apiHost') . '/access-token';
        $atomicObj = new AtomicService();
        $resp = $atomicObj->sendHttpRequest($url, $requestBody);
        if (empty($resp) || !isset($resp['data'])) {
            return $return;
        }
        return ['status' => true, 'msg' => 'You are successfully connected now', 'token' => $resp['data']['publicToken']];
    }
}
