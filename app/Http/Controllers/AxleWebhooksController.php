<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\PenaltyInsuranceTrait;
use App\Http\Controllers\Traits\PolicyValidateTrait;
use App\Services\Legacy\AxleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AxleWebhooksController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    use PolicyValidateTrait, PenaltyInsuranceTrait;

    private array $_eventTypes = ['ignition.completed', 'account.disconnected', 'policy.modified', 'ignition.opened'];

    public function returnPage()
    {
        return response("<h2>Your insurance policy has been successfully connected to the program</h2>");
    }

    public function index(Request $request)
    {
        $postData = $request->getContent();
        Log::channel('daily')->info('Axle Webhook', ['body' => $postData]);

        if (empty($postData)) {
            return response()->json(["status" => false, "message" => "Sorry, body payload required"]);
        }
        $dataValues = json_decode($postData, true);
        if (empty($dataValues)) {
            return response()->json(["status" => false, "message" => "Sorry, body payload is not a valid json"]);
        }
        if (!isset($dataValues['type']) || !in_array($dataValues['type'], $this->_eventTypes)) {
            return response()->json(["status" => false, "message" => "Sorry, body payload must have event type and valid values for it are " . implode(', ', $this->_eventTypes)]);
        }

        $event = $dataValues['type'];
        $data = $dataValues['data'];
        $metadata = $data['metadata'] ?? [];
        if (empty($metadata)) {
            return response()->json(["status" => false, "message" => "Sorry, body payload dont have a valid metadata"]);
        }

        $exist = DB::table('axle_status')->where('order_id', $metadata['order_id'])->where('type', 'axle')->first();
        $existArr = $exist ? (array) $exist : ['order_id' => $metadata['order_id']];
        $isNew = empty($exist);

        if ($event == 'account.disconnected' && ($existArr['type'] ?? '') === 'axle') {
            DB::table('axle_status')->where('id', $existArr['id'] ?? 0)->update([
                'axle_client' => null, 'policy' => null, 'axle_status' => 0,
                'expired_on' => date('Y-m-d', strtotime($data['expirationDate'] ?? date('Y-m-d'))),
            ]);
        }

        if ($event == 'ignition.completed') {
            $exist = DB::table('axle_status')->where('order_id', $metadata['order_id'])->first();
            $existArr = $exist ? (array) $exist : ['order_id' => $metadata['order_id']];

            $updateData = [
                'order_id' => $metadata['order_id'],
                'axle_client' => $data['client'],
                'axle_authCode' => $data['authCode'],
                'axle_status' => 6,
                'type' => 'axle',
                'policy_details' => null,
                'extra' => null,
            ];

            $axleService = new AxleService();
            $policyStatus = $axleService->fetchAccountAndPolicyDetails(array_merge($existArr, $updateData));
            if (!($policyStatus['success'] ?? false)) {
                $updateData['axle_status'] = 3;
                Log::channel('daily')->info('Axle fetchAccountAndPolicyDetails issue', $policyStatus);
            }
            if ($policyStatus['success'] ?? false) {
                $updateData['axle_status'] = ($policyStatus['data']['isActive'] ?? false) == true ? 2 : 3;
            }
            if (!empty($policyStatus['access_token'])) {
                $updateData['access_token'] = $policyStatus['access_token'];
            }
            if (!empty($policyStatus['accountId'])) {
                $updateData['account_id'] = $policyStatus['accountId'];
            }
            if (!empty($policyStatus['accountId'])) {
                $merged = array_merge($existArr, $updateData);
                $axleObj = $axleService->fetchAccountDetails($merged);
                if ($axleObj['success'] ?? false) {
                    $updateData['policy'] = current($axleObj['data']['policies']);
                }
            }

            if ($isNew) {
                DB::table('axle_status')->insert($updateData);
                $existArr = array_merge($existArr, $updateData);
                $existArr['id'] = DB::getPdo()->lastInsertId();
            } else {
                DB::table('axle_status')->where('id', $existArr['id'])->update($updateData);
                $existArr = array_merge($existArr, $updateData);
            }

            if (!empty($existArr['policy'])) {
                $policy = $existArr['policy'];
                $policyResult = $axleService->fetchPolicyDetails($existArr, $policy);
                if (!($policyResult['success'] ?? false)) {
                    $existArr['axle_status'] = 3;
                }
                if ($policyResult['success'] ?? false) {
                    $existArr['axle_status'] = ($policyResult['data']['isActive'] ?? false) == true ? 2 : 3;
                    $existArr['policy_details'] = json_encode([
                        'policy_number' => $policyResult['data']['policyNumber'] ?? '',
                        'provider' => $policyResult['data']['carrier'] ?? '',
                        'start_date' => date('Y-m-d H:i:s', strtotime($policyResult['data']['effectiveDate'] ?? 'now')),
                        'end_date' => date('Y-m-d H:i:s', strtotime($policyResult['data']['expirationDate'] ?? 'now')),
                        'premium' => $policyResult['data']['premium'] ?? '',
                    ]);
                }
                DB::table('axle_status')->where('id', $existArr['id'])->update([
                    'axle_status' => $existArr['axle_status'],
                    'policy_details' => $existArr['policy_details'] ?? null,
                ]);
                $this->convertBookingInsuranceTypeIfPolicyExpired($policyResult['data'] ?? [], $existArr);
            }
        }

        if ($event == 'policy.modified' && ($existArr['type'] ?? '') === 'axle') {
            $policy = $data['ref'];
            $policyStatus = (new AxleService())->fetchPolicyDetails($existArr, $policy);
            if (!($policyStatus['success'] ?? false)) {
                $existArr['axle_status'] = 3;
            }
            if ($policyStatus['success'] ?? false) {
                $existArr['policy'] = $policy;
                $existArr['axle_status'] = ($policyStatus['data']['isActive'] ?? false) == true ? 2 : 3;
                $existArr['policy_details'] = json_encode([
                    'policy_number' => $policyStatus['data']['policyNumber'] ?? '',
                    'provider' => $policyStatus['data']['carrier'] ?? '',
                    'start_date' => date('Y-m-d H:i:s', strtotime($policyStatus['data']['effectiveDate'] ?? 'now')),
                    'end_date' => date('Y-m-d H:i:s', strtotime($policyStatus['data']['expirationDate'] ?? 'now')),
                    'premium' => $policyStatus['data']['premium'] ?? '',
                ]);
            }
            DB::table('axle_status')->where('id', $existArr['id'])->update([
                'axle_status' => $existArr['axle_status'],
                'policy' => $existArr['policy'] ?? null,
                'policy_details' => $existArr['policy_details'] ?? null,
            ]);
            $this->convertBookingInsuranceTypeIfPolicyExpired($policyStatus['data'] ?? [], $existArr);
        }

        return response()->json(["status" => true, "message" => "Your request processed successfully"]);
    }
}
