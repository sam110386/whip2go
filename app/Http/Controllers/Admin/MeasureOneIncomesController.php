<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\MeasureOneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeasureOneIncomesController extends LegacyAppController
{
    public function income(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, required input is missing', 'result' => ''];
        $userid = base64_decode($request->input('user'));
        if (empty($userid)) {
            return response()->json($return);
        }
        $connectedAccounts = DB::table('measureone_users')->where('user_id', $userid)->get();
        if ($connectedAccounts->isEmpty()) {
            $return['message'] = 'Sorry, user is not linked any income account with MeasureOne';
            return response()->json($return);
        }
        return response()->json(['status' => true, 'message' => 'success', 'result' => $connectedAccounts->toArray()]);
    }

    public function pullMeasureOneIncomeDetails(Request $request)
    {
        $recordid = $request->input('recordid');
        $measureOneObj = DB::table('measureone_users')->where('id', $recordid)->first();
        $return = ['status' => false, 'message' => "Sorry, User didnt add his bank details yet"];
        if (empty($measureOneObj)) {
            return response()->json($return);
        }
        if ($measureOneObj->status == 1) {
            $return['message'] = 'Sorry, Report is not available yet';
            return response()->json($return);
        }
        if ($measureOneObj->status == 0) {
            DB::table('measureone_users')->where('id', $measureOneObj->id)->update(['status' => 1]);
        }
        $libObj = (new MeasureOneService())->getIncomeEmploymentDetails((array) $measureOneObj);
        if (!$libObj['status']) {
            return response()->json($libObj);
        }
        $measureOneView = view('admin.measure_one._incomedetail', [
            'MeasureOneLibObj' => $libObj['result'],
            'MeasureOneObj' => $measureOneObj,
        ])->render();
        return response()->json(['status' => true, 'message' => '', 'view' => $measureOneView, 'MeasureOneObj' => $measureOneObj]);
    }

    public function getrecord(Request $request)
    {
        $userid = $request->input('userid');
        $measureOneObj = DB::table('measureone_users')->where('user_id', $userid)->where('status', 1)->get();
        $return = ['status' => false, 'message' => "Sorry, User didnt add his bank details yet"];
        if ($measureOneObj->isNotEmpty()) {
            $atomicView = view('admin.measure_one._list', ['MeasureOnes' => $measureOneObj])->render();
            $return = ['status' => true, 'message' => '', 'view' => $atomicView, 'MeasureOneObj' => $measureOneObj->toArray()];
        }
        return response()->json($return);
    }
}
