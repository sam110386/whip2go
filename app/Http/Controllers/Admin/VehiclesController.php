<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\User;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use App\Models\Legacy\VehicleLocation;
use App\Models\Legacy\VehicleSetting;
use App\Services\Legacy\Colors;
use App\Models\Legacy\DynamicFare;
use App\Services\Legacy\Free2MoveService;
use App\Http\Controllers\Traits\VehiclesTrait;
use App\Http\Controllers\Traits\VehicleLocationTrait;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VehiclesController extends LegacyAppController
{
    protected $imageSize = 2097152;
    protected $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];

    use VehiclesTrait, VehicleLocationTrait;
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
                    'images:id,filename,iorder,remote',
                    'locations:id,lat,lng,address'
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

    protected function vehicleAddFormBasePath(): string
    {
        return '/admin/vehicles/add';
    }

    protected function vehicleBasePath(): string
    {
        return '/admin/vehicles';
    }

    protected function vehicleAddReturnListUrl(bool $isSuperAdmin): string
    {
        return $isSuperAdmin ? '/admin/vehicles/index' : '/admin/linked_vehicles/index';
    }

    protected function vehicleAddLinkedListPath(): string
    {
        return '/admin/linked_vehicles/index';
    }

    protected function vehicleAddRedirectAfterCreate(int $vehicleId): RedirectResponse
    {
        return redirect($this->vehicleAddFormBasePath() . '/' . base64_encode((string) $vehicleId))
            ->with('success', 'Vehicle data saved successfully.');
    }

    public function ownerautocomplete(Request $request)
    {
        $term = trim((string) $request->query('term', ''));
        $userId = trim((string) $request->query('user_id', ''));

        if ($userId !== '' && is_numeric($userId)) {
            $u = User::query()->whereKey((int) $userId)->first(['id', 'first_name', 'contact_number']);
            $result = [];
            if ($u) {
                $result = ['id' => (int) $u->id, 'tag' => trim(($u->first_name ?? '') . ' - ' . ($u->contact_number ?? ''))];
            }
            return response()->json($result);
        }

        $q = User::query()->where('status', 1);
        if ($term !== '') {
            $like = '%' . $term . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('contact_number', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('last_name', 'like', $like);
            });
        }
        $users = $q->orderBy('first_name')->limit(10)->get(['id', 'first_name', 'contact_number']);

        return response()->json($users->map(fn($u) => [
            'id' => (int) $u->id,
            'tag' => trim(($u->first_name ?? '') . ' - ' . ($u->contact_number ?? '')),
        ])->values()->all());
    }

    public function loadVehicleStatus(Request $request)
    {
        $vehicleId = $this->decodeId((string) $request->input('vehicleid', ''));
        $vehicle = $vehicleId ? Vehicle::query()->find($vehicleId, ['id', 'status']) : null;
        return response()->json(['vehicle' => $vehicle]);
    }

    public function changeVehicleStatus(Request $request)
    {
        $payload = $request->input('Vehicle', []);
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        $status = isset($payload['status']) ? (int) $payload['status'] : null;
        if ($id <= 0 || $status === null) {
            return response()->json(['status' => false, 'message' => 'Invalid payload']);
        }

        if ($status === 11 || $status === 12) {
            Vehicle::query()->whereKey($id)->update(['trash' => $status === 11 ? 1 : 0]);
            return response()->json(['status' => true, 'message' => 'Vehicle has been updated successfully', 'vehicleid' => $id]);
        }

        Vehicle::query()->whereKey($id)->update(['status' => $status]);
        return response()->json(['status' => true, 'message' => 'Vehicle has been updated successfully', 'vehicleid' => $id]);
    }

    public function loadSingleRow(Request $request)
    {
        $vehicleId = (int) $request->input('vehicleid', 0);
        $vehicle = Vehicle::query()->with('owner')->whereKey($vehicleId)->first();
        return response()->json(['vehicle' => $vehicle]);
    }

    public function multiplAction(Request $request)
    {
        $statusAction = (string) $request->input('Vehicle.status', '');
        $selected = $request->input('select', []);
        if (!is_array($selected)) {
            $selected = [];
        }
        $ids = array_values(array_filter(array_map('intval', array_keys(array_filter($selected)))));
        if (!empty($ids)) {
            if ($statusAction === 'active') {
                Vehicle::query()->whereIn('id', $ids)->update(['status' => 1]);
            } elseif ($statusAction === 'inactive') {
                Vehicle::query()->whereIn('id', $ids)->update(['status' => 0]);
            }
        }
        return redirect()->to($request->headers->get('referer') ?: '/admin/vehicles/index');
    }

    public function saveImage(Request $request): JsonResponse
    {
        $vehicleId = (int) $request->input('id', 0);
        $file = $request->file('vehicleimage');
        if ($vehicleId <= 0 || !$file) {
            return response()->json(['success' => false, 'message' => 'Invalid upload payload']);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid image type']);
        }

        $name = 'veh_' . $vehicleId . '_' . time() . '.' . $ext;
        $targetDir = $this->vehiclePhotoDirectory();
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $file->move($targetDir, $name);

        $maxOrder = (int) (VehicleImage::query()->where('vehicle_id', $vehicleId)->max('iorder') ?? 0);
        $img = VehicleImage::query()->create([
            'vehicle_id' => $vehicleId,
            'filename' => $name,
            'iorder' => $maxOrder + 1,
            'remote' => 0,
        ]);

        return response()->json(['success' => true, 'key' => (int) $img->id, 'file' => $this->vehiclePhotoUrl($name)]);
    }

    public function deleteImage(Request $request): JsonResponse
    {
        $key = (int) $request->input('key', 0);
        $img = $key > 0 ? VehicleImage::query()->find($key) : null;
        if (!$img) {
            return response()->json(['success' => true, 'key' => '']);
        }

        $filename = (string) ($img->filename ?? '');
        if ($filename !== '') {
            $full = $this->vehiclePhotoDirectory() . DIRECTORY_SEPARATOR . $filename;
            if (is_file($full)) {
                @unlink($full);
            }
        }
        VehicleImage::query()->whereKey((int) $img->id)->delete();
        return response()->json(['success' => true, 'key' => '']);
    }

    public function reorderImage(Request $request): JsonResponse
    {
        $stack = $request->input('stack', []);
        if (!is_array($stack)) {
            $stack = [];
        }
        $i = 1;
        foreach ($stack as $item) {
            $key = isset($item['key']) ? (int) $item['key'] : 0;
            if ($key > 0) {
                VehicleImage::query()->whereKey($key)->update(['iorder' => $i++]);
            }
        }
        return response()->json(['success' => true]);
    }

    public function getVehicleRegistration(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string) $request->input('vehicleid', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Invalid Vehicle ID', 'result' => []]);
        }
        $vehicle = Vehicle::query()->find($vehicleId, ['registration_image']);
        $filename = (string) data_get($vehicle, 'registration_image', '');
        if ($filename === '') {
            return response()->json(['status' => false, 'message' => 'sorry, document not added yet by owner', 'result' => []]);
        }
        if (!is_file($this->vehiclePhotoDirectory() . DIRECTORY_SEPARATOR . $filename)) {
            return response()->json(['status' => false, 'message' => 'sorry, document not exists', 'result' => []]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'result' => ['file' => $this->vehiclePhotoUrl($filename)],
        ]);
    }

    public function getVehicleInspectionDoc(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string) $request->input('vehicleid', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Invalid Vehicle ID', 'result' => []]);
        }
        $vehicle = Vehicle::query()->find($vehicleId, ['inspection_image']);
        $filename = (string) data_get($vehicle, 'inspection_image', '');
        if ($filename === '') {
            return response()->json(['status' => false, 'message' => 'sorry, document not added yet by owner', 'result' => []]);
        }
        if (!is_file($this->vehiclePhotoDirectory() . DIRECTORY_SEPARATOR . $filename)) {
            return response()->json(['status' => false, 'message' => 'sorry, document not exists', 'result' => []]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'result' => ['file' => $this->vehiclePhotoUrl($filename)],
        ]);
    }

    public function rental_setting(Request $request, $id = null)
    {
        $vehicleId = $this->decodeId((string) $id);
        if (!$vehicleId) {
            return redirect($this->vehicleBasePath() . '/index');
        }

        if ($request->isMethod('POST')) {
            $deposit = $request->input('DepositRule', []);
            $vehiclePayload = $request->input('Vehicle', []);

            $depositAmtOpt = $this->normalizeAmountOptions($deposit['deposit_amt_opt'] ?? []);
            $initialFeeOpt = $this->normalizeAmountOptions($deposit['initial_fee_opt'] ?? []);

            $depositData = $deposit;
            $depositData['vehicle_id'] = $vehicleId;
            $depositData['deposit_amt_opt'] = empty($depositAmtOpt) ? '' : json_encode(array_values($depositAmtOpt));
            $depositData['initial_fee_opt'] = empty($initialFeeOpt) ? '' : json_encode(array_values($initialFeeOpt));
            $depositData['total_deposit_amt'] = (float) ($deposit['deposit_amt'] ?? 0) + array_sum(array_column($depositAmtOpt, 'amount'));
            $depositData['total_initial_fee'] = (float) ($deposit['initial_fee'] ?? 0) + array_sum(array_column($initialFeeOpt, 'amount'));
            if (($deposit['prepaid_initial_fee'] ?? null) && !empty(data_get($deposit, 'prepaid_initial_fee_data.amount')) && !empty(data_get($deposit, 'prepaid_initial_fee_data.day'))) {
                $depositData['prepaid_initial_fee'] = 1;
                $depositData['prepaid_initial_fee_data'] = json_encode($deposit['prepaid_initial_fee_data']);
            } else {
                $depositData['prepaid_initial_fee'] = 0;
                $depositData['prepaid_initial_fee_data'] = null;
            }
            if (($depositData['deposit_event'] ?? null) === 'N') {
                $depositData['deposit_amt'] = 0;
            }

            $existing = DepositRule::query()->where('vehicle_id', $vehicleId)->first();
            if ($existing) {
                DepositRule::query()->whereKey((int) $existing->id)->update($depositData);
            } else {
                DepositRule::query()->create($depositData);
            }

            $vehicleData = [
                'id' => $vehicleId,
                'day_rent' => (float) preg_replace('/[^0-9.]/', '', (string) ($vehiclePayload['day_rent'] ?? '0')),
                'rate' => (float) preg_replace('/[^0-9.]/', '', (string) ($vehiclePayload['rate'] ?? '0')),
                'fare_type' => (string) ($vehiclePayload['fare_type'] ?? ''),
                'auth_require' => $vehiclePayload['auth_require'] ?? null,
                'rent_opt' => !empty($vehiclePayload['rent_opt']) ? json_encode(array_filter(array_map('array_filter', (array) $vehiclePayload['rent_opt']))) : '[]',
            ];
            Vehicle::query()->whereKey($vehicleId)->update($vehicleData);

            if ($request->has('Vehicle.updatebooking') && $vehicleData['day_rent'] > 0) {
                $active = CsOrder::query()->where('vehicle_id', $vehicleId)->where('status', 1)->first(['id', 'parent_id']);
                if ($active) {
                    $bookingId = !empty($active->parent_id) ? (int) $active->parent_id : (int) $active->id;
                    OrderDepositRule::query()->where('cs_order_id', $bookingId)->update(['rental' => $vehicleData['day_rent']]);
                }
            }

            return redirect()->to($request->headers->get('referer') ?: $this->vehicleBasePath() . '/rental_setting/' . base64_encode((string) $vehicleId));
        }

        $vehicle = Vehicle::query()->find($vehicleId, ['id', 'vehicle_unique_id', 'rent_opt', 'rate', 'day_rent', 'auth_require', 'fare_type', 'user_id']);
        $depositRule = DepositRule::query()->where('vehicle_id', $vehicleId)->first();
        if ($vehicle && !empty($vehicle->rent_opt)) {
            $vehicle->rent_opt = json_decode((string) $vehicle->rent_opt, true) ?: [];
        }
        if ($depositRule) {
            $depositRule->deposit_amt_opt = !empty($depositRule->deposit_amt_opt) ? (json_decode((string) $depositRule->deposit_amt_opt, true) ?: []) : [];
            $depositRule->initial_fee_opt = !empty($depositRule->initial_fee_opt) ? (json_decode((string) $depositRule->initial_fee_opt, true) ?: []) : [];
            $depositRule->prepaid_initial_fee_data = !empty($depositRule->prepaid_initial_fee_data) ? (json_decode((string) $depositRule->prepaid_initial_fee_data, true) ?: ['day' => '', 'amount' => '']) : ['day' => '', 'amount' => ''];
        }

        return view('admin.vehicles.rental_setting', [
            'id' => $vehicleId,
            'vehicle' => $vehicle,
            'depositRule' => $depositRule,
            'listTitle' => 'Update Rental Fee Setting',
            'vehicleBasePath' => $this->vehicleBasePath(),
            'returnListUrl' => $this->vehicleAddReturnListUrl(!empty($this->getAdminUserid()['administrator'])),
        ]);
    }

    public function duplicate(Request $request, $vehicleid = '')
    {
        $sourceId = $this->decodeId((string) $vehicleid);
        if (!$sourceId) {
            return redirect($this->vehicleBasePath() . '/index');
        }

        $sourceVehicle = Vehicle::query()->find($sourceId);
        if (!$sourceVehicle) {
            return redirect($this->vehicleBasePath() . '/index');
        }

        if (!$request->isMethod('POST')) {
            return view('admin.vehicles.duplicate', [
                'vehicleid' => $sourceId,
                'dealerid' => $sourceVehicle->user_id,
                'vehicleBasePath' => $this->vehicleBasePath(),
                'returnListUrl' => $this->vehicleAddReturnListUrl(!empty($this->getAdminUserid()['administrator'])),
            ]);
        }

        $vinNo = preg_replace('/[^0-9A-Z]/', '', strtoupper((string) $request->input('Vehicle.vin_no', '')));
        $newUserId = (int) $request->input('Vehicle.user_id', $sourceVehicle->user_id);
        if (strlen($vinNo) !== 17) {
            return back()->withInput()->with('error', 'Please enter valid VIN');
        }

        $newVehicle = $sourceVehicle->toArray();
        unset($newVehicle['id']);
        $newVehicle['booked'] = 0;
        $newVehicle['from_feed'] = 0;
        $newVehicle['trash'] = 0;
        $newVehicle['vin_no'] = $vinNo;
        $newVehicle['user_id'] = $newUserId;
        $newVehicle['vehicle_name'] = $this->buildVehicleName($newVehicle);

        $created = Vehicle::query()->create($newVehicle);
        $uniqueNo = ((int) $created->id < 999) ? ('1' . sprintf('%04d', (int) $created->id)) : (string) $created->id;
        Vehicle::query()->whereKey((int) $created->id)->update(['vehicle_unique_id' => $uniqueNo]);

        $sourceImages = VehicleImage::query()->where('vehicle_id', $sourceId)->get();
        foreach ($sourceImages as $img) {
            $copy = $img->toArray();
            unset($copy['id']);
            $copy['vehicle_id'] = (int) $created->id;
            VehicleImage::query()->create($copy);
        }
        $sourceLocations = VehicleLocation::query()->where('vehicle_id', $sourceId)->get();
        foreach ($sourceLocations as $loc) {
            $copy = $loc->toArray();
            unset($copy['id']);
            $copy['vehicle_id'] = (int) $created->id;
            VehicleLocation::query()->create($copy);
        }
        $sourceSetting = VehicleSetting::query()->where('vehicle_id', $sourceId)->first();
        if ($sourceSetting) {
            $copy = $sourceSetting->toArray();
            unset($copy['id']);
            $copy['vehicle_id'] = (int) $created->id;
            VehicleSetting::query()->create($copy);
        }
        $sourceRule = DepositRule::query()->where('vehicle_id', $sourceId)->first();
        if ($sourceRule) {
            $copy = $sourceRule->toArray();
            unset($copy['id']);
            $copy['vehicle_id'] = (int) $created->id;
            $copy['user_id'] = $newUserId;
            DepositRule::query()->create($copy);
        }

        return redirect($this->vehicleAddReturnListUrl(!empty($this->getAdminUserid()['administrator'])));
    }

    public function checkVinDetails(Request $request): JsonResponse
    {
        $vin = strtoupper(trim((string) $request->input('vin', '')));
        if ($vin === '') {
            return response()->json(['status' => 'error', 'message' => 'Invalid Json', 'result' => []]);
        }
        $result = [
            'vin' => $vin,
            'length' => strlen($vin),
            'valid_length' => strlen($vin) === 17,
        ];
        return response()->json(['status' => 'success', 'message' => 'record found', 'result' => $result]);
    }

    public function lastlocation(Request $request, $vehicle_id = null)
    {
        $vehicleId = $this->decodeId((string) $vehicle_id);
        $vehicle = $vehicleId ? Vehicle::query()->find($vehicleId) : null;
        return view('admin.vehicles.lastlocation', [
            'vehicle' => $vehicle,
            'vehicleLocation' => ['status' => false, 'message' => 'Passtime provider migration pending'],
            'returnListUrl' => $this->vehicleAddReturnListUrl(!empty($this->getAdminUserid()['administrator'])),
        ]);
    }

    public function getVehicleDynamicFare(Request $request): JsonResponse
    {
        $vehicleId = (int) $request->input('vehicleid', 0);
        $tag = (string) $request->input('tag', 'D');
        $vehicle = $vehicleId > 0 ? Vehicle::query()->find($vehicleId) : null;
        if (!$vehicle) {
            return response()->json(['status' => 'error', 'msg' => 'Sorry, something went wrong. Please try again']);
        }
        $estimate = $tag === 'D' ? (float) $vehicle->rate : (float) $vehicle->day_rent;
        return response()->json(['status' => 'success', 'data' => ['estimated_fare' => $estimate], 'msg' => '']);
    }

    public function getvehicledetails(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string) $request->input('vehicleid', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Invalid Vehicle ID']);
        }
        $vehicle = Vehicle::query()->find($vehicleId, [
            'id',
            'plate_number',
            'inspection_image',
            'registration_image',
            'gps_serialno',
            'passtime_serialno',
            'registered_state',
            'reg_name_date',
            'reg_name_exp_date',
            'wireless_gps_serial',
        ]);
        return response()->json(['status' => true, 'vehicle' => $vehicle, 'orderid' => $this->decodeId((string) $request->input('orderid', ''))]);
    }

    public function updateVehicleDetails(Request $request): JsonResponse
    {
        if ($request->ajax() && $request->filled('pk')) {
            $pk = (int) $request->input('pk');
            $name = (string) $request->input('name');
            $value = $request->input('value');
            if ($pk > 0 && $name !== '') {
                Vehicle::query()->whereKey($pk)->update([$name => $value]);
                return response()->json(['status' => true, 'message' => '']);
            }
        }

        $vehicleId = (int) $request->input('Vehicle.id', 0);
        if ($vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $dataToSave = ['id' => $vehicleId];
        foreach (['registration_image', 'insurance_image', 'inspection_image'] as $field) {
            $file = $request->file('Vehicle.' . $field);
            if (!$file) {
                continue;
            }
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
                continue;
            }
            $filename = 'vehi_' . $vehicleId . '_' . str_replace('_image', '', $field) . '.' . $ext;
            $targetDir = $this->vehiclePhotoDirectory();
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $filename);
            $dataToSave[$field] = $filename;
        }
        Vehicle::query()->whereKey($vehicleId)->update($dataToSave);
        return response()->json(['status' => true, 'message' => '']);
    }

    public function getVehicleGps(Request $request): JsonResponse
    {
        $vehicleId = (int) $request->input('vehicleid', 0);
        $type = (string) $request->input('type', 'gps_provider');
        $vehicle = $vehicleId > 0 ? Vehicle::query()->find($vehicleId, ['gps_serialno', 'passtime_serialno']) : null;
        if (!$vehicle) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $serial = $type === 'passtime' ? (string) ($vehicle->passtime_serialno ?? '') : (string) ($vehicle->gps_serialno ?? '');
        return response()->json(['status' => true, 'message' => '', 'gps_serialno' => $serial]);
    }

    public function gps_setting(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string) $request->input('vehicle_id', ''));
        if (!$request->ajax() || !$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $exists = VehicleSetting::query()->where('vehicle_id', $vehicleId)->first();
        $settingData = [];
        if ($exists && !empty($exists->data)) {
            $settingData = json_decode((string) $exists->data, true) ?: [];
        }
        $html = view('admin.vehicles.gps_setting', [
            'vehicle' => base64_encode((string) $vehicleId),
            'vehicledepndend' => $exists && !empty($exists->data),
            'csSetting' => $settingData,
        ])->render();
        return response()->json(['status' => true, 'message' => '', 'html' => $html]);
    }

    public function save_gpssetting(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $payload = $request->input('CsSetting', []);
        $vehicleId = $this->decodeId((string) data_get($payload, 'vehicle_id', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $exists = VehicleSetting::query()->where('vehicle_id', $vehicleId)->first();
        $gpsProvider = (string) data_get($payload, 'gps_provider', '');
        $passtime = (string) data_get($payload, 'passtime', '');
        if ($gpsProvider === '' && $passtime === '') {
            if ($exists) {
                if (!empty($exists->financing)) {
                    VehicleSetting::query()->whereKey((int) $exists->id)->update(['data' => null]);
                } else {
                    VehicleSetting::query()->whereKey((int) $exists->id)->delete();
                }
            }
            return response()->json(['status' => true, 'message' => 'Setting saved successfully']);
        }
        $data = json_encode($payload);
        if ($exists) {
            VehicleSetting::query()->whereKey((int) $exists->id)->update(['data' => $data]);
        } else {
            VehicleSetting::query()->create(['vehicle_id' => $vehicleId, 'data' => $data]);
        }
        return response()->json(['status' => true, 'message' => 'Setting saved successfully']);
    }

    public function delete_gpssetting(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $vehicleId = $this->decodeId((string) $request->input('vehicle_id', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $exists = VehicleSetting::query()->where('vehicle_id', $vehicleId)->first();
        if ($exists) {
            if (!empty($exists->financing)) {
                VehicleSetting::query()->whereKey((int) $exists->id)->update(['data' => null]);
            } else {
                VehicleSetting::query()->whereKey((int) $exists->id)->delete();
            }
        }
        return response()->json(['status' => true, 'message' => 'Setting deleted successfully']);
    }

    public function changePasstimeVehicleStatus(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string) $request->input('vehicleid', ''));
        $status = trim((string) $request->input('status', ''));
        if (!$vehicleId || !in_array($status, ['active', 'inactive'], true)) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'vehicleid' => $vehicleId]);
        }
        // External starter activation/deactivation migration is pending;
        // preserve DB status toggle endpoint contract for admin UI.
        Vehicle::query()->whereKey($vehicleId)->update(['passtime_status' => $status === 'active' ? 1 : 0]);
        return response()->json(['status' => true, 'message' => 'Updated', 'vehicleid' => $vehicleId]);
    }

    /**
     * Cake VehicleLocationTrait::saveVehicleLocation (replace-all for this form).
     *
     * @return array<string, string>|string
     */
    /**
     * Drop keys that are not real columns on `vehicles` (older DBs may lack newer fields).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function filterKeysForVehiclesTable(array $data): array
    {
        static $allowed = null;
        if ($allowed === null) {
            $allowed = array_flip(Schema::getColumnListing((new Vehicle())->getTable()));
        }

        return array_intersect_key($data, $allowed);
    }

    private function mergeVehicleDocumentUploads(Request $request, int $vehicleId)
    {
        if ($vehicleId <= 0) {
            return [];
        }
        $out = [];
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        $max = $this->uploadMaxBytes();
        $map = [
            'registration_image' => 'registration',
            'insurance_image' => 'insurance',
            'inspection_image' => 'inspection',
        ];
        foreach ($map as $inputName => $suffix) {
            $file = $request->file($inputName);
            if ($file === null || !$file->isValid()) {
                continue;
            }
            if ($file->getSize() > $max) {
                return 'Upload too large for ' . $inputName . ' (max ' . ini_get('upload_max_filesize') . ').';
            }
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (!in_array($ext, $allowed, true)) {
                return 'Invalid file type for ' . $inputName;
            }
            $filename = 'vehi_' . $vehicleId . '_' . $suffix . '.' . $ext;
            $dir = $this->vehiclePhotoDirectory();
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file->move($dir, $filename);
            $out[$inputName] = $filename;
        }

        return $out;
    }

    private function replaceVehicleLocationsFromRequest(Request $request, int $vehicleId): void
    {
        if ($vehicleId <= 0) {
            return;
        }
        $rows = $request->input('VehicleLocation', []);
        if (!is_array($rows)) {
            return;
        }
        VehicleLocation::query()->where('vehicle_id', $vehicleId)->delete();
        $locTable = (new VehicleLocation())->getTable();
        $hasGeoCol = Schema::hasColumn($locTable, 'geo');
        $geoType = $hasGeoCol ? Schema::getColumnType($locTable, 'geo') : null;
        foreach ($rows as $loc) {
            if (!is_array($loc)) {
                continue;
            }
            $lat = isset($loc['lat']) ? trim((string) $loc['lat']) : '';
            $lng = isset($loc['lng']) ? trim((string) $loc['lng']) : '';
            if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }
            $latf = (float) $lat;
            $lngf = (float) $lng;
            $insert = [
                'vehicle_id' => $vehicleId,
                'lat' => $latf,
                'lng' => $lngf,
                'address' => isset($loc['address']) ? (string) $loc['address'] : '',
            ];
            if ($hasGeoCol) {
                if (in_array($geoType, ['integer', 'bigint', 'smallint', 'tinyint'], true)) {
                    $insert['geo'] = 0;
                } else {
                    $insert['geo'] = DB::raw('POINT(' . $lngf . ',' . $latf . ')');
                }
            }
            VehicleLocation::query()->create($insert);
        }
    }

    private function uploadMaxBytes(): int
    {
        return min($this->iniToBytes((string) ini_get('upload_max_filesize')), $this->iniToBytes((string) ini_get('post_max_size')));
    }

    private function iniToBytes(string $val): int
    {
        $val = trim($val);
        if ($val === '') {
            return 0;
        }
        $n = (int) $val;
        $u = strtolower(substr($val, -1));
        if ($u === 'g') {
            return $n * 1024 * 1024 * 1024;
        }
        if ($u === 'm') {
            return $n * 1024 * 1024;
        }
        if ($u === 'k') {
            return $n * 1024;
        }

        return $n;
    }

    /** @return array<string, string> */
    private function simpleVehicleColorOptions(): array
    {
        $c = ['Black', 'White', 'Silver', 'Gray', 'Red', 'Blue', 'Brown', 'Green', 'Beige', 'Gold', 'Orange', 'Yellow', 'Purple'];

        return array_combine($c, $c);
    }

    private function buildVehicleName(array $data): string
    {
        $year = isset($data['year']) && $data['year'] !== '' ? substr((string) $data['year'], -2) . '-' : '';
        $make = isset($data['make']) && $data['make'] !== '' ? str_replace(' ', '_', (string) $data['make']) . '-' : '';
        $model = isset($data['model']) && $data['model'] !== '' ? str_replace(' ', '_', (string) $data['model']) : '';
        $vinTail = isset($data['vin_no']) && $data['vin_no'] !== '' ? '-' . substr((string) $data['vin_no'], -6) : '';
        return $year . $make . $model . $vinTail;
    }

    private function normalizeAmountOptions($input): array
    {
        if (!is_array($input)) {
            return [];
        }
        $rows = [];
        foreach ($input as $row) {
            $day = (int) data_get($row, 'after_day', 0);
            $amount = (float) data_get($row, 'amount', 0);
            if ($day <= 0 && $amount <= 0) {
                continue;
            }
            $rows[] = ['after_day' => $day, 'amount' => $amount];
        }
        return $rows;
    }

    private function vehiclePhotoDirectory(): string
    {
        return base_path('app/webroot/img/custom/vehicle_photo');
    }

    private function vehiclePhotoUrl(string $filename): string
    {
        return '/img/custom/vehicle_photo/' . ltrim($filename, '/');
    }
}

