<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\MeasureOneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeasureOneConnectController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index($userid)
    {
        $user = DB::table('users')->where('id', base64_decode($userid))->first();
        if (empty($user)) {
            return redirect('/measureone/connect/error');
        }
        $tokenObj = (new MeasureOneService())->createDataRequest((array) $user);
        if (!$tokenObj['status']) {
            return redirect('/measureone/connect/error');
        }
        $token = $tokenObj['result'];
        return view('measure_one.connect.index', compact('userid', 'token'));
    }

    public function insurance($userid, $orderruleid = '')
    {
        $user = DB::table('users')->where('id', base64_decode($userid))->first();
        if (empty($user) || empty($orderruleid)) {
            return redirect('/measureone/connect/error');
        }
        $tokenObj = (new MeasureOneService())->createInsuranceDataRequest((array) $user, $orderruleid);
        if (!$tokenObj['status']) {
            return redirect('/measureone/connect/error');
        }
        $token = $tokenObj['result'];
        return view('measure_one.connect.insurance', compact('userid', 'token', 'orderruleid'));
    }

    public function error()
    {
        return view('measure_one.connect.error');
    }

    public function success()
    {
        return view('measure_one.connect.success');
    }

    public function saveUser(Request $request)
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];
        if (!$request->isMethod('post')) {
            $return['msg'] = 'sorry wrong page';
            return response()->json($return);
        }
        $userId = !empty($request->input('userId')) ? base64_decode($request->input('userId')) : '';
        $detail = $request->input('detail', []);
        $individualId = trim($detail['individual_id'] ?? '');
        $datarequestId = trim($detail['datarequest_id'] ?? '');
        $datasourceId = trim($detail['datasource_id'] ?? '');
        $datasourceName = trim($detail['datasource_name'] ?? '');
        $externalId = trim($detail['external_id'] ?? '');
        $paystub = (int) $request->input('paystub');

        $userObj = DB::table('users')->where('id', $userId)->select('id')->first();
        if (empty($userObj)) return response()->json($return);
        if ($userId != $externalId) return response()->json($return);

        $dataToSave = [
            'user_id' => $userId, 'individual_id' => $individualId,
            'datarequest_id' => $datarequestId, 'datasource_id' => $datasourceId,
            'datasource_name' => $datasourceName, 'paystub' => $paystub,
        ];
        if ($paystub) {
            $dataToSave['status'] = 1;
        }
        $exists = DB::table('measureone_users')->where('user_id', $userId)->select('id', 'income', 'datasource_id')->first();
        if (!empty($exists)) {
            if (empty($exists->datasource_id)) {
                DB::table('measureone_users')->where('id', $exists->id)->update($dataToSave);
                $recordid = $exists->id;
            } else {
                $exist2 = DB::table('measureone_users')->where('user_id', $userId)->where('datasource_id', $datasourceId)->select('id', 'income')->first();
                if (!empty($exist2)) {
                    DB::table('measureone_users')->where('id', $exist2->id)->update($dataToSave);
                    $recordid = $exist2->id;
                } else {
                    $recordid = DB::table('measureone_users')->insertGetId($dataToSave);
                }
            }
        } else {
            $recordid = DB::table('measureone_users')->insertGetId($dataToSave);
        }

        return response()->json(['status' => true, 'msg' => 'You are successfully connected now', 'recordid' => base64_encode($recordid)]);
    }

    public function saveinsurance(Request $request)
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];
        if (!$request->isMethod('post')) {
            $return['msg'] = 'sorry wrong page';
            return response()->json($return);
        }
        $userId = !empty($request->input('userId')) ? base64_decode($request->input('userId')) : '';
        $orderruleid = !empty($request->input('orderruleid')) ? base64_decode($request->input('orderruleid')) : '';
        $detail = $request->input('detail', []);
        $individualId = trim($detail['individual_id'] ?? '');
        $datarequestId = trim($detail['datarequest_id'] ?? '');
        $datasourceId = trim($detail['datasource_id'] ?? '');
        $externalId = trim($detail['external_id'] ?? '');

        $userObj = DB::table('users')->where('id', $userId)->select('id')->first();
        if (empty($userObj)) return response()->json($return);
        if ($userId != $externalId) return response()->json($return);

        $dataToSave = [
            'order_id' => $orderruleid, 'account_id' => $individualId,
            'axle_client' => $datarequestId, 'policy' => $datasourceId,
            'type' => 'measureone', 'axle_status' => 6,
        ];
        $exists = DB::table('axle_status')->where('order_id', $orderruleid)->select('id', 'policy')->first();
        if (!empty($exists)) {
            DB::table('axle_status')->where('id', $exists->id)->update($dataToSave);
            $recordid = $exists->id;
        } else {
            $recordid = DB::table('axle_status')->insertGetId($dataToSave);
        }

        return response()->json(['status' => true, 'msg' => 'You are successfully connected now', 'recordid' => base64_encode($recordid)]);
    }
}
