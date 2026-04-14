<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\MeasureOneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeasureOneWebhooksController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request)
    {
        $this->verifyWebhookAuth($request);

        $return = ['status' => false, 'message' => 'Sorry, request is not valid'];
        if (!$request->isMethod('post')) {
            return response()->json($return);
        }
        $postData = $request->getContent();
        Log::channel('daily')->info('MeasureOne Webhook', ['body' => $postData]);
        if (empty($postData)) {
            return response()->json($return);
        }
        $postData = json_decode($postData, true);
        if (empty($postData)) {
            return response()->json($return);
        }
        $validEvents = ['datasource.connected', 'datarequest.report_available', 'datarequest.report_error'];
        if (!in_array($postData['event'] ?? '', $validEvents) || empty($postData['individual_id']) || empty($postData['datarequest_id'])) {
            return response()->json($return);
        }

        $userId = $postData['external_id'] ?? '';
        $individualId = trim($postData['individual_id']);
        $datarequestId = trim($postData['datarequest_id'] ?? '');
        $datasourceId = trim($postData['datasource_id'] ?? '');
        $datasourceName = trim($postData['datasource_name'] ?? '');
        $transactionId = trim($postData['transaction_id'] ?? '');

        $userObj = DB::table('users')->where('id', $userId)->select('id')->first();
        if (empty($userObj)) {
            return response()->json($return);
        }

        if ($postData['event'] == 'datarequest.report_error') {
            $exists = DB::table('measureone_users')
                ->where('user_id', $userId)->whereIn('status', [0, 1])
                ->whereNull('transaction_id')->where('datarequest_id', $datarequestId)
                ->select('id', 'income', 'datasource_id', 'transaction_id')->first();
            if (!empty($exists)) {
                DB::table('measureone_users')->where('id', $exists->id)->update([
                    'datasource_id' => $datasourceId, 'datasource_name' => $datasourceName,
                    'transaction_id' => $transactionId, 'status' => 3,
                ]);
            }
            return response()->json(['status' => true, 'message' => 'You are successfully connected now']);
        }

        $dataToSave = [
            'user_id' => $userId, 'individual_id' => $individualId,
            'datarequest_id' => $datarequestId, 'datasource_id' => $datasourceId,
            'datasource_name' => $datasourceName,
        ];

        if ($postData['event'] == 'datarequest.report_available') {
            $exists = DB::table('measureone_users')
                ->where('user_id', $userId)->whereIn('status', [0, 1])
                ->select('id', 'income', 'datasource_id', 'transaction_id')->first();
            if (!empty($exists)) {
                $update = $dataToSave;
                $update['status'] = 2;
                if (!empty($transactionId)) {
                    $update['transaction_id'] = $transactionId;
                }
                DB::table('measureone_users')->where('id', $exists->id)->update($update);
                return response()->json(['status' => true, 'message' => 'You are successfully connected now']);
            }
        }

        $exists = DB::table('measureone_users')->where('user_id', $userId)->select('id', 'income', 'datasource_id')->first();
        $recordId = null;
        if (!empty($exists)) {
            if (empty($exists->datarequest_id)) {
                $recordId = $exists->id;
            } else {
                $exist2 = DB::table('measureone_users')->where('user_id', $userId)->where('datarequest_id', $datarequestId)->select('id', 'income')->first();
                if (!empty($exist2)) {
                    $recordId = $exist2->id;
                }
            }
        }

        if (!empty($datasourceId) && (empty($exists) || empty($datasourceName))) {
            $dataResourceObj = (new MeasureOneService())->searchEmployer($datasourceId);
            if ($dataResourceObj['status'] && isset($dataResourceObj['result'][0])) {
                $datasourceName = $dataResourceObj['result'][0]['institution_display_name'];
            }
            $dataToSave['datasource_name'] = $datasourceName;
        }

        try {
            if ($recordId) {
                DB::table('measureone_users')->where('id', $recordId)->update($dataToSave);
            } else {
                DB::table('measureone_users')->insert($dataToSave);
            }
            return response()->json(['status' => true, 'message' => 'You are successfully connected now']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    private function verifyWebhookAuth(Request $request): void
    {
        $authorization = $request->header('Authorization', '');
        $jwtToken = strtoupper(trim(str_replace('Basic', '', $authorization)));
        if (empty($jwtToken) || $jwtToken != config('legacy.MeasureOne.webhook_token')) {
            abort(400, json_encode(['status' => false, 'message' => 'Sorry, you are not authorized to access this end point']));
        }
    }
}
