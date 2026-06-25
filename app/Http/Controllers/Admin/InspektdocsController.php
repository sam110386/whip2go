<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\VehicleScanInspection;
use App\Services\Legacy\Emailnotify;
use App\Services\Legacy\InspektService;
use App\Http\Controllers\Legacy\LegacyAppController;

class InspektdocsController extends LegacyAppController
{
    public function index(Request $request)
    {
        $sessLimitName = "inspektdocs_limit";
        $bookingid = $request->input('orderid');
        $model = $request->input('model');

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } else {
            $limit = session($sessLimitName, $this->recordsPerPage);
        }

        $lists = VehicleScanInspection::with(['csOrder:id,increment_id'])->latest('id')->paginate($limit);
        $statusFlags = (new InspektService())->statusFlags();

        if ($request->ajax()) {
            return view('admin.inspekt.elements._index', compact('lists', 'statusFlags', 'bookingid', 'model', 'limit'));
        }

        return view('admin.inspekt.index', compact('lists', 'statusFlags', 'bookingid', 'model', 'limit'));
    }
    public function openVehicleScanRequestPopup(Request $request)
    {
        $booking = $this->decodeId($request->input('booking'));

        try {
            $orderData = CsOrder::select('id', 'vehicle_id')
                ->where('id', $booking)
                ->first();

            if (!$orderData) {
                throw new \Exception('Sorry, booking not found');
            }

            return view('inspekt._open_vehicle_scan_request_popup', compact('booking'));

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }
    public function saveVehicleScanPopupRequest(Request $request)
    {
        $return = [
            "status" => false,
            "message" => "Sorry, something went wrong"
        ];

        if (!$request->isMethod('post')) {
            return response()->json($return);
        }

        $dataObj = $request->input('Text', []);
        $booking = $this->decodeId($dataObj['booking']);

        if (empty($dataObj['email'])) {
            $return['message'] = "Sorry, please enter email address";
            return response()->json($return);
        }

        $csOrder = CsOrder::with(['vehicle:id,vin_no,vehicle_name'])
            ->select('vehicle_id', 'parent_id', 'id', 'renter_id')
            ->where('id', $booking)
            ->first();

        if (!$csOrder) {
            $return['message'] = "Sorry, booking not found";
            return response()->json($return);
        }

        $stillOpen = VehicleScanInspection::where('vehicle_id', $csOrder->vehicle_id)
            ->where('status', 0)
            ->where('created', '>', date('Y-m-d'))
            ->first();

        if ($stillOpen && isset($dataObj['token']) && $dataObj['token'] === 'old') {
            $inspektUrl = config('legacy.Inspektlabs.url') . '#' . $stillOpen->token;
        } else {
            $reqObj = [
                'vehicle_id' => $csOrder->vehicle_id,
                'vin_no' => $csOrder?->vehicle?->vin_no,
                'rand' => $csOrder->id,
            ];

            $tokenObj = (new InspektService())->generateToken($reqObj);

            if (!$tokenObj['status']) {
                return response()->json($tokenObj);
            }

            VehicleScanInspection::where('vehicle_id', $csOrder->vehicle_id)
                ->where('status', 0)
                ->delete();

            VehicleScanInspection::create([
                "case_id" => $tokenObj['result']['caseId'],
                "token" => $tokenObj['result']['token'],
                'vehicle_id' => $csOrder->vehicle_id,
                'order_id' => $csOrder->id,
                'parent_order_id' => !empty($csOrder->parent_id) ? $csOrder->parent_id : $csOrder->id
            ]);

            $inspektUrl = $tokenObj['result']['webview_url'];
        }

        $msg = "Please perform a vehicle condition report for vehicle " . ($csOrder?->vehicle?->vehicle_name ?? '') . ", VIN " . ($csOrder?->vehicle?->vin_no ?? '') . ". Please click <a href='" . $inspektUrl . "'>here</a> to start scan";
        $subject = "DriveItAway - Vehicle Scan Reminder";

        (new Emailnotify())->sendCustomEmail($msg, $dataObj['email'], $subject);

        return response()->json([
            'status' => true,
            'message' => 'Email reminder sent successfully'
        ]);
    }
    public function openDetail(Request $request)
    {
        $caseId = $request->input('caseid');
        $jsonFilePath = public_path("files/VehicleScanInspection/{$caseId}.json");

        if (!File::exists($jsonFilePath)) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, report file is not available'
            ]);
        }

        $contents = File::get($jsonFilePath);
        $reportData = !empty($contents) ? json_decode($contents, true) : [];
        $pdfRelativePath = "files/VehicleScanInspection/{$caseId}.pdf";
        $pdfFilePath = public_path($pdfRelativePath);
        $reportFile = File::exists($pdfFilePath) ? asset($pdfRelativePath) : $reportData['reportUrl'] ?? '';
        $viewHtml = view('inspekt._report_detail_popup', compact('reportData', 'reportFile'))->render();

        return response()->json([
            'status' => true,
            'view' => $viewHtml,
            'message' => ''
        ]);
    }
    public function getOrderBasedReport(Request $request)
    {
        $orderId = $request->input('orderid');
        $record = VehicleScanInspection::where('order_id', $orderId)
            ->latest('id')
            ->first();

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, no record found for respective booking'
            ]);
        }

        if ($record->status != 2) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, report is not available now for respective booking'
            ]);
        }

        $caseId = $record->case_id;
        $jsonFilePath = public_path("files/VehicleScanInspection/{$caseId}.json");

        if (!File::exists($jsonFilePath)) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, report file is not available'
            ]);
        }

        $contents = File::get($jsonFilePath);
        $reportData = !empty($contents) ? json_decode($contents, true) : [];
        $pdfRelativePath = "files/VehicleScanInspection/{$caseId}.pdf";
        $pdfFilePath = public_path($pdfRelativePath);
        $reportFile = File::exists($pdfFilePath) ? asset($pdfRelativePath) : $reportData['reportUrl'] ?? '';
        $viewHtml = view('inspekt._report_detail_popup', compact('reportData', 'reportFile'))->render();

        return response()->json([
            'status' => true,
            'view' => $viewHtml,
            'message' => ''
        ]);
    }
}
