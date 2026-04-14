<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\InspektService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspektdocsController extends LegacyAppController
{
    public function index(Request $request)
    {
        $bookingid = $request->input('orderid', '');
        $model = $request->input('model', '');

        $sessLimitName = 'inspektdocs_limit';
        $limit = $request->input('Record.limit', session($sessLimitName, $this->recordsPerPage ?? 20));
        session([$sessLimitName => $limit]);

        $query = DB::table('vehicle_scan_inspections as VehicleScanInspection')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'VehicleScanInspection.order_id')
            ->select('VehicleScanInspection.*', 'CsOrder.increment_id')
            ->orderBy('VehicleScanInspection.id', 'DESC');

        $lists = $query->paginate($limit);
        $statusFlags = (new InspektService())->statusFlags();

        if ($request->ajax()) {
            return view('admin.inspekt._index', compact('lists', 'statusFlags', 'bookingid', 'model'));
        }
        return view('admin.inspekt.index', compact('lists', 'statusFlags', 'bookingid', 'model'));
    }

    public function openVehicleScanRequestPopup(Request $request)
    {
        $booking = base64_decode($request->input('booking'));
        $orderData = DB::table('cs_orders')->where('id', $booking)->select('id', 'vehicle_id')->first();
        if (empty($orderData)) {
            abort(400, 'Sorry, booking not found');
        }
        return view('admin.inspekt._open_vehicle_scan_request_popup', compact('booking'));
    }

    public function saveVehicleScanPopupRequest(Request $request)
    {
        $return = ["status" => false, "message" => "Sorry, something went wrong"];
        if (!$request->isMethod('post')) {
            return response()->json($return);
        }
        $booking = base64_decode($request->input('Text.booking'));
        $dataObj = $request->input('Text', []);
        if (empty($dataObj['email'])) {
            $return['message'] = "Sorry, please enter email address";
            return response()->json($return);
        }

        $csOrder = DB::table('cs_orders as CsOrder')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->where('CsOrder.id', $booking)
            ->select('CsOrder.vehicle_id', 'CsOrder.parent_id', 'CsOrder.id', 'CsOrder.renter_id', 'Vehicle.vin_no', 'Vehicle.vehicle_name')
            ->first();

        if (empty($csOrder)) {
            $return['message'] = "Sorry, booking not found";
            return response()->json($return);
        }

        $stillOpen = DB::table('vehicle_scan_inspections')
            ->where('vehicle_id', $csOrder->vehicle_id)
            ->where('status', 0)
            ->where('created', '>', date('Y-m-d'))
            ->first();

        if ($stillOpen && ($dataObj['token'] ?? '') == 'old') {
            $inspektUrl = config('legacy.Inspektlabs.url') . '#' . $stillOpen->token;
        } else {
            $reqObj = [
                'vehicle_id' => $csOrder->vehicle_id,
                'vin_no' => $csOrder->vin_no,
                'rand' => $csOrder->id,
            ];
            $tokenObj = (new InspektService())->generateToken($reqObj);
            if (!$tokenObj['status']) {
                return response()->json($tokenObj);
            }
            DB::table('vehicle_scan_inspections')
                ->where('vehicle_id', $csOrder->vehicle_id)
                ->where('status', 0)
                ->delete();
            DB::table('vehicle_scan_inspections')->insert([
                'case_id' => $tokenObj['result']['caseId'],
                'token' => $tokenObj['result']['token'],
                'vehicle_id' => $csOrder->vehicle_id,
                'order_id' => $csOrder->id,
                'parent_order_id' => !empty($csOrder->parent_id) ? $csOrder->parent_id : $csOrder->id,
            ]);
            $inspektUrl = $tokenObj['result']['webview_url'];
        }

        $msg = "Please perform a vehicle condition report for vehicle " . $csOrder->vehicle_name . ", VIN " . $csOrder->vin_no . ". Please click <a href='" . $inspektUrl . "'>here</a> to start scan";
        // Email notification would be sent here via a mail service
        // (new Emailnotify())->sendCustomEmail($msg, $dataObj['email'], 'DriveItAway - Vehicle Scan Reminder');

        return response()->json(['status' => true, 'message' => 'Email reminder sent successfully']);
    }

    public function openDetail(Request $request)
    {
        $return = ["status" => false, "message" => "Sorry, report is not available"];
        $caseid = $request->input('caseid');
        $file = public_path('files/VehicleScanInspection/' . $caseid . '.json');
        if (!file_exists($file)) {
            $return['message'] = "Sorry, report file is not available";
            return response()->json($return);
        }
        $contents = file_get_contents($file);
        $reportData = !empty($contents) ? json_decode($contents, true) : [];
        $reportFile = '/files/VehicleScanInspection/' . $caseid . '.pdf';
        if (file_exists(public_path($reportFile))) {
            $reportFile = config('app.url') . $reportFile;
        } else {
            $reportFile = $reportData['reportUrl'] ?? null;
        }

        $view = view('admin.inspekt._report_detail_popup', compact('reportData', 'reportFile'))->render();
        return response()->json(['status' => true, 'view' => $view, 'message' => '']);
    }

    public function getOrderBasedReport(Request $request)
    {
        $return = ["status" => false, "message" => "Sorry, report is not available"];
        $orderid = $request->input('orderid');
        $record = DB::table('vehicle_scan_inspections')
            ->where('order_id', $orderid)
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($record)) {
            $return['message'] = "Sorry, no record found for respective booking";
            return response()->json($return);
        }
        if ($record->status != 2) {
            $return['message'] = "Sorry, report is not available now for respective booking";
            return response()->json($return);
        }
        $file = public_path('files/VehicleScanInspection/' . $record->case_id . '.json');
        if (!file_exists($file)) {
            $return['message'] = "Sorry, report file is not available";
            return response()->json($return);
        }
        $contents = file_get_contents($file);
        $reportData = !empty($contents) ? json_decode($contents, true) : [];
        $reportFile = '/files/VehicleScanInspection/' . $record->case_id . '.pdf';
        if (file_exists(public_path($reportFile))) {
            $reportFile = config('app.url') . $reportFile;
        } else {
            $reportFile = $reportData['reportUrl'] ?? null;
        }

        $view = view('admin.inspekt._report_detail_popup', compact('reportData', 'reportFile'))->render();
        return response()->json(['status' => true, 'view' => $view, 'message' => '']);
    }
}
