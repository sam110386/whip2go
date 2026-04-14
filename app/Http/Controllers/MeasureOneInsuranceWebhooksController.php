<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\ValidateInsuranceTrait;
use App\Services\Legacy\MeasureOneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeasureOneInsuranceWebhooksController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    use ValidateInsuranceTrait;

    public function index(Request $request)
    {
        $this->verifyWebhookAuth($request);

        $return = ['status' => false, 'message' => 'Sorry, request is not valid'];
        if (!$request->isMethod('post')) {
            return response()->json($return);
        }
        $postData = $request->getContent();
        Log::channel('daily')->info('MeasureOne Insurance Webhook', ['body' => $postData]);
        if (empty($postData)) return response()->json($return);

        $postData = json_decode($postData, true);
        if (empty($postData)) return response()->json($return);

        $validEvents = ['datasource.connected', 'datarequest.report_available', 'transaction.processed', 'datarequest.refresh_failed'];
        if (!in_array($postData['event'] ?? '', $validEvents) || empty($postData['individual_id']) || empty($postData['datarequest_id'])) {
            return response()->json($return);
        }

        $userId = $postData['external_id'] ?? '';
        $datarequestId = trim($postData['datarequest_id'] ?? '');
        $transactionId = trim($postData['transaction_id'] ?? '');

        $userObj = DB::table('users')->where('id', $userId)->select('id')->first();
        if (empty($userObj)) return response()->json($return);

        if (in_array($postData['event'], ['datarequest.refresh_failed', 'datarequest.report_error'])) {
            $exist = DB::table('axle_status')->where('axle_client', $datarequestId)->where('type', 'measureone')->select('id', 'policy', 'axle_status')->first();
            if (!empty($exist)) {
                DB::table('axle_status')->where('id', $exist->id)->update(['axle_status' => 0, 'expired_on' => date('Y-m-d')]);
            }
            return response()->json(['status' => true, 'message' => 'You are successfully connected now']);
        }

        if (in_array($postData['event'], ['datasource.connected', 'transaction.processed', 'datarequest.items_processed', 'datarequest.report_available'])) {
            $exist = DB::table('axle_status')->where('axle_client', $datarequestId)->where('type', 'measureone')
                ->select('id', 'order_id', 'policy', 'axle_status', 'access_token')->first();
            if (!empty($exist)) {
                $transactionId = !empty($transactionId) ? $transactionId : $exist->access_token;
                $policyStatus = (new MeasureOneService())->getInsuranceDetails(['transaction_id' => $transactionId]);
                $update = [];
                if (!$policyStatus['status']) {
                    $update['axle_status'] = 3;
                }
                $insuranceDetails = [];
                if ($policyStatus['status'] && ($policyStatus['result']['processing_status'] ?? '') == 'COMPLETED') {
                    $insuranceDetails = $this->insuranceDetails($exist->policy, $policyStatus['result']['insurance_details']);
                    $update['axle_status'] = ($policyStatus['result']['insurance_details']['status'] ?? '') == 'ACTIVE' ? 2 : 3;
                    $update['policy_details'] = json_encode([
                        'policy_number' => $insuranceDetails['policy_number'] ?? '',
                        'provider' => $insuranceDetails['insurance_provider']['name'] ?? '',
                        'start_date' => isset($insuranceDetails['coverage_period']['start_date']) ? date('Y-m-d H:i:s', $insuranceDetails['coverage_period']['start_date'] / 1000) : '',
                        'end_date' => isset($insuranceDetails['coverage_period']['end_date']) ? date('Y-m-d H:i:s', $insuranceDetails['coverage_period']['end_date'] / 1000) : '',
                        'premium' => $insuranceDetails['premium_amount']['amount'] ?? '',
                    ]);
                }
                if (!empty($update)) {
                    DB::table('axle_status')->where('id', $exist->id)->update($update);
                }
                if (!empty($insuranceDetails)) {
                    $this->validateInsurance($insuranceDetails, (array) $exist);
                }
                return response()->json(['status' => true, 'message' => 'You are successfully connected now step1']);
            }
        }

        return response()->json(['status' => true, 'message' => 'You are successfully connected now']);
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
