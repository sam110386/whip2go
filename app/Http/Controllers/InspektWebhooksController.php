<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InspektWebhooksController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private array $_eventTypes = ['uploadSuccesful', 'Available', 'Rejected'];

    public function index(Request $request)
    {
        $this->verifyWebhookAuth($request);

        $postData = $request->getContent();
        Log::channel('daily')->info('Inspekt Webhook', ['body' => $postData]);

        if (empty($postData)) {
            return response()->json(["status" => false, "message" => "Sorry, body payload required"]);
        }
        $dataValues = json_decode($postData, true);
        if (empty($dataValues)) {
            return response()->json(["status" => false, "message" => "Sorry, body payload is not a valid json"]);
        }
        if (!isset($dataValues['eventType']) || !in_array($dataValues['eventType'], $this->_eventTypes)) {
            return response()->json(["status" => false, "message" => "Sorry, body payload must have eventType and valid values for it are " . implode(', ', $this->_eventTypes)]);
        }

        $event = $dataValues['eventType'];
        $caseId = $dataValues['caseId'];

        $exist = DB::table('vehicle_scan_inspections')->where('case_id', $caseId)->first();
        if (empty($exist)) {
            return response()->json(["status" => false, "message" => "Sorry, we couldnt find respective case id on our end."]);
        }

        $status = 0;
        if ($event == 'uploadSuccesful') $status = 1;
        if ($event == 'Available') $status = 2;
        if ($event == 'Rejected') $status = 3;

        $fullfilepath = public_path('files/VehicleScanInspection');
        if (!file_exists($fullfilepath)) {
            @mkdir($fullfilepath, 0755, true);
        }
        $filename = $caseId . '.json';
        file_put_contents($fullfilepath . '/' . $filename, $postData);

        DB::table('vehicle_scan_inspections')->where('id', $exist->id)->update(['status' => $status]);

        $vehicleIssue = DB::table('cs_vehicle_issues')
            ->where('cs_order_id', $exist->order_id)
            ->where('vehicle_id', $exist->vehicle_id)
            ->where('type', 7)
            ->where('status', 0)
            ->orderBy('id', 'desc')
            ->select('id')
            ->first();

        if (!empty($vehicleIssue)) {
            DB::table('cs_vehicle_issues')->where('id', $vehicleIssue->id)->update(['status' => 4]);
        }

        if ($status === 2 && !empty($dataValues['reportUrl'])) {
            $this->downloadReportFile($dataValues['reportUrl'], $caseId);
        }

        return response()->json(["status" => true, "message" => "Your request processed successfully"]);
    }

    private function verifyWebhookAuth(Request $request): void
    {
        $authorization = $request->header('Authorization', '');
        $jwtToken = strtoupper(trim(str_replace('Basic', '', $authorization)));
        if (empty($jwtToken) || $jwtToken != config('legacy.Inspektlabs.webhook_token')) {
            abort(400, json_encode(['status' => false, 'message' => 'Sorry, you are not authorized to access this end point']));
        }
    }

    private function downloadReportFile(string $reportUrl, string $caseId): void
    {
        $fullfilepath = public_path('files/VehicleScanInspection');
        if (!file_exists($fullfilepath)) {
            @mkdir($fullfilepath, 0755, true);
        }
        $fileData = @file_get_contents($reportUrl);
        if ($fileData !== false) {
            file_put_contents($fullfilepath . '/' . $caseId . '.pdf', $fileData);
        }
    }
}
