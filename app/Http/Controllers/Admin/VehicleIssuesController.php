<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\CsVehicleIssue;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class VehicleIssuesController extends LegacyAppController
{
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
    private int $imageSize = 2097152;
    private array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx'];

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
        $vehicleIssue = ['CsVehicleIssue' => [], 'CsVehicleIssueImage' => []];

        if (!empty($id)) {
            $issue = DB::table('cs_vehicle_issues')->where('id', $id)->first();
            if ($issue) {
                $data = (array) $issue;
                $extra = json_decode($data['extra'] ?? '{}', true) ?: [];
                $data = array_merge($data, $extra);
                $vehicleIssue['CsVehicleIssue'] = $data;
                $vehicleIssue['CsVehicleIssueImage'] = DB::table('cs_vehicle_issue_images')
                    ->where('cs_vehicle_issue_id', $id)->get()->map(fn($r) => (array) $r)->all();
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
            DB::table('cs_vehicle_issues')->where('id', $id)->delete();
        }

        return redirect()->back()->with('success', 'Record deleted successfully');
    }
    public function cleaning(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Cleaning Request' : 'Add Cleaning Request';
        $vehicleIssue = ['CsVehicleIssue' => [], 'CsVehicleIssueImage' => []];

        if (!empty($id)) {
            $issue = DB::table('cs_vehicle_issues')->where('id', $id)->first();
            if ($issue) {
                $vehicleIssue['CsVehicleIssue'] = (array) $issue;
                $vehicleIssue['CsVehicleIssueImage'] = DB::table('cs_vehicle_issue_images')
                    ->where('cs_vehicle_issue_id', $id)->get()->map(fn($r) => (array) $r)->all();
            }
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
        $vehicleIssue = ['CsVehicleIssue' => [], 'CsVehicleIssueImage' => []];

        if (!empty($id)) {
            $issue = DB::table('cs_vehicle_issues')->where('id', $id)->where('type', 6)->first();
            if ($issue) {
                $data = (array) $issue;
                $extra = json_decode($data['extra'] ?? '{}', true) ?: [];
                $data = array_merge($data, $extra);
                $vehicleIssue['CsVehicleIssue'] = $data;
                $vehicleIssue['CsVehicleIssueImage'] = DB::table('cs_vehicle_issue_images')
                    ->where('cs_vehicle_issue_id', $id)->get()->map(fn($r) => (array) $r)->all();
            }
        }

        return view('admin.vehicle_issues.maintenance', [
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
            $data = DB::table('cs_vehicle_issues')->where('id', $id)->where('type', 8)->first();
            if (empty($data)) {
                return redirect('admin/vehicle_issues/index')->with('error', 'Sorry, wrong attempt');
            }
            $row = (array) $data;
            $notes = $request->input('CsVehicleIssue.notes', '');
            $newStatus = $request->input('CsVehicleIssue.status', $row['status']);
            $extra = !empty($row['extra']) ? json_decode($row['extra'], true) : [];
            $checklistArr = array_filter($extra, fn($k) => strpos($k, '_note') === false, ARRAY_FILTER_USE_KEY);
            $noteKey = current(array_keys($checklistArr));
            $extra[$noteKey . '_note'] = $notes;

            DB::table('cs_vehicle_issues')->where('id', $id)->update([
                'status' => $newStatus,
                'extra' => json_encode($extra),
            ]);

            return redirect('admin/vehicle_issues/index')->with('success', 'Record updated successfully');
        }

        $data = DB::table('cs_vehicle_issues')
            ->where('id', $id)->where('type', 8)->first();
        if (empty($data)) {
            return redirect('admin/vehicle_issues/index')->with('error', 'Sorry, wrong attempt');
        }
        $row = (array) $data;
        $extra = !empty($row['extra']) ? json_decode($row['extra'], true) : [];
        $row['extra'] = $extra;
        $notesArr = array_filter($extra, fn($k) => strpos($k, '_note') !== false, ARRAY_FILTER_USE_KEY);
        $checklist = current($extra) ?: '';
        $notes = current($notesArr) ?: '';

        return view('admin.vehicle_issues.pending_booking', [
            'title' => $title,
            'issueStatus' => $this->issueStatus,
            'vehicleIssue' => ['CsVehicleIssue' => $row],
            'checklist' => $checklist,
            'notes' => $notes,
            'id' => base64_encode($id),
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
            $input = $request->input('CsVehicleIssue', []);
            $booking = $input['cs_order_id'] ?? '';
            $vehicle = $input['vehicle_id'] ?? '';
            $bookingObj = null;
            $vehicleObj = null;

            if (!empty($booking)) {
                $bookingObj = DB::table('cs_orders')->where('id', $booking)
                    ->select('id', 'user_id', 'renter_id', 'vehicle_id', 'parent_id')->first();
            }

            if (!empty($bookingObj)) {
                $input['renter_id'] = $bookingObj->renter_id;
                $input['vehicle_id'] = $bookingObj->vehicle_id;
                $input['user_id'] = $bookingObj->user_id;
                $input['cs_order_id'] = $bookingObj->id;
            }

            if (empty($booking) && !empty($vehicle)) {
                $vehicleObj = DB::table('vehicles')->where('id', $vehicle)->select('id', 'user_id')->first();
            }

            if (!empty($vehicleObj)) {
                $bookingObj = DB::table('cs_orders')
                    ->where('vehicle_id', $vehicleObj->id)
                    ->select('id', 'user_id', 'renter_id', 'vehicle_id', 'parent_id')
                    ->orderByDesc('id')->first();
                $input['renter_id'] = null;
                $input['vehicle_id'] = $vehicleObj->id;
                $input['user_id'] = $vehicleObj->user_id;
                $input['cs_order_id'] = null;
            }

            if (!empty($bookingObj)) {
                $input['renter_id'] = $bookingObj->renter_id;
                $input['cs_order_id'] = $bookingObj->id;
            }

            $input['type'] = 7;
            $isNew = empty($input['id']);

            if (!empty($input['id'])) {
                DB::table('cs_vehicle_issues')->where('id', $input['id'])->update(array_filter([
                    'vehicle_id' => $input['vehicle_id'] ?? null,
                    'user_id' => $input['user_id'] ?? null,
                    'renter_id' => $input['renter_id'] ?? null,
                    'cs_order_id' => $input['cs_order_id'] ?? null,
                    'status' => $input['status'] ?? 0,
                    'type' => 7,
                ], fn($v) => $v !== null));
                $recordId = $input['id'];
            } else {
                $recordId = DB::table('cs_vehicle_issues')->insertGetId([
                    'vehicle_id' => $input['vehicle_id'] ?? null,
                    'user_id' => $input['user_id'] ?? null,
                    'renter_id' => $input['renter_id'] ?? null,
                    'cs_order_id' => $input['cs_order_id'] ?? null,
                    'status' => $input['status'] ?? 0,
                    'type' => 7,
                    'created' => now(),
                    'modified' => now(),
                ]);
            }

            // TODO: Inspekt integration — generate token for new records
            // if ($isNew) {
            //     $vehicleObj = DB::table('vehicles')->where('id', $input['vehicle_id'])->first();
            //     $resp = (new \App\Services\Legacy\Inspekt())->createTokenAndSave([...]);
            //     if ($resp['status']) {
            //         DB::table('cs_vehicle_issues')->where('id', $recordId)->update(['extra' => json_encode($resp['result'])]);
            //     }
            // }

            return redirect('admin/vehicle_issues/index')->with('success', 'Request saved successfully');
        }

        $vehicleIssue = ['CsVehicleIssue' => []];
        if (!empty($id)) {
            $issue = DB::table('cs_vehicle_issues')->where('id', $id)->first();
            if ($issue) {
                $data = (array) $issue;
                $data['extra'] = !empty($data['extra']) ? json_decode($data['extra'], true) : [];
                $vehicleIssue['CsVehicleIssue'] = $data;
            }
        }

        return view('admin.vehicle_issues.inspection_scan', [
            'title' => $title,
            'issueStatus' => $this->issueStatus,
            'vehicleIssue' => $vehicleIssue,
        ]);
    }



    public function saveAdd(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $input = $request->input('CsVehicleIssue', []);
        if (empty($input['vehicle_id']) || empty($input['type'])) {
            return response()->json(['status' => 'error', 'message' => 'Not valid input data']);
        }

        $input['extra'] = json_encode(['service_paid' => $input['service_paid'] ?? 0]);

        $recordId = $this->saveIssue($input);

        // TODO: afterSave — create CsVehicleExpense record and Intercom ticket if applicable

        return response()->json(['status' => 'success', 'message' => 'Saved successfully', 'recordid' => $recordId]);
    }

    public function saveAccident(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $input = $request->input('CsVehicleIssue', []);
        if (empty($input['vehicle_id'])) {
            return response()->json(['status' => 'error', 'message' => 'Not valid input data']);
        }

        $timezone = session('default_timezone', 'UTC');
        $input['police_reported'] = isset($input['police_reported']) ? 1 : 0;
        $input['on_way_tolift'] = isset($input['on_way_tolift']) ? 1 : 0;
        $input['have_passenger'] = isset($input['have_passenger']) ? 1 : 0;
        $input['working_with_delivery'] = isset($input['working_with_delivery']) ? 1 : 0;
        $input['orders_from_delivery'] = isset($input['orders_from_delivery']) ? 1 : 0;
        $input['way_to_drop_off_delivery'] = isset($input['way_to_drop_off_delivery']) ? 1 : 0;
        $input['injury'] = !empty($input['injury']) ? json_encode($input['injury']) : '';
        $input['witness'] = !empty($input['witness']) ? json_encode($input['witness']) : '';
        $input['accident_datetime'] = !empty($input['accident_datetime'])
            ? Carbon::parse($input['accident_datetime'], $timezone)->setTimezone('UTC')->format('Y-m-d H:i:s')
            : null;
        $input['vehicle_seen_date'] = !empty($input['vehicle_seen_date'])
            ? date('Y-m-d', strtotime($input['vehicle_seen_date'])) : null;
        $input['other_party_vehi_insuranceexp'] = !empty($input['other_party_vehi_insuranceexp'])
            ? date('Y-m-d', strtotime($input['other_party_vehi_insuranceexp'])) : null;
        $input['other_party_driverlicexpdate'] = !empty($input['other_party_driverlicexpdate'])
            ? date('Y-m-d', strtotime($input['other_party_driverlicexpdate'])) : null;
        $input['extra'] = json_encode(['service_paid' => $input['service_paid'] ?? 0]);

        $recordId = $this->saveIssue($input);

        // TODO: afterSave — create CsVehicleExpense record and Intercom ticket if applicable

        return response()->json(['status' => 'success', 'message' => 'Saved successfully', 'recordid' => $recordId]);
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

        DB::table('cs_vehicle_issues')->where('id', $id)->update(['status' => $status]);

        if ($status == 3) {
            $issue = DB::table('cs_vehicle_issues')->where('id', $id)->select('vehicle_id', 'type')->first();
            if ($issue && $issue->type == 6) {
                $vehicle = DB::table('vehicles')->where('id', $issue->vehicle_id)
                    ->select('last_mile', 'total_mileage')->first();
                if ($vehicle && $vehicle->last_mile >= $vehicle->total_mileage) {
                    $newMileage = $vehicle->last_mile + config('legacy.MaintenanceMonitoring.miles', 5000);
                    DB::table('vehicles')->where('id', $issue->vehicle_id)
                        ->update(['total_mileage' => $newMileage]);
                }
            }
        }

        $html = '<span class="dropdown-submenu">';
        $html .= e($this->issueStatus[$status]);
        $html .= '<a href="#"><i class="icon-gear"></i></a><ul class="dropdown-menu dropdown-menu-sm">';
        foreach ($this->issueStatus as $k => $issueStats) {
            if ($k == $status)
                continue;
            $html .= '<li><a href="#" onclick="changemystatus(\'' . base64_encode($id) . '\',' . $k . ')">' . e($issueStats) . '</a></li>';
        }
        $html .= '</ul></span>';

        return response()->json([
            'status' => 'success',
            'html' => $html,
            'recordid' => $id,
        ]);
    }

    public function saveMaintenance(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $input = $request->input('CsVehicleIssue', []);
        if (empty($input['vehicle_id'])) {
            return response()->json(['status' => 'error', 'message' => 'Not valid input data']);
        }

        $nextOdometerCheckbox = !empty($input['next_service_odometer_checkbox']);
        $nextOdometer = $input['next_service_odometer'] ?? 0;
        $currentOdometer = $input['current_odometer'] ?? 0;
        $maintenanceMiles = config('legacy.MaintenanceMonitoring.miles', 5000);

        if ($nextOdometerCheckbox && $nextOdometer && ($input['status'] ?? '') != 3) {
            DB::table('vehicles')->where('id', $input['vehicle_id'])
                ->update(['total_mileage' => $nextOdometer]);
        }
        if ($nextOdometerCheckbox && ($input['status'] ?? '') == 3) {
            $mileage = $nextOdometer ?: ($currentOdometer + $maintenanceMiles);
            DB::table('vehicles')->where('id', $input['vehicle_id'])
                ->update(['total_mileage' => $mileage]);
        }

        $input['extra'] = json_encode([
            'vehicle_scheduled_for_service' => $input['vehicle_scheduled_for_service'] ?? '',
            'vehicle_serviced' => $input['vehicle_serviced'] ?? 0,
            'service_paid' => $input['service_paid'] ?? 0,
            'current_odometer' => $currentOdometer,
            'next_service_odometer' => $nextOdometer,
        ]);

        $recordId = $this->saveIssue($input);

        return response()->json(['status' => 'success', 'message' => 'Saved successfully', 'recordid' => $recordId]);
    }

    public function saveImage(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $id = $request->input('id');
        if (empty($id)) {
            return response()->json(['error' => 'Could not save uploaded file. Required parameter is missing']);
        }

        $file = $request->file('othervehicleimage') ?? $request->file('vehicleimage');
        if (!$file) {
            return response()->json(['error' => 'No files were uploaded.']);
        }

        $ftype = $request->input('type', 0);
        return response()->json($this->handleUpload($file, $id, $ftype));
    }

    public function deleteImage(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $imageData = DB::table('cs_vehicle_issue_images')->where('id', $request->input('key'))->first();
        if (!empty($imageData)) {
            $filePath = public_path('img/custom/vehicle_issue/' . $imageData->image);
            if (!empty($imageData->image) && file_exists($filePath)) {
                @unlink($filePath);
            }
            DB::table('cs_vehicle_issue_images')->where('id', $imageData->id)->delete();
        }

        return response()->json(['success' => true, 'key' => '']);
    }



    protected function handleUpload($file, int $issueid, int $ftype = 0): array
    {
        if ($file->getError()) {
            return ['error' => 'Upload Error #' . $file->getError()];
        }
        if ($file->getSize() == 0) {
            return ['error' => 'File is empty.'];
        }
        if ($file->getSize() > $this->imageSize) {
            return ['error' => 'File is too large.', 'preventRetry' => true];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $this->allowedExtensions)) {
            return ['error' => 'File has an invalid extension, it should be one of ' . implode(', ', $this->allowedExtensions) . '.'];
        }

        $imageCount = DB::table('cs_vehicle_issue_images')
            ->where('cs_vehicle_issue_id', $issueid)->count() + 1;

        $uploadDir = public_path('img/custom/vehicle_issue');
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = 'issue_' . $issueid . '_' . $imageCount . '.' . $ext;
        $file->move($uploadDir, $filename);

        $imageId = DB::table('cs_vehicle_issue_images')->insertGetId([
            'image' => $filename,
            'type' => $ftype,
            'cs_vehicle_issue_id' => $issueid,
        ]);

        return ['success' => true, 'key' => $imageId];
    }

    protected function saveIssue(array $input): int
    {
        $columns = [
            'vehicle_id',
            'user_id',
            'renter_id',
            'type',
            'status',
            'roadside_request_detail',
            'maintenance_issue_detail',
            'accident_datetime',
            'accident_location',
            'accident_description',
            'vehicle_damage_description',
            'vehicle_damage_location',
            'vehicle_seen_date',
            'vehicle_other_insurance',
            'police_reported',
            'police_reportno',
            'police_dept_name',
            'on_way_tolift',
            'have_passenger',
            'working_with_delivery',
            'orders_from_delivery',
            'way_to_drop_off_delivery',
            'other_vehicle_involved',
            'other_party_vehi_make',
            'other_party_vehi_model',
            'other_party_vehi_year',
            'other_party_vehi_vin',
            'other_party_vehi_insurancecompany',
            'other_party_vehi_insurance',
            'other_party_vehi_insuranceexp',
            'other_party_vehi_insurance_claim',
            'other_party_nameaddress',
            'other_party_phone',
            'other_party_driver',
            'other_party_driverphone',
            'other_party_driveradress',
            'other_party_driverlicense',
            'other_party_driverlicstate',
            'other_party_driverlicexpdate',
            'other_party_vehiclelocation',
            'other_party_damage_detail',
            'other_party_injury_details',
            'injury',
            'witness',
            'ccm_claim_number',
            'total_damage',
            'insurance_coverage',
            'company_cost',
            'vehicle_insurance_company_name',
            'vehicle_insurance',
            'claim_number',
            'service_paid',
            'notes',
            'extra',
            'cs_order_id',
        ];

        $data = array_intersect_key($input, array_flip($columns));

        if (!empty($input['id'])) {
            $data['modified'] = now();
            DB::table('cs_vehicle_issues')->where('id', $input['id'])->update($data);
            return (int) $input['id'];
        }

        $data['created'] = now();
        $data['modified'] = now();
        return DB::table('cs_vehicle_issues')->insertGetId($data);
    }
}
