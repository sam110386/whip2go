<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\User;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use App\Models\Legacy\VehicleSetting;
use App\Models\Legacy\DynamicFare;
use App\Services\Legacy\Colors;
use App\Services\Legacy\Free2MoveService;
use App\Services\Legacy\Passtime;
use App\Http\Controllers\Traits\VehiclesTrait;
use App\Http\Controllers\Traits\VehicleLocationTrait;
use App\Http\Controllers\Traits\CopyVehicleImageTrait;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class VehiclesController extends LegacyAppController
{
    protected $imageSize = 2097152;
    protected $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];

    use VehiclesTrait, VehicleLocationTrait, CopyVehicleImageTrait;
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        $title = 'Manage Vehicles';
        $sessionLimitName = "vehicles_limit";

        if (empty($admin['administrator'])) {
            return redirect('/admin/linked_vehicles/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        if ($request->has('Search.ClearFilter') || $request->has('ClearFilter')) {
            $request->offsetUnset('Search');
            Cookie::queue(Cookie::forget('vehicle_list_search'));
            $cookies = [];
        } else {
            $cookies = $request->cookie('vehicle_list_search', []);
            if (is_string($cookies)) {
                $cookies = json_decode($cookies, true) ?? [];
            }
        }

        $fieldname = $request->input('Search.searchin', $request->query('searchin', $cookies['searchin'] ?? 'All'));
        $keyword = $request->input('Search.keyword', $request->query('keyword', $cookies['keyword'] ?? ''));
        $show = $request->input('Search.show', $request->query('showtype', $cookies['show'] ?? ''));
        $user_id = $request->input('Search.user_id', $request->query('user_id', $cookies['user_id'] ?? ''));
        $type = $request->input('Search.type', $request->query('type', $cookies['type'] ?? ''));
        $visibility = $request->input('Search.visibility', $request->query('visibility', $cookies['visibility'] ?? ''));

        $options = [
            'vehicle_name' => "Car #",
            'vin_no' => "VIN #",
            'plate_number' => "Plate Number"
        ];

        $vehicleSatatus = $this->commonService->getVehicleStatus();
        $vehicleSatatus['10'] = "Waitlist";

        $query = Vehicle::query()->with('owner:id,first_name,last_name');

        if (!empty($keyword)) {
            if (trim($fieldname) === 'All') {
                $query->where(function ($q) use ($keyword) {
                    $q->where('vehicle_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('vin_no', 'LIKE', "%{$keyword}%");
                });
            } elseif (!empty(trim($fieldname))) {
                $query->where("{$fieldname}", 'LIKE', "%{$keyword}%");
            }
        }

        if (array_key_exists($show, $vehicleSatatus)) {
            if ($show == 10) {
                $query->where('waitlist', 1);
            } else {
                $query->where('status', $show);
            }
        }

        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }

        if (!empty($type)) {
            $query->where('is_featured', $type === 'featured' ? 1 : 0);
        }

        if (!empty($visibility)) {
            $query->where('visibility', $visibility);
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['vehicle_name', 'status'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $query->orderBy($sort, $direction);

        if ($request->input('export') === 'Export') {
            $vehicles = $query->get();
            return $this->exportToCsv($vehicles);
        }

        if (!$request->ajax()) {
            Cookie::queue('vehicle_list_search', json_encode(compact(
                'keyword',
                'show',
                'fieldname',
                'user_id',
                'type',
                'visibility'
            )), 60); // Store for 60 minutes
        }

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            Session::put($sessionLimitName, $limit);
        } else {
            $limit = Session::get($sessionLimitName, 50);
        }

        $vehicleDetails = $query->paginate($limit);

        if ($request->ajax()) {
            return view('admin.vehicles.elements.index', compact(
                'options',
                'title',
                'keyword',
                'show',
                'fieldname',
                'user_id',
                'type',
                'visibility',
                'vehicleDetails',
                'limit',
                'vehicleSatatus'
            ));
        }

        return view('admin.vehicles.index', compact(
            'options',
            'title',
            'keyword',
            'show',
            'fieldname',
            'user_id',
            'type',
            'visibility',
            'vehicleDetails',
            'limit',
            'vehicleSatatus'
        ));
    }

    public function add(Request $request, $vehicle_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $vehicleId = $this->decodeId($vehicle_id);
        $title = $vehicleId ? 'Edit Vehicle' : 'Add Vehicle';

        if ($request->isMethod('get')) {
            $vehicleData = null;
            $colors = (new Colors())->getColors();

            if ($vehicleId) {
                $vehicleData = Vehicle::with([
                    'csSetting:user_id,passtime,gps_provider',
                    'user:id,distance_unit',
                    'images:id,vehicle_id,filename,iorder,remote',
                    'locations:id,vehicle_id,lat,lng,address'
                ])->findOrFail($vehicleId);

                if (!empty($vehicleData->color)) {
                    $colors[$vehicleData->color] = $vehicleData->color;
                }

                if (!empty($vehicleData->interior_color)) {
                    $colors[$vehicleData->interior_color] = $vehicleData->interior_color;
                }
            }

            return view('admin.vehicles.add', ['listTitle' => $title, 'vehicle' => $vehicleData, 'colorOptions' => $colors]);
        }

        $allowedSize = $this->commonService->FileSizeInBytes(ini_get('upload_max_filesize'));
        $allowedSizeInKb = $allowedSize / 1024;
        $extensionString = implode(', ', $this->allowedExtensions);

        $validatedData = $request->validate([
            'Vehicle.vehicle_name' => 'bail|required|string',
            'Vehicle.vin_no' => 'bail|required|unique:vehicles,vin_no' . ($vehicleId ? ',' . $vehicleId : ''),
            'Vehicle.user_id' => 'bail|required|integer',

            // --- Image File Inputs ---
            'registration_image' => "nullable|file|mimes:{$extensionString}|max:{$allowedSizeInKb}",
            'insurance_image' => "nullable|file|mimes:{$extensionString}|max:{$allowedSizeInKb}",
            'inspection_image' => "nullable|file|mimes:{$extensionString}|max:{$allowedSizeInKb}",
        ], [
            'Vehicle.vehicle_name.required' => 'Please enter the Vehicle Name.',
            'Vehicle.vin_no.required' => 'Please enter VIN number.',
            'Vehicle.vin_no.unique' => 'Entered VIN number already registered.',
            'Vehicle.user_id.required' => 'Please enter Vehicle owner Id.',

            // Custom error messages for images (Optional, but gives you clean errors)
            'registration_image.mimes' => 'Registration image must be a valid file type (' . $extensionString . ').',
            'registration_image.max' => 'Registration image size cannot exceed ' . round($allowedSizeInKb / 1024, 2) . 'MB.',
            'insurance_image.mimes' => 'Insurance image must be a valid file type.',
            'insurance_image.max' => 'Insurance image size is too large.',
            'inspection_image.mimes' => 'Inspection image must be a valid file type.',
            'inspection_image.max' => 'Inspection image size is too large.',
        ]);

        $vehicleData = array_merge($request->input('Vehicle', []), $validatedData['Vehicle'] ?? []);
        $vehicleData['cab_type'] ??= 'Regular Sedan';
        $dateFields = [
            'insurance_policy_exp_date',
            'inspection_exp_date',
            'state_insp_exp_date',
            'reg_name_exp_date',
            'reg_name_date'
        ];

        foreach ($dateFields as $field) {
            if (!empty($vehicleData[$field])) {
                $vehicleData[$field] = Carbon::createFromFormat('m/d/Y', $vehicleData[$field])->format('Y-m-d');
            }
        }

        if (!empty($vehicleData['availability_date'])) {
            $vehicleData['availability_date'] = Carbon::parse($vehicleData['availability_date'])->format('Y-m-d');
        }

        $yearPart = !empty($vehicleData['year']) ? substr($vehicleData['year'], -2) . '-' : '';
        $makePart = !empty($vehicleData['make']) ? Str::slug($vehicleData['make'], '_') . '-' : '';
        $modelPart = !empty($vehicleData['model']) ? Str::slug($vehicleData['model'], '_') : '';
        $vinPart = !empty($vehicleData['vin_no']) ? '-' . substr($vehicleData['vin_no'], -6) : '';

        $vehicleData['vehicle_name'] = "{$yearPart}{$makePart}{$modelPart}{$vinPart}";
        $vehicleData['rate'] = (float) preg_replace("/[^0-9,.]/", "", $vehicleData['rate'] ?? 0);
        $vehicleData['status'] = 1;
        $vehicleData['rent_opt'] = "";

        if (($vehicleData['fare_type']) === 'D') {
            $vehicleData['day_rent'] = 0;
        }

        $vehicleData['vehicleCostInclRecon'] = (float) ($vehicleData['vehicleCostInclRecon'] ?? 0);
        $vehicleData['kbbnadaWholesaleBook'] = (float) ($vehicleData['kbbnadaWholesaleBook'] ?? 0);
        $vehicleData['doors'] = (int) ($vehicleData['doors'] ?? 0);
        $vehicleData['total_mileage'] = (int) ($vehicleData['total_mileage'] ?? 0);
        $vehicleData['allowed_miles'] = (float) ($vehicleData['allowed_miles'] ?? 0);
        $vehicleData['rate'] = (float) ($vehicleData['rate'] ?? 0);
        $vehicleData['day_rent'] = (float) ($vehicleData['day_rent'] ?? 0);
        $vehicleData['vin_no'] = strtoupper($vehicleData['vin_no'] ?? '');

        $vehicle = Vehicle::updateOrCreate(['id' => $vehicleId], $vehicleData);

        if (!$vehicleId) {
            $uniqueNo = ($vehicle->id < 999) ? '1' . sprintf('%04d', $vehicle->id) : $vehicle->id;
            $vehicle->update(['vehicle_unique_id' => $uniqueNo]);
        }

        $imageFields = ['registration_image', 'insurance_image', 'inspection_image'];
        $imageUpdateData = [];

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $suffix = str_replace('_image', '', $field);
                $extension = $file->getClientOriginalExtension();
                $filename = "vehi_{$$vehicle->id}_{$suffix}.{$extension}";
                $destinationPath = public_path('img/custom/vehicle_photo');
                $file->move($destinationPath, $filename);
                $imageUpdateData[$field] = $filename;
            }
        }

        if (!empty($imageUpdateData)) {
            $vehicle->update($imageUpdateData);
        }

        if ($vehicle->fare_type === 'D') {
            $farePayload = [
                'id' => $vehicle->id,
                'user_id' => $vehicle->user_id,
                'msrp' => $vehicle->msrp,
                'fare_type' => $vehicle->fare_type,
                'vehicleCostInclRecon' => $vehicle->vehicleCostInclRecon,
            ];

            DynamicFare::calculateDynamicFare($farePayload, 1);
        }

        if ($vehicle->fare_type === 'L') {
            Free2MoveService::fetchDynamicFare($vehicle->id, 1);
        }

        if ($request->has('VehicleLocation')) {
            $this->saveVehicleLocation($request->input('VehicleLocation'), $vehicle->id);
        }

        if (!$vehicleId) {
            return redirect('admin/vehicles/add/' . base64_encode($vehicle->id))->with('success', 'Vehicle data saved successfully');
        }

        return redirect('admin/vehicles/index')->with('success', 'Vehicle data updated successfully');
    }

    public function multiplAction(Request $request)
    {
        $statusAction = (string) $request->input('Vehicle.status', '');
        $selected = $request->input('select', []);

        if (!is_array($selected)) {
            $selected = [];
        }

        $ids = array_filter(array_map('intval', array_values($selected)));

        if (!empty($ids)) {
            if ($statusAction === 'active') {
                Vehicle::query()->whereIn('id', $ids)->update(['status' => 1]);
            } elseif ($statusAction === 'inactive') {
                Vehicle::query()->whereIn('id', $ids)->update(['status' => 0]);
            }
        }

        return redirect()->to($request->headers->get('referer') ?: '/admin/vehicles/index');
    }

    public function lastlocation($vehicle_id = null)
    {
        $vehicleId = $this->decodeId((string) $vehicle_id);
        $vehicle = Vehicle::with(['csSetting', 'vehicleSetting'])->find($vehicleId);

        if (!$vehicle) {
            return redirect('admin/vehicles/index')->with('error', 'Sorry, this vehicle data not found.');
        }

        $passtime = new Passtime();
        $vehicleLocation = $passtime->getVehicleLocation($vehicle);

        if (!$vehicleLocation['status']) {
            return redirect('admin/vehicles/index')->with('error', 'Sorry, this vehicle data not found.');
        }

        return view('admin.vehicles.lastlocation', compact('vehicleLocation'));
    }

    public function saveImage(Request $request)
    {
        $file = $request->file('vehicleimage');
        $vehicleId = $request->input('id');
        $return = $this->handleUpload($file, $vehicleId);
        return response()->json($return);
    }

    public function deleteImage(Request $request)
    {
        $return = ['success' => true, 'key' => ''];
        $imageId = $request->input('key');
        $vehicleImage = VehicleImage::find($imageId);

        if ($vehicleImage) {
            $filePath = public_path('img/custom/vehicle_photo/' . $vehicleImage->filename);

            if (!empty($vehicleImage->filename) && is_file($filePath)) {
                @unlink($filePath);
            }

            $vehicleImage->delete();
        }

        return response()->json($return);
    }

    public function checkVinDetails(Request $request)
    {
        $vin = $request->input('vin');
        $return = [
            'status' => 'error',
            'message' => 'Invalid Request or missing VIN',
            'result' => []
        ];

        if (!empty($vin)) {
            $vinInfo = $this->commonService->getVinDetails($vin);
            $return = [
                'status' => 'success',
                'message' => 'Record found',
                'result' => $vinInfo
            ];
        }

        return response()->json($return);
    }

    public function ownerautocomplete(Request $request)
    {
        $searchTerm = $request->query('term');
        $userId = $request->query('user_id');

        if (!empty($userId)) {
            $user = User::select('id', 'first_name', 'contact_number')
                ->where('id', $userId)
                ->first();

            if ($user) {
                return response()->json([
                    'id' => $user->id,
                    'tag' => $user->first_name . ' - ' . $user->contact_number
                ]);
            }

            return response()->json([]);
        }

        $userLists = User::select('id', 'first_name', 'contact_number')
            ->where('status', 1)
            ->where(function ($query) use ($searchTerm) {
                $query->where('contact_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
            })
            ->orderBy('first_name', 'ASC')
            ->limit(10)
            ->get();

        $users = $userLists->map(function ($user) {
            return [
                'id' => $user->id,
                'tag' => $user->first_name . ' - ' . $user->contact_number
            ];
        });

        return response()->json($users);
    }

    public function loadVehicleStatus(Request $request)
    {
        $vehicleId = $this->decodeId(trim($request->input('vehicleid')));
        $vehicle = Vehicle::select('id', 'status')->find($vehicleId);
        return view('admin.vehicles.load_vehicle_status', compact('vehicle'));
    }

    public function changeVehicleStatus(Request $request)
    {
        $vehicleId = $request->input('id');
        $status = (int) $request->input('status');

        $return = [
            'status' => true,
            'message' => 'Vehicle has been updated successfully',
            'vehicleid' => $vehicleId
        ];

        if (in_array($status, [8, 9])) {
            $vehicleData = Vehicle::select([
                'id',
                'user_id',
                'passtime_serialno',
                'autopi_unit_id',
                'passtime_status'
            ])->with(['csSetting', 'vehicleSetting'])
                ->find($vehicleId);

            if (!$vehicleData) {
                return response()->json(['status' => false, 'message' => 'Vehicle not found']);
            }

            if (empty($vehicleData->passtime_serialno)) {
                $return['message'] = 'Vehicle Passtime serial # not set';
                $return['status'] = false;
            }

            $csSetting = $vehicleData->csSetting;
            if (
                empty($csSetting?->passtime) ||
                ($csSetting->passtime === 'passtime' && empty($csSetting->passtime_dealerid)) ||
                ($csSetting->passtime === 'ituran' && empty($csSetting->ituran_usr))
            ) {
                $return['message'] = 'Vehicle Owner\'s GPS provider setting not set';
                $return['status'] = false;
            }

            if ($status === 8 && $vehicleData->passtime_status == 0) {
                $return['message'] = "Vehicle's Starter already Disabled";
                $return['status'] = false;
            } elseif ($status === 9 && $vehicleData->passtime_status == 1) {
                $return['message'] = "Vehicle's Starter already enabled";
                $return['status'] = false;
            }

            if (!empty($vehicleData->passtime_serialno) && $return['status']) {
                $passtime = new Passtime();

                $resp = ($status === 8)
                    ? $passtime->deActivateVehicle($vehicleData)
                    : $passtime->ActivateVehicle($vehicleData);

                if ($resp['status']) {
                    $vehicleData->passtime_status = ($status === 8) ? 0 : 1;
                    $vehicleData->saveQuietly();
                    $return['status'] = true;
                } else {
                    $return['status'] = false;
                    $return['message'] = $resp['message'] ?? 'External provider API communication failed.';
                }
            }

            return response()->json($return);
        }

        if (in_array($status, [11, 12])) {
            Vehicle::where('id', $vehicleId)->updateQuietly([
                'trash' => ($status === 11) ? 1 : 0
            ]);

            return response()->json($return);
        }

        $vehicle = Vehicle::find($vehicleId);

        if ($vehicle) {
            $vehicle->status = $status;
            $vehicle->save();
        }

        return response()->json($return);
    }

    public function loadSingleRow(Request $request)
    {
        $vehicleId = trim($request->input('vehicleid'));
        $vehicle = Vehicle::with('owner:id,first_name,last_name')->find($vehicleId);
        $vehicleSatatus = $this->commonService->getVehicleStatus();
        return view('admin.vehicles.load_single_row', compact('vehicle', 'vehicleSatatus'));
    }

    public function changePasstimeVehicleStatus(Request $request)
    {
        $vehicleId = $this->decodeId($request->input('vehicleid'));
        $status = trim($request->input('status'));
        $responseData = [
            'status' => false,
            'message' => 'Something went wrong',
            'vehicleid' => $vehicleId
        ];

        if (!$vehicleId) {
            return response()->json($responseData);
        }

        $vehicle = Vehicle::select([
            'id',
            'user_id',
            'passtime_serialno',
            'autopi_unit_id',
            'passtime_status'
        ])->with(['csSetting', 'vehicleSetting'])->find($vehicleId);

        if (!$vehicle) {
            $responseData['message'] = 'Vehicle not found';
            return response()->json($responseData);
        }

        if (empty($vehicle->passtime_serialno)) {
            $responseData['message'] = 'Vehicle Passtime serial # not set';
            return response()->json($responseData);
        }

        $csSetting = $vehicle->csSetting;

        if (
            empty($csSetting?->passtime) ||
            ($csSetting->passtime === 'passtime' && empty($csSetting->passtime_dealerid)) ||
            ($csSetting->passtime === 'ituran' && empty($csSetting->ituran_usr))
        ) {
            $responseData['message'] = "Vehicle Owner's GPS provider setting not set";
            return response()->json($responseData);
        }

        $passtimeService = new Passtime();

        if ($status === 'active') {
            $resp = $passtimeService->ActivateVehicle($vehicle);
            if (!empty($resp['status'])) {
                $vehicle->updateQuietly(['passtime_status' => 1]);
                $responseData['status'] = true;
                $responseData['message'] = 'Vehicle activated successfully';
            }
        }

        if ($status === 'inactive') {
            $resp = $passtimeService->deActivateVehicle($vehicle);
            if (!empty($resp['status'])) {
                $vehicle->updateQuietly(['passtime_status' => 0]);
                $responseData['status'] = true;
                $responseData['message'] = 'Vehicle deactivated successfully';
            }
        }

        return response()->json($responseData);
    }

    public function reorderImage(Request $request)
    {
        $stacks = $request->input('stack', []);
        $responseData = ['success' => true];

        if (!empty($stacks) && is_array($stacks)) {
            DB::transaction(function () use ($stacks) {
                $order = 1;

                foreach ($stacks as $stack) {
                    if (isset($stack['key'])) {
                        VehicleImage::where('id', $stack['key'])->update(['iorder' => $order++]);
                    }
                }
            });
        }

        return response()->json($responseData);
    }

    public function getVehicleRegistration(Request $request)
    {
        $vehicleId = $this->decodeId($request->input('vehicleid'));
        $responseData = [
            'status' => false,
            'message' => 'Invalid Vehicle ID',
            'result' => []
        ];

        if (empty($vehicleId)) {
            return response()->json($responseData);
        }

        $vehicle = Vehicle::select('registration_image')->find($vehicleId);

        if (!$vehicle || empty($vehicle->registration_image)) {
            $responseData['message'] = 'sorry, document not added yet by owner';
            return response()->json($responseData);
        }

        $relativePath = "img/custom/vehicle_photo/{$vehicle->registration_image}";
        $absolutePath = public_path($relativePath);

        if (File::exists($absolutePath)) {
            $responseData = [
                'status' => true,
                'message' => 'Success',
                'result' => [
                    'file' => asset($relativePath)
                ]
            ];
        } else {
            $responseData['message'] = 'sorry, document not exists';
        }

        return response()->json($responseData);
    }

    public function rental_setting(Request $request, $id = null)
    {
        $id = $this->decodeId($id);
        $listTitle = !empty($id) ? 'Update Rental Fee Setting' : 'Add Rental Fee Setting';

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $inputData = $request->all();

            $validator = Validator::make($inputData['DepositRule'], [
                'vehicle_id' => 'bail|required|integer',
            ], [
                'vehicle_id.required' => 'Please choose the Vehicle.',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            if (($inputData['DepositRule']['deposit_event'] ?? '') === 'N') {
                $inputData['DepositRule']['deposit_amt'] = 0;
            }

            $depositAmt = $inputData['DepositRule']['deposit_amt'] ?? 0;
            $depositAmtOpt = collect($inputData['DepositRule']['deposit_amt_opt'] ?? []);
            $totalDepositAmtSum = $depositAmtOpt->sum('amount');

            $inputData['DepositRule']['total_deposit_amt'] = $depositAmt + $totalDepositAmtSum;
            $inputData['DepositRule']['deposit_amt_opt'] = $totalDepositAmtSum ? json_encode(array_values($depositAmtOpt->toArray())) : "";

            $initialFee = $inputData['DepositRule']['initial_fee'] ?? 0;
            $initialFeeOpt = collect($inputData['DepositRule']['initial_fee_opt'] ?? []);
            $totalInitialFeeSum = $initialFeeOpt->sum('amount');

            $inputData['DepositRule']['total_initial_fee'] = $initialFee + $totalInitialFeeSum;
            $inputData['DepositRule']['initial_fee_opt'] = $totalInitialFeeSum ? json_encode(array_values($initialFeeOpt->toArray())) : "";

            $prepaidData = $inputData['DepositRule']['prepaid_initial_fee_data'] ?? [];

            if (
                !empty($inputData['DepositRule']['prepaid_initial_fee']) &&
                !empty($prepaidData['amount']) &&
                !empty($prepaidData['day'])
            ) {
                $inputData['DepositRule']['prepaid_initial_fee_data'] = json_encode($prepaidData);
                $inputData['DepositRule']['prepaid_initial_fee'] = 1;
            } else {
                $inputData['DepositRule']['prepaid_initial_fee_data'] = null;
                $inputData['DepositRule']['prepaid_initial_fee'] = 0;
            }

            try {
                DB::transaction(function () use ($inputData, $id) {
                    $depositRuleId = $inputData['DepositRule']['id'] ?? null;
                    DepositRule::updateOrCreate(['id' => $depositRuleId], $inputData['DepositRule']);

                    $rentOpt = $inputData['Vehicle']['rent_opt'] ?? [];
                    $filteredRentOpt = collect($rentOpt)->map(fn($item) => array_filter($item))->filter()->toArray();
                    $dayRent = preg_replace("/[^0-9,.]/", "", $inputData['Vehicle']['day_rent'] ?? '');
                    $rate = preg_replace("/[^0-9,.]/", "", $inputData['Vehicle']['rate'] ?? '');
                    $vehicle = Vehicle::find($id);

                    if ($vehicle) {
                        $vehicle->updateQuietly([
                            'rent_opt' => !empty($filteredRentOpt) ? json_encode($filteredRentOpt) : json_encode([]),
                            'day_rent' => $dayRent,
                            'rate' => $rate,
                            'fare_type' => $inputData['Vehicle']['fare_type'] ?? null,
                            'auth_require' => $inputData['Vehicle']['auth_require'] ?? null,
                        ]);
                    }

                    if (isset($inputData['Vehicle']['updatebooking']) && $dayRent) {
                        $activeBooking = CsOrder::select('id', 'parent_id')
                            ->where('vehicle_id', $id)
                            ->where('status', 1)
                            ->first();

                        if ($activeBooking) {
                            $bookingId = !empty($activeBooking->parent_id) ? $activeBooking->parent_id : $activeBooking->id;
                            OrderDepositRule::where('cs_order_id', $bookingId)->update(['rental' => $dayRent]);
                        }
                    }

                    if (empty($depositRuleId)) {
                        session()->flash('success', 'Rule has been added successfully.');
                    } else {
                        session()->flash('success', 'Rule has been updated successfully.');
                    }
                });

            } catch (\Exception $e) {
                //
            }
        }

        $formData = [];
        $vehicle = null;

        if (!empty($id) && !$request->isMethod('post') && !$request->isMethod('put')) {
            $vehicle = Vehicle::select([
                'id',
                'vehicle_unique_id',
                'rent_opt',
                'rate',
                'day_rent',
                'auth_require',
                'fare_type',
                'user_id'
            ])->with('depositRule')->find($id);

            $toArrayFormat = fn($val) => is_array($val) ? $val : (json_decode($val ?? '', true) ?? []);
            $vehicle->rent_opt = $toArrayFormat($vehicle->rent_opt);

            if ($rule = $vehicle->depositRule) {
                $vehicle->depositRule->deposit_amt_opt = $toArrayFormat($vehicle->depositRule->deposit_amt_opt);
                $vehicle->depositRule->initial_fee_opt = $toArrayFormat($vehicle->depositRule->initial_fee_opt);
                $vehicle->depositRule->prepaid_initial_fee_data = $toArrayFormat($vehicle->depositRule->prepaid_initial_fee_data);
            }
        }

        return view('admin.vehicles.rental_setting', compact('id', 'listTitle', 'vehicle'));
    }

    public function getVehicleDynamicFare(Request $request)
    {
        return $this->_getVehicleDynamicFare($request);
    }
    public function getvehicledetails(Request $request)
    {
        $vehicleId = $this->decodeId($request->input('vehicleid', ''));
        $orderId = $this->decodeId($request->input('orderid', ''));

        $vehicle = Vehicle::select([
            'id',
            'plate_number',
            'inspection_image',
            'registration_image',
            'gps_serialno',
            'passtime_serialno',
            'registered_state',
            'reg_name_date',
            'reg_name_exp_date',
            'wireless_gps_serial'
        ])
            ->where('id', $vehicleId)
            ->first();

        $stateopt = [
            [
                'text' => 'United States',
                'children' => $this->commonService->getStates()
            ],
            [
                'text' => 'Canada',
                'children' => $this->commonService->getCanadaStates()
            ]
        ];

        return view('vehicles.getvehicledetails', compact('vehicle', 'orderId', 'stateopt', ));
    }

    public function updateVehicleDetails(Request $request)
    {
        $return = ["status" => false, "message" => "Sorry, something went wrong."];

        if (!$request->ajax() || empty($request->all())) {
            return response()->json($return);
        }

        if ($request->has('pk') && !empty($request->input('pk'))) {
            $vehicle = Vehicle::find($request->input('pk'));

            if ($vehicle) {
                $fieldName = $request->input('name');
                $vehicle->$fieldName = $request->input('value');
                $vehicle->timestamps = false;
                $vehicle->save();

                return response()->json(["status" => true]);
            }

            return response()->json($return);
        }

        $vehicleId = $request->input('Vehicle.id');
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            return response()->json($return);
        }

        $maxSizeInBytes = $this->commonService->FileSizeInBytes(ini_get('upload_max_filesize'));
        $imageTypes = [
            'registration_image' => 'registration',
            'insurance_image' => 'insurance',
            'inspection_image' => 'inspection'
        ];

        foreach ($imageTypes as $inputName => $prefix) {
            if ($request->hasFile("Vehicle.{$inputName}")) {
                $file = $request->file("Vehicle.{$inputName}");

                if (
                    in_array(strtolower($file->getClientOriginalExtension()), $this->allowedExtensions) &&
                    $file->getSize() <= $maxSizeInBytes
                ) {
                    $fileFormat = $file->getClientOriginalExtension();
                    $fileName = "vehi_{$vehicleId}_{$prefix}.{$fileFormat}";
                    $file->move(public_path('img/custom/vehicle_photo'), $fileName);

                    $vehicle->$inputName = $fileName;

                    $return['status'] = true;

                } else if ($file->getSize() > $maxSizeInBytes) {
                    $return['message'] = "Sorry {$prefix} image could not be uploaded, it must be in proper size";
                    return response()->json($return);
                }
            }
        }

        $vehicle->timestamps = false;

        if ($vehicle->save()) {
            $return['status'] = true;
            unset($return['message']);
        }

        return response()->json($return);
    }

    public function getVehicleGps(Request $request)
    {
        if (!$request->ajax() || !$request->has(['vehicleid', 'type'])) {
            return response()->json([
                "status" => false,
                "message" => "Sorry, something went wrong."
            ]);
        }

        $result = $this->_getVehicleGps($request->input('vehicleid'), $request->input('type'));
        return response()->json($result);
    }

    public function gps_setting(Request $request)
    {
        if (!$request->ajax() || !$request->has('vehicle_id')) {
            return response()->json(["status" => false, "message" => "Sorry, something went wrong."]);
        }

        $vehicleId = $this->decodeId($request->input('vehicle_id'));
        $vehicle = Vehicle::with(['vehicleSetting', 'csSetting'])->find($vehicleId);

        if (!$vehicle) {
            return response()->json(["status" => false, "message" => "Vehicle not found."]);
        }

        $toArrayFormat = fn($val) => is_array($val) ? $val : (json_decode($val ?? '', true) ?? []);
        $exists = $vehicle->vehicleSetting;
        $csSettingObj = $vehicle->csSetting;
        $vehicleDependent = (!empty($exists) && !empty($exists->data));
        $csSettingData = $vehicleDependent ? $toArrayFormat($exists->data) : ($csSettingObj ? $csSettingObj->toArray() : []);

        $html = view('vehicles.gps_setting', [
            'vehicle' => $request->input('vehicle_id'),
            'vehicledepndend' => $vehicleDependent,
            'CsSetting' => $csSettingData
        ])->render();

        return response()->json([
            "status" => true,
            "message" => "",
            "html" => $html
        ]);
    }

    public function save_gpssetting(Request $request)
    {
        if (!$request->ajax() || !$request->has('CsSetting')) {
            return response()->json(["status" => false, "message" => "Sorry, something went wrong."]);
        }

        $inputData = $request->input('CsSetting');
        $vehicleId = $this->decodeId($inputData['vehicle_id'] ?? '');
        $exists = VehicleSetting::where('vehicle_id', $vehicleId)->first();

        if (empty($inputData['gps_provider'])) {
            if ($exists) {
                if ($exists->financing !== null) {
                    $exists->update(['data' => null]);
                } else {
                    $exists->delete();
                }
            }

            return response()->json(["status" => true, "message" => "Setting saved successfully"]);
        }

        VehicleSetting::updateOrCreate(
            ['vehicle_id' => $vehicleId],
            ['data' => $inputData]
        );

        return response()->json(["status" => true, "message" => "Setting saved successfully"]);
    }

    public function delete_gpssetting(Request $request)
    {
        if (!$request->ajax() || !$request->has('vehicle_id')) {
            return response()->json(["status" => false, "message" => "Sorry, something went wrong."]);
        }

        $vehicleId = $this->decodeId($request->input('vehicle_id'));
        $exists = VehicleSetting::where('vehicle_id', $vehicleId)->first();

        if ($exists) {
            if ($exists->financing !== null) {
                $exists->update(['data' => null]);
            } else {
                $exists->delete();
            }
        }

        return response()->json(["status" => true, "message" => "Setting deleted successfully"]);
    }

    public function getVehicleInspectionDoc(Request $request)
    {
        return $this->_getVehicleInspectionDoc($request);
    }

    public function duplicate(Request $request, $vehicleid = '')
    {
        $vehicleId = $this->decodeId($vehicleid);

        if (empty($vehicleId)) {
            return redirect()->back();
        }

        if ($request->isMethod('post')) {

            $sourceVehicle = Vehicle::with([
                'vehicleSetting',
                'depositRule',
                'images',
                'locations'
            ])->find($vehicleId);

            if (!$sourceVehicle) {
                return redirect()->back()->with('error', 'Sorry, source vehicle not found');
            }

            $rawVin = $request->input('Vehicle.vin_no', '');
            $cleanVin = preg_replace("/[^0-9A-Z]/", "", strtoupper($rawVin));

            if (empty($cleanVin) || strlen($cleanVin) !== 17) {
                return redirect()->back()->with('error', "Please enter valid VIN=" . strlen($cleanVin));
            }

            try {
                $newVehicle = $sourceVehicle->replicate();

                $newVehicle->booked = 0;
                $newVehicle->from_feed = 0;
                $newVehicle->trash = 0;
                $newVehicle->vin_no = $cleanVin;
                $userId = $request->input('Vehicle.user_id') ?: $sourceVehicle->user_id;
                $newVehicle->user_id = $userId;

                if (empty($newVehicle->make) || empty($newVehicle->model)) {
                    return redirect()->back()->with('error', 'Validation failed: Required basic fields missing.');
                }

                $newVehicle->save();
                $newVehicleId = $newVehicle->id;

                $vehicleName = (!empty($newVehicle->year) ? substr($newVehicle->year, -2) . '-' : '') .
                    (!empty($newVehicle->make) ? str_replace(' ', '_', $newVehicle->make) . '-' : '') .
                    (!empty($newVehicle->model) ? str_replace(' ', '_', $newVehicle->model) : '') .
                    (!empty($newVehicle->vin_no) ? '-' . substr($newVehicle->vin_no, -6) : '');

                $uniqueNo = ($newVehicleId < 999) ? '1' . sprintf('%04d', $newVehicleId) : $newVehicleId;

                $newVehicle->update([
                    'vehicle_name' => $vehicleName,
                    'vehicle_unique_id' => $uniqueNo
                ]);

                if ($sourceVehicle->images->isNotEmpty()) {
                    foreach ($sourceVehicle->images as $image) {
                        $newImage = $image->replicate();
                        $newImage->vehicle_id = $newVehicleId;
                        $newImage->save();
                    }
                }

                if ($sourceVehicle->vehicleSetting) {
                    $newSetting = $sourceVehicle->vehicleSetting->replicate();
                    $newSetting->vehicle_id = $newVehicleId;
                    $newSetting->save();
                }

                if ($sourceVehicle->depositRule) {
                    $newDepositRule = $sourceVehicle->depositRule->replicate();
                    $newDepositRule->vehicle_id = $newVehicleId;
                    $newDepositRule->user_id = $userId;
                    $newDepositRule->save();
                }

                if ($sourceVehicle->locations->isNotEmpty()) {
                    foreach ($sourceVehicle->locations as $location) {
                        $newLocation = $location->replicate();
                        $newLocation->vehicle_id = $newVehicleId;
                        $newLocation->save();
                    }
                }

                $this->_CopyVehicleImageFromRemote($newVehicleId);

                return redirect()->route('vehicles.index')->with('success', 'Vehicle duplicated successfully');

            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        $vehicleObj = Vehicle::find($vehicleId);

        if (empty($vehicleObj)) {
            return redirect()->back()->with('error', 'Sorry, vehicle is not found');
        }

        return view('admin.vehicles.duplicate', [
            'vehicleid' => $vehicleId,
            'dealerid' => $vehicleObj->user_id
        ]);

    }

}

