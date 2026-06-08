<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\CsVehicleIssue;
use App\Models\Legacy\CsVehicleIssueImage;
use App\Models\Legacy\CsOrder;
use App\Services\Legacy\InspektService;
use App\Http\Controllers\Traits\VehicleIssuesTrait;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class VehicleIssuesController extends LegacyAppController
{
    use VehicleIssuesTrait;
    public array $issueStatus = [
        '0' => 'NOT Resolved',
        '1' => 'Assigned',
        '2' => 'Hold',
        '3' => 'Resolved',
        '4' => 'Pending',
        '5' => 'LiabilityTransferredToDriver',
        '6' => 'DirectlyPaidByDriver',
        '7' => 'ChargedToDriverByDIA',
        '8' => 'PaidByOwner',
        '9' => 'Liability transferred to Uber/Lyft',
        '10' => 'Need to Report',
        '11' => '3rd party responsible-Hold',
        '12' => '3rd party responsible-Resolved',
    ];
    protected int $imageSize = 2097152;
    protected array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx'];

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $sessionLimitName = "vehicle_issues_limit";
        $title = "Outstanding Issues";

        if ($request->has('Search.ClearFilter')) {
            Cookie::queue(Cookie::forget('vehicle_issues_search'));
            return redirect('admin/vehicle_issues/index');
        }

        $cookies = json_decode($request->cookie('vehicle_issues_search'), true) ?? [];

        $vehicle_id = $request->input('Search.vehicle_id', $request->query('vehicle_id', $cookies['vehicle_id'] ?? ''));
        $type = $request->input('Search.type', $request->query('type', $cookies['type'] ?? ''));
        $status = $request->input('Search.status', $request->query('status', $cookies['status'] ?? ''));
        $user_id = $request->input('Search.user_id', $request->query('user_id', $cookies['user_id'] ?? ''));
        $renter_id = $request->input('Search.renter_id', $request->query('renter_id', $cookies['renter_id'] ?? ''));


        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            Session::put($sessionLimitName, $limit);
        } else {
            $limit = Session::get($sessionLimitName, $this->recordsPerPage ?? 10);
        }

        $query = CsVehicleIssue::with([
            'vehicle:id,vehicle_unique_id,vehicle_name',
            'user:id,first_name,last_name'
        ]);


        if (!empty($vehicle_id)) {
            $query->where('vehicle_id', $vehicle_id);
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if ($status !== '' && $status !== null) {
            $query->where('status', $status);
        } else {
            $status = '0';
            $query->where('status', 0);
        }

        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }

        if (!empty($renter_id)) {
            $query->where('renter_id', $renter_id);
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $direction);
        $vehicleissues = $query->paginate($limit);

        $data = compact('title', 'vehicleissues', 'vehicle_id', 'type', 'status', 'user_id', 'renter_id', 'limit');
        $data['issueStatus'] = $this->issueStatus;
        $data['VehicleIssueType'] = $this->commonService->getVehicleIssueType();

        if ($request->ajax()) {
            return view('admin.vehicle_issues._index', $data);
        }

        Cookie::queue('vehicle_issues_search', json_encode([
            'vehicle_id' => $vehicle_id,
            'type' => $type,
            'status' => $status,
            'user_id' => $user_id,
            'renter_id' => $renter_id,
        ]), 60 * 24 * 30);

        return view('admin.vehicle_issues.index', $data);
    }
    public function getVehicle(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json([], 403);
        }

        $vehicles = [];
        $id = $request->input('id', '');
        $searchTerm = $request->input('term', '');

        $query = Vehicle::select([
            'id',
            'vehicle_unique_id',
            'vehicle_name',
            'user_id',
            'last_mile'
        ]);


        $vehicleLists = !empty($id) ?
            $query->where('id', $id)->get() :
            $query->where(function ($q) use ($searchTerm) {
                $q->where('vehicle_unique_id', 'like', "%{$searchTerm}%")
                    ->orWhere('vehicle_name', 'like', "%{$searchTerm}%");
            })
                ->orderBy('vehicle_unique_id')
                ->limit(10)
                ->get();


        $vehicles = $vehicleLists->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'tag' => "{$vehicle->vehicle_unique_id}-{$vehicle->vehicle_name}",
                'user_id' => $vehicle->user_id,
                'last_mile' => $vehicle->last_mile,
            ];
        });

        return response()->json($vehicles);
    }
    public function roadside($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = 'Add Roadside Report';
        $vehicleIssue = null;

        if (!empty($id)) {
            $title = 'Update Roadside Report';
            $vehicleIssue = CsVehicleIssue::with('images')->findOrFail($id);
        }

        return view('admin.vehicle_issues.roadside', [
            'title' => $title,
            'vehicleIssue' => $vehicleIssue,
            'issueStatus' => $this->issueStatus,
        ]);
    }
    public function accident(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Accidental Report' : 'Add Accident Report';
        $vehicleIssue = null;

        if (!empty($id)) {
            $vehicleIssue = CsVehicleIssue::with('images')->findOrFail($id);
            $vehicleIssue->injury = !empty($vehicleIssue->injury) ? json_decode($vehicleIssue->injury, true) : [];
            $vehicleIssue->witness = !empty($vehicleIssue->witness) ? json_decode($vehicleIssue->witness, true) : [];
            $extraData = json_decode($vehicleIssue->extra ?? '{}', true);

            if (is_array($extraData)) {
                $vehicleIssue->forceFill($extraData);
            }
        }

        return view('admin.vehicle_issues.accident', [
            'title' => $title,
            'vehicleIssue' => $vehicleIssue,
            'issueStatus' => $this->issueStatus,
        ]);
    }
    public function mechanical(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Mechanical Issue' : 'Add Mechanical Issue';
        $vehicleIssue = null;

        if (!empty($id)) {
            $vehicleIssue = CsVehicleIssue::with('images')->findOrFail($id);
            $extraData = json_decode($vehicleIssue->extra ?? '{}', true);

            if (is_array($extraData)) {
                $vehicleIssue->forceFill($extraData);
            }
        }

        return view('admin.vehicle_issues.mechanical', [
            'title' => $title,
            'issueStatus' => $this->issueStatus,
            'vehicleIssue' => $vehicleIssue,
        ]);
    }
    public function delete($id)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);

        if ($id) {
            CsVehicleIssue::where('id', $id)->delete();
        }

        return redirect()->back()->with('success', 'Record deleted successfully');
    }
    public function saveImage(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $id = $request->input('id');
        $ftype = $request->input('type', 0);

        if (empty($id)) {
            return response()->json(['error' => 'Could not save uploaded file. Required parameter is missing']);
        }

        $file = $request->file('othervehicleimage') ?? $request->file('vehicleimage');

        if (!$file) {
            return response()->json(['error' => 'No files were uploaded.']);
        }

        $res = $this->handleUpload($file, $id, $ftype);
        return response()->json($res);
    }
    public function deleteImage(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $imageData = CsVehicleIssueImage::where('id', $request->input('key'))->first();

        if (!empty($imageData)) {
            $filePath = public_path('img/custom/vehicle_issue/' . $imageData->image);

            if (!empty($imageData->image) && file_exists($filePath)) {
                @unlink($filePath);
            }

            CsVehicleIssueImage::where('id', $imageData->id)->delete();
        }

        return response()->json(['success' => true, 'key' => '']);
    }
    public function saveAdd(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'CsVehicleIssue.vehicle_id' => 'required',
        ], [
            'CsVehicleIssue.vehicle_id.required' => 'Please choose the Vehicle.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        $input = $request->input('CsVehicleIssue', []);
        $input['extra'] = json_encode(['service_paid' => $input['service_paid'] ?? 0]);
        $vehicleIssue = CsVehicleIssue::updateOrCreate(
            ['id' => $request->input('CsVehicleIssue.id')],
            $input
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Saved successfully',
            'recordid' => $vehicleIssue->id
        ]);
    }
    public function saveAccident(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'CsVehicleIssue.vehicle_id' => 'required',
        ], [
            'CsVehicleIssue.vehicle_id.required' => 'Please choose the Vehicle.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        $input = $request->input('CsVehicleIssue', []);
        $timezone = session('default_timezone', 'UTC');
        $input['police_reported'] = isset($input['police_reported']) ? 1 : 0;
        $input['on_way_tolift'] = isset($input['on_way_tolift']) ? 1 : 0;
        $input['have_passenger'] = isset($input['have_passenger']) ? 1 : 0;
        $input['injury'] = !empty($input['injury']) ? json_encode($input['injury']) : '';
        $input['witness'] = !empty($input['witness']) ? json_encode($input['witness']) : '';

        $input['accident_datetime'] = !empty($input['accident_datetime'])
            ? Carbon::parse($input['accident_datetime'], $timezone)->setTimezone('UTC')->format('Y-m-d H:i:s')
            : null;

        $input['vehicle_seen_date'] = !empty($input['vehicle_seen_date'])
            ? date('Y-m-d', strtotime($input['vehicle_seen_date']))
            : null;

        $input['other_party_vehi_insuranceexp'] = !empty($input['other_party_vehi_insuranceexp'])
            ? date('Y-m-d', strtotime($input['other_party_vehi_insuranceexp']))
            : null;

        $input['other_party_driverlicexpdate'] = !empty($input['other_party_driverlicexpdate'])
            ? date('Y-m-d', strtotime($input['other_party_driverlicexpdate']))
            : null;

        $input['extra'] = json_encode(['service_paid' => $input['service_paid'] ?? 0]);

        $vehicleIssue = CsVehicleIssue::updateOrCreate(
            ['id' => $request->input('CsVehicleIssue.id')],
            $input
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Saved successfully',
            'recordid' => $vehicleIssue->id
        ]);
    }
    public function changeMyStatus(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $id = $this->decodeId($request->input('id'));
        $status = $request->input('status');

        if (!$id || !isset($this->issueStatus[$status])) {
            return response()->json(['status' => 'error', 'message' => 'Not valid input data']);
        }

        CsVehicleIssue::where('id', $id)->update(['status' => $status]);

        if ($status == 3) {
            $issue = CsVehicleIssue::where('id', $id)
                ->select('vehicle_id', 'type')
                ->first();

            if ($issue && $issue->type == 6) {
                $vehicle = Vehicle::where('id', $issue->vehicle_id)
                    ->select('last_mile', 'total_mileage')
                    ->first();

                if ($vehicle && $vehicle->last_mile >= $vehicle->total_mileage) {
                    $newMileage = $vehicle->last_mile + config('legacy.MaintenanceMonitoring.miles', 5000);
                    Vehicle::where('id', $issue->vehicle_id)
                        ->update([
                            'total_mileage' => $newMileage
                        ]);
                }
            }
        }

        $html = '<span class="dropdown-submenu">';
        $html .= $this->issueStatus[$status];
        $html .= '<a href="#"><i class="icon-gear"></i></a><ul class="dropdown-menu dropdown-menu-sm">';

        foreach ($this->issueStatus as $k => $issueStats) {
            if ($k == $status) {
                continue;
            }
            $html .= '<li><a href="#" onclick="changemystatus(\'' . base64_encode($id) . '\',' . $k . ')">' . $issueStats . '</a></li>';
        }

        $html .= '</ul></span>';

        return response()->json([
            'status' => 'success',
            'html' => $html,
            'recordid' => $id,
        ]);
    }
    public function cleaning(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Cleaning Request' : 'Add Cleaning Request';
        $vehicleIssue = null;

        if (!empty($id)) {
            $vehicleIssue = CsVehicleIssue::with('images')->findOrFail($id);
        }

        return view('admin.vehicle_issues.cleaning', [
            'title' => $title,
            'issueStatus' => $this->issueStatus,
            'vehicleIssue' => $vehicleIssue,
        ]);
    }
    public function maintenance(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Vehicle Maintenance Record' : 'Add Vehicle Maintenance Record';
        $vehicleIssue = null;

        if (!empty($id)) {
            $vehicleIssue = CsVehicleIssue::with('images')->where('type', 6)->findOrFail($id);
            $extraData = json_decode($vehicleIssue->extra ?? '{}', true);

            if (is_array($extraData)) {
                $vehicleIssue->forceFill($extraData);
            }
        }

        return view('admin.vehicle_issues.maintenance', [
            'title' => $title,
            'issueStatus' => $this->issueStatus,
            'vehicleIssue' => $vehicleIssue,
        ]);
    }
    public function saveMaintenance(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'CsVehicleIssue.vehicle_id' => 'required',
        ], [
            'CsVehicleIssue.vehicle_id.required' => 'Please choose the Vehicle.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        $input = $request->input('CsVehicleIssue', []);
        $nextOdometerCheckbox = !empty($input['next_service_odometer_checkbox']);
        $nextOdometer = $input['next_service_odometer'] ?? 0;
        $currentOdometer = $input['current_odometer'] ?? 0;
        $maintenanceMiles = config('legacy.MaintenanceMonitoring.miles', 5000);

        if ($nextOdometerCheckbox && $nextOdometer && ($input['status'] ?? '') != 3) {
            Vehicle::where('id', $input['vehicle_id'])
                ->update(['total_mileage' => $nextOdometer]);
        }

        if ($nextOdometerCheckbox && ($input['status'] ?? '') == 3) {
            $mileage = $nextOdometer ?: ($currentOdometer + $maintenanceMiles);
            Vehicle::where('id', $input['vehicle_id'])
                ->update(['total_mileage' => $mileage]);
        }

        $input['extra'] = json_encode([
            'vehicle_scheduled_for_service' => $input['vehicle_scheduled_for_service'] ?? '',
            'vehicle_serviced' => $input['vehicle_serviced'] ?? 0,
            'service_paid' => $input['service_paid'] ?? 0,
            'current_odometer' => $currentOdometer,
            'next_service_odometer' => $nextOdometer,
        ]);

        $vehicleIssue = CsVehicleIssue::updateOrCreate(
            ['id' => $request->input('CsVehicleIssue.id')],
            $input
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Saved successfully',
            'recordid' => $vehicleIssue->id
        ]);
    }
    public function inspectionScan(Request $request, $id = '')
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Inspection Scan Request' : 'Add Inspection Scan Request';

        if ($request->isMethod('post') || $request->isMethod('put')) {

            $validator = Validator::make($request->all(), [
                'CsVehicleIssue.vehicle_id' => 'required',
            ], [
                'CsVehicleIssue.vehicle_id.required' => 'Please choose the Vehicle.',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->with('error', $validator->errors()->first())
                    ->withInput();
            }

            $input = $request->input('CsVehicleIssue', []);
            $bookingId = $input['cs_order_id'] ?? '';
            $vehicleId = $input['vehicle_id'] ?? '';
            $bookingObj = null;

            if (!empty($bookingId)) {
                $bookingObj = CsOrder::select('id', 'user_id', 'renter_id', 'vehicle_id', 'parent_id')
                    ->where('id', $bookingId)
                    ->first();

                if ($bookingObj) {
                    $input['renter_id'] = $bookingObj->renter_id;
                    $input['vehicle_id'] = $bookingObj->vehicle_id;
                    $input['user_id'] = $bookingObj->user_id;
                    $input['cs_order_id'] = $bookingObj->id;
                }
            } elseif (!empty($vehicleId)) {
                $vehicleObj = Vehicle::select('id', 'user_id')->where('id', $vehicleId)->first();

                if ($vehicleObj) {
                    $input['renter_id'] = null;
                    $input['vehicle_id'] = $vehicleObj->id;
                    $input['user_id'] = $vehicleObj->user_id;
                    $input['cs_order_id'] = null;

                    $bookingObj = CsOrder::select('id', 'user_id', 'renter_id', 'vehicle_id', 'parent_id')
                        ->where('vehicle_id', $vehicleObj->id)
                        ->orderByDesc('id')
                        ->first();

                    if ($bookingObj) {
                        $input['renter_id'] = $bookingObj->renter_id;
                        $input['cs_order_id'] = $bookingObj->id;
                    }
                }
            }

            $input['type'] = 7;

            $vehicleIssue = CsVehicleIssue::updateOrCreate(
                ['id' => $request->input('CsVehicleIssue.id')],
                [
                    'vehicle_id' => $input['vehicle_id'] ?? null,
                    'user_id' => $input['user_id'] ?? null,
                    'renter_id' => $input['renter_id'] ?? null,
                    'cs_order_id' => $input['cs_order_id'] ?? null,
                    'status' => $input['status'] ?? 0,
                    'type' => 7,
                ]
            );

            if (empty($input['id'])) {
                $vehicleObj = Vehicle::select('id', 'vin_no')
                    ->where('id', $input['vehicle_id'] ?? null)
                    ->first();

                if ($vehicleObj) {
                    $reqObj = [
                        'vehicle_id' => $vehicleObj->id,
                        'vin_no' => $vehicleObj->vin_no,
                        'id' => $bookingObj ? $bookingObj->id : rand(11111, 99999),
                        'parent_id' => $bookingObj ? ($bookingObj->parent_id ?? '') : '',
                        'renter_id' => $input['renter_id'] ?? null,
                    ];

                    $resp = (new InspektService())->createTokenAndSave($reqObj);

                    if (!empty($resp['status'])) {
                        $vehicleIssue->update(['extra' => json_encode($resp['result'] ?? [])]);
                    } else {
                        Session::flash('error', $resp['message'] ?? 'Failed to generate inspection token.');
                    }
                }
            }

            return redirect('admin/vehicle_issues/index')->with('success', 'Request saved successfully');
        }

        $vehicleIssue = null;
        if (!empty($id)) {
            $vehicleIssue = CsVehicleIssue::with('images')->findOrFail($id);
            $extraData = json_decode($vehicleIssue->extra ?? '{}', true);

            if (is_array($extraData)) {
                $vehicleIssue->forceFill($extraData);
            }
        }

        return view('admin.vehicle_issues.inspection_scan', [
            'title' => $title,
            'issueStatus' => $this->issueStatus,
            'vehicleIssue' => $vehicleIssue,
        ]);
    }
    public function pendingBooking(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);

        if (empty($id)) {
            return redirect('admin/vehicle_issues/index')
                ->with('error', 'Sorry, new request is not allowed to create here, for Pending Booking');
        }

        $title = 'Update Pending Booking Related Request';

        if ($request->isMethod('put') || $request->isMethod('post')) {
            $vehicleIssue = CsVehicleIssue::where('type', 8)->findOrFail($id);

            $notes = $request->input('CsVehicleIssue.notes', '');
            $newStatus = $request->input('CsVehicleIssue.status', $vehicleIssue->status);
            $extra = !empty($vehicleIssue->extra) ? json_decode($vehicleIssue->extra, true) : [];
            $checklistArr = array_filter($extra, fn($k) => strpos($k, '_note') === false, ARRAY_FILTER_USE_KEY);
            $noteKey = current(array_keys($checklistArr));

            if ($noteKey) {
                $extra[$noteKey . '_note'] = $notes;
            } else {
                $extra['note'] = $notes;
            }

            $vehicleIssue->update([
                'status' => $newStatus,
                'extra' => json_encode($extra),
            ]);

            return redirect('admin/vehicle_issues/index')->with('success', 'Record updated successfully');
        }

        $vehicleIssue = CsVehicleIssue::where('type', 8)->findOrFail($id);
        $extra = !empty($vehicleIssue->extra) ? json_decode($vehicleIssue->extra, true) : [];

        if (is_array($extra)) {
            $vehicleIssue->forceFill($extra);
        }

        $notesArr = array_filter($extra, fn($k) => strpos($k, '_note') !== false, ARRAY_FILTER_USE_KEY);
        $checklist = current($extra) ?: '';
        $notes = current($notesArr) ?: '';

        return view('admin.vehicle_issues.pending_booking', [
            'title' => $title,
            'issueStatus' => $this->issueStatus,
            'vehicleIssue' => $vehicleIssue,
            'checklist' => $checklist,
            'notes' => $notes,
            'id' => base64_encode($id),
        ]);
    }
}
