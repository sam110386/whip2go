<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsOrder as LegacyCsOrder;
use App\Models\Legacy\DepositRule as LegacyDepositRule;
use App\Models\Legacy\OrderDepositRule as LegacyOrderDepositRule;
use App\Models\Legacy\User as LegacyUser;
use App\Models\Legacy\Vehicle as LegacyVehicle;
use App\Models\Legacy\VehicleImage as LegacyVehicleImage;
use App\Models\Legacy\VehicleLocation as LegacyVehicleLocation;
use App\Models\Legacy\VehicleSetting as LegacyVehicleSetting;
use App\Support\VehicleAdminSave;
use App\Support\VehicleListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehiclesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        if (empty($admin['administrator'])) {
            return redirect('/admin/linked_vehicles/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        if ($request->input('export') === 'Export') {
            return $this->streamAdminVehiclesCsv($request);
        }

        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['vehicles_limit' => $lim]);
            }
        }
        $limit = (int)session('vehicles_limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }

        $q = LegacyVehicle::query()->with('owner')->orderByDesc('id');
        VehicleListing::applyAdminFilters($q, $request);
        $vehicleDetails = $q->paginate($limit)->withQueryString();

        $keyword = trim((string)$request->input('Search.keyword', $request->query('keyword', '')));
        $searchin = trim((string)$request->input('Search.searchin', $request->query('searchin', '')));
        $show = (string)$request->input('Search.show', $request->query('showtype', ''));
        $userId = trim((string)$request->input('Search.user_id', $request->query('user_id', '')));
        $type = trim((string)$request->input('Search.type', $request->query('type', '')));
        $visibility = trim((string)$request->input('Search.visibility', $request->query('visibility', '')));

        return view('admin.vehicles.index', [
            'vehicleDetails' => $vehicleDetails,
            'keyword' => $keyword,
            'searchin' => $searchin,
            'show' => $show,
            'userId' => $userId,
            'type' => $type,
            'visibility' => $visibility,
            'limit' => $limit,
            'showArr' => VehicleListing::adminStatusLabels(),
        ]);
    }

    private function streamAdminVehiclesCsv(Request $request): StreamedResponse
    {
        $q = LegacyVehicle::query()->orderByDesc('id');
        VehicleListing::applyAdminFilters($q, $request);
        $rows = $q->limit(5000)->get([
            'id', 'user_id', 'vehicle_name', 'vehicle_unique_id', 'vin_no', 'plate_number',
            'status', 'booked', 'waitlist', 'passtime_status',
        ]);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="vehicles.csv"',
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'user_id', 'vehicle_name', 'vehicle_unique_id', 'vin_no', 'plate_number', 'status', 'booked', 'waitlist', 'passtime_status']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->user_id,
                    $r->vehicle_name,
                    $r->vehicle_unique_id,
                    $r->vin_no,
                    $r->plate_number,
                    $r->status,
                    $r->booked,
                    $r->waitlist,
                    $r->passtime_status,
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function admin_add(Request $request, $vehicle_id = null)
    {
        if ($redirect = $this->ensureVehicleAddSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        $isSuperAdmin = !empty($admin['administrator']);
        $linkedDealerId = !$isSuperAdmin ? (int)($admin['parent_id'] ?? 0) : 0;
        if (!$isSuperAdmin && $linkedDealerId <= 0) {
            return redirect($this->vehicleAddLinkedListPath())->with('error', 'Invalid dealer account.');
        }

        $decodedVehicleId = $this->decodeId($vehicle_id);
        $vehicle = $decodedVehicleId ? LegacyVehicle::query()->find($decodedVehicleId) : null;

        if (!$isSuperAdmin && $vehicle !== null && (int)$vehicle->user_id !== $linkedDealerId) {
            return redirect($this->vehicleAddLinkedListPath())->with('error', 'You cannot edit this vehicle.');
        }

        $returnListUrl = $this->vehicleAddReturnListUrl($isSuperAdmin);

        if (!$request->isMethod('POST')) {
            $locations = collect();
            $vehicleImages = collect();
            $owner = null;
            if ($vehicle !== null) {
                $locations = LegacyVehicleLocation::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->orderBy('id')
                    ->get();
                $vehicleImages = LegacyVehicleImage::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->orderBy('iorder')
                    ->get();
                $owner = LegacyUser::query()->find($vehicle->user_id, ['id', 'distance_unit']);
            } elseif (!$isSuperAdmin && $linkedDealerId > 0) {
                $owner = LegacyUser::query()->find($linkedDealerId, ['id', 'distance_unit']);
            }
            if ($locations->isEmpty()) {
                $locations = collect([(object)['id' => null, 'address' => '', 'lat' => '', 'lng' => '']]);
            }

            return view('admin.vehicles.add', [
                'listTitle' => $vehicle ? 'Edit Vehicle' : 'Add Vehicle',
                'vehicle' => $vehicle,
                'locations' => $locations,
                'vehicleImages' => $vehicleImages,
                'owner' => $owner,
                'availabilityOptions' => VehicleAdminSave::availabilityOptions(),
                'financingOptions' => VehicleAdminSave::financingOptions(),
                'colorOptions' => $this->simpleVehicleColorOptions(),
                'lockedDealerId' => $isSuperAdmin ? null : $linkedDealerId,
                'returnListUrl' => $returnListUrl,
                'vehicleFormActionBase' => $this->vehicleAddFormBasePath(),
            ]);
        }

        $payload = $request->input('Vehicle', []);
        if (!is_array($payload)) {
            $payload = [];
        }
        if (!$isSuperAdmin) {
            $payload['user_id'] = $linkedDealerId;
        }

        $data = VehicleAdminSave::buildRow($payload, $vehicle);
        $data = $this->filterKeysForVehiclesTable($data);
        if ($data['user_id'] <= 0) {
            return redirect()->back()->withInput()->with('error', 'Dealer / owner is required.');
        }
        if ($data['vin_no'] === '') {
            return redirect()->back()->withInput()->with('error', 'VIN is required.');
        }

        if ($vehicle !== null) {
            LegacyVehicle::query()->whereKey((int)$vehicle->id)->update($data);
            $vehicleId = (int)$vehicle->id;
        } else {
            $created = LegacyVehicle::query()->create($data);
            $vehicleId = (int)$created->id;
            if ($vehicleId > 0 && empty($created->vehicle_unique_id)) {
                $uniqueNo = ($vehicleId < 999) ? ('1' . sprintf('%04d', $vehicleId)) : (string)$vehicleId;
                LegacyVehicle::query()->whereKey($vehicleId)->update(['vehicle_unique_id' => $uniqueNo]);
            }
        }

        $docUpdates = $this->mergeVehicleDocumentUploads($request, $vehicleId);
        if (is_string($docUpdates)) {
            return redirect()->back()->withInput()->with('error', $docUpdates);
        }
        if ($docUpdates !== []) {
            $docUpdates = $this->filterKeysForVehiclesTable($docUpdates);
            if ($docUpdates !== []) {
                LegacyVehicle::query()->whereKey($vehicleId)->update($docUpdates);
            }
        }

        $this->replaceVehicleLocationsFromRequest($request, $vehicleId);

        if ($vehicle !== null) {
            return redirect($returnListUrl)->with('success', 'Vehicle data updated successfully.');
        }

        return $this->vehicleAddRedirectAfterCreate($vehicleId);
    }

    /**
     * Session guard for vehicle add/edit (cloud controller overrides for cloud slug).
     */
    protected function ensureVehicleAddSession(): ?RedirectResponse
    {
        return $this->ensureAdminSession();
    }

    protected function vehicleAddFormBasePath(): string
    {
        return '/admin/vehicles/add';
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
        return redirect($this->vehicleAddFormBasePath() . '/' . base64_encode((string)$vehicleId))
            ->with('success', 'Vehicle data saved successfully.');
    }

    public function admin_ownerautocomplete(Request $request)
    {
        $term = trim((string)$request->query('term', ''));
        $userId = trim((string)$request->query('user_id', ''));

        if ($userId !== '' && is_numeric($userId)) {
            $u = LegacyUser::query()->whereKey((int)$userId)->first(['id', 'first_name', 'contact_number']);
            $result = [];
            if ($u) {
                $result = ['id' => (int)$u->id, 'tag' => trim(($u->first_name ?? '') . ' - ' . ($u->contact_number ?? ''))];
            }
            return response()->json($result);
        }

        $q = LegacyUser::query()->where('status', 1);
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

        return response()->json($users->map(fn ($u) => [
            'id' => (int)$u->id,
            'tag' => trim(($u->first_name ?? '') . ' - ' . ($u->contact_number ?? '')),
        ])->values()->all());
    }

    public function admin_loadVehicleStatus(Request $request)
    {
        $vehicleId = $this->decodeId((string)$request->input('vehicleid', ''));
        $vehicle = $vehicleId ? LegacyVehicle::query()->find($vehicleId, ['id', 'status']) : null;
        return response()->json(['vehicle' => $vehicle]);
    }

    public function admin_changeVehicleStatus(Request $request)
    {
        $payload = $request->input('Vehicle', []);
        $id = isset($payload['id']) ? (int)$payload['id'] : 0;
        $status = isset($payload['status']) ? (int)$payload['status'] : null;
        if ($id <= 0 || $status === null) {
            return response()->json(['status' => false, 'message' => 'Invalid payload']);
        }

        if ($status === 11 || $status === 12) {
            LegacyVehicle::query()->whereKey($id)->update(['trash' => $status === 11 ? 1 : 0]);
            return response()->json(['status' => true, 'message' => 'Vehicle has been updated successfully', 'vehicleid' => $id]);
        }

        LegacyVehicle::query()->whereKey($id)->update(['status' => $status]);
        return response()->json(['status' => true, 'message' => 'Vehicle has been updated successfully', 'vehicleid' => $id]);
    }

    public function admin_loadSingleRow(Request $request)
    {
        $vehicleId = (int)$request->input('vehicleid', 0);
        $vehicle = LegacyVehicle::query()->with('owner')->whereKey($vehicleId)->first();
        return response()->json(['vehicle' => $vehicle]);
    }

    public function admin_multiplAction(Request $request)
    {
        $statusAction = (string)$request->input('Vehicle.status', '');
        $selected = $request->input('select', []);
        if (!is_array($selected)) {
            $selected = [];
        }
        $ids = array_values(array_filter(array_map('intval', array_keys(array_filter($selected)))));
        if (!empty($ids)) {
            if ($statusAction === 'active') {
                LegacyVehicle::query()->whereIn('id', $ids)->update(['status' => 1]);
            } elseif ($statusAction === 'inactive') {
                LegacyVehicle::query()->whereIn('id', $ids)->update(['status' => 0]);
            }
        }
        return redirect()->to($request->headers->get('referer') ?: '/admin/vehicles/index');
    }

    public function admin_saveImage(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('id', 0);
        $file = $request->file('vehicleimage');
        if ($vehicleId <= 0 || !$file) {
            return response()->json(['success' => false, 'message' => 'Invalid upload payload']);
        }

        $ext = strtolower((string)$file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid image type']);
        }

        $name = 'veh_' . $vehicleId . '_' . time() . '.' . $ext;
        $targetDir = $this->vehiclePhotoDirectory();
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $file->move($targetDir, $name);

        $maxOrder = (int)(LegacyVehicleImage::query()->where('vehicle_id', $vehicleId)->max('iorder') ?? 0);
        $img = LegacyVehicleImage::query()->create([
            'vehicle_id' => $vehicleId,
            'filename' => $name,
            'iorder' => $maxOrder + 1,
            'remote' => 0,
        ]);

        return response()->json(['success' => true, 'key' => (int)$img->id, 'file' => $this->vehiclePhotoUrl($name)]);
    }

    public function admin_deleteImage(Request $request): JsonResponse
    {
        $key = (int)$request->input('key', 0);
        $img = $key > 0 ? LegacyVehicleImage::query()->find($key) : null;
        if (!$img) {
            return response()->json(['success' => true, 'key' => '']);
        }

        $filename = (string)($img->filename ?? '');
        if ($filename !== '') {
            $full = $this->vehiclePhotoDirectory() . DIRECTORY_SEPARATOR . $filename;
            if (is_file($full)) {
                @unlink($full);
            }
        }
        LegacyVehicleImage::query()->whereKey((int)$img->id)->delete();
        return response()->json(['success' => true, 'key' => '']);
    }

    public function admin_reorderImage(Request $request): JsonResponse
    {
        $stack = $request->input('stack', []);
        if (!is_array($stack)) {
            $stack = [];
        }
        $i = 1;
        foreach ($stack as $item) {
            $key = isset($item['key']) ? (int)$item['key'] : 0;
            if ($key > 0) {
                LegacyVehicleImage::query()->whereKey($key)->update(['iorder' => $i++]);
            }
        }
        return response()->json(['success' => true]);
    }

    public function admin_getVehicleRegistration(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string)$request->input('vehicleid', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Invalid Vehicle ID', 'result' => []]);
        }
        $vehicle = LegacyVehicle::query()->find($vehicleId, ['registration_image']);
        $filename = (string)data_get($vehicle, 'registration_image', '');
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

    public function admin_getVehicleInspectionDoc(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string)$request->input('vehicleid', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Invalid Vehicle ID', 'result' => []]);
        }
        $vehicle = LegacyVehicle::query()->find($vehicleId, ['inspection_image']);
        $filename = (string)data_get($vehicle, 'inspection_image', '');
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

    public function admin_rental_setting(Request $request, $id = null)
    {
        $vehicleId = $this->decodeId((string)$id);
        if (!$vehicleId) {
            return redirect('/admin/vehicles/index');
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
            $depositData['total_deposit_amt'] = (float)($deposit['deposit_amt'] ?? 0) + array_sum(array_column($depositAmtOpt, 'amount'));
            $depositData['total_initial_fee'] = (float)($deposit['initial_fee'] ?? 0) + array_sum(array_column($initialFeeOpt, 'amount'));
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

            $existing = LegacyDepositRule::query()->where('vehicle_id', $vehicleId)->first();
            if ($existing) {
                LegacyDepositRule::query()->whereKey((int)$existing->id)->update($depositData);
            } else {
                LegacyDepositRule::query()->create($depositData);
            }

            $vehicleData = [
                'id' => $vehicleId,
                'day_rent' => (float)preg_replace('/[^0-9.]/', '', (string)($vehiclePayload['day_rent'] ?? '0')),
                'rate' => (float)preg_replace('/[^0-9.]/', '', (string)($vehiclePayload['rate'] ?? '0')),
                'fare_type' => (string)($vehiclePayload['fare_type'] ?? ''),
                'auth_require' => $vehiclePayload['auth_require'] ?? null,
                'rent_opt' => !empty($vehiclePayload['rent_opt']) ? json_encode(array_filter(array_map('array_filter', (array)$vehiclePayload['rent_opt']))) : '[]',
            ];
            LegacyVehicle::query()->whereKey($vehicleId)->update($vehicleData);

            if ($request->has('Vehicle.updatebooking') && $vehicleData['day_rent'] > 0) {
                $active = LegacyCsOrder::query()->where('vehicle_id', $vehicleId)->where('status', 1)->first(['id', 'parent_id']);
                if ($active) {
                    $bookingId = !empty($active->parent_id) ? (int)$active->parent_id : (int)$active->id;
                    LegacyOrderDepositRule::query()->where('cs_order_id', $bookingId)->update(['rental' => $vehicleData['day_rent']]);
                }
            }

            return redirect()->to($request->headers->get('referer') ?: '/admin/vehicles/rental_setting/' . base64_encode((string)$vehicleId));
        }

        $vehicle = LegacyVehicle::query()->find($vehicleId, ['id', 'vehicle_unique_id', 'rent_opt', 'rate', 'day_rent', 'auth_require', 'fare_type', 'user_id']);
        $depositRule = LegacyDepositRule::query()->where('vehicle_id', $vehicleId)->first();
        if ($vehicle && !empty($vehicle->rent_opt)) {
            $vehicle->rent_opt = json_decode((string)$vehicle->rent_opt, true) ?: [];
        }
        if ($depositRule) {
            $depositRule->deposit_amt_opt = !empty($depositRule->deposit_amt_opt) ? (json_decode((string)$depositRule->deposit_amt_opt, true) ?: []) : [];
            $depositRule->initial_fee_opt = !empty($depositRule->initial_fee_opt) ? (json_decode((string)$depositRule->initial_fee_opt, true) ?: []) : [];
            $depositRule->prepaid_initial_fee_data = !empty($depositRule->prepaid_initial_fee_data) ? (json_decode((string)$depositRule->prepaid_initial_fee_data, true) ?: ['day' => '', 'amount' => '']) : ['day' => '', 'amount' => ''];
        }

        return view('admin.vehicles.rental_setting', [
            'id' => $vehicleId,
            'vehicle' => $vehicle,
            'depositRule' => $depositRule,
            'listTitle' => 'Update Rental Fee Setting',
        ]);
    }

    public function admin_duplicate(Request $request, $vehicleid = '')
    {
        $sourceId = $this->decodeId((string)$vehicleid);
        if (!$sourceId) {
            return redirect('/admin/vehicles/index');
        }

        $sourceVehicle = LegacyVehicle::query()->find($sourceId);
        if (!$sourceVehicle) {
            return redirect('/admin/vehicles/index');
        }

        if (!$request->isMethod('POST')) {
            return view('admin.vehicles.duplicate', [
                'vehicleid' => $sourceId,
                'dealerid' => $sourceVehicle->user_id,
            ]);
        }

        $vinNo = preg_replace('/[^0-9A-Z]/', '', strtoupper((string)$request->input('Vehicle.vin_no', '')));
        $newUserId = (int)$request->input('Vehicle.user_id', $sourceVehicle->user_id);
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

        $created = LegacyVehicle::query()->create($newVehicle);
        $uniqueNo = ((int)$created->id < 999) ? ('1' . sprintf('%04d', (int)$created->id)) : (string)$created->id;
        LegacyVehicle::query()->whereKey((int)$created->id)->update(['vehicle_unique_id' => $uniqueNo]);

        $sourceImages = LegacyVehicleImage::query()->where('vehicle_id', $sourceId)->get();
        foreach ($sourceImages as $img) {
            $copy = $img->toArray();
            unset($copy['id']);
            $copy['vehicle_id'] = (int)$created->id;
            LegacyVehicleImage::query()->create($copy);
        }
        $sourceLocations = LegacyVehicleLocation::query()->where('vehicle_id', $sourceId)->get();
        foreach ($sourceLocations as $loc) {
            $copy = $loc->toArray();
            unset($copy['id']);
            $copy['vehicle_id'] = (int)$created->id;
            LegacyVehicleLocation::query()->create($copy);
        }
        $sourceSetting = LegacyVehicleSetting::query()->where('vehicle_id', $sourceId)->first();
        if ($sourceSetting) {
            $copy = $sourceSetting->toArray();
            unset($copy['id']);
            $copy['vehicle_id'] = (int)$created->id;
            LegacyVehicleSetting::query()->create($copy);
        }
        $sourceRule = LegacyDepositRule::query()->where('vehicle_id', $sourceId)->first();
        if ($sourceRule) {
            $copy = $sourceRule->toArray();
            unset($copy['id']);
            $copy['vehicle_id'] = (int)$created->id;
            $copy['user_id'] = $newUserId;
            LegacyDepositRule::query()->create($copy);
        }

        return redirect('/admin/vehicles/index');
    }

    public function admin_checkVinDetails(Request $request): JsonResponse
    {
        $vin = strtoupper(trim((string)$request->input('vin', '')));
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

    public function admin_lastlocation(Request $request, $vehicle_id = null)
    {
        $vehicleId = $this->decodeId((string)$vehicle_id);
        $vehicle = $vehicleId ? LegacyVehicle::query()->find($vehicleId) : null;
        return view('admin.vehicles.lastlocation', [
            'vehicle' => $vehicle,
            'vehicleLocation' => ['status' => false, 'message' => 'Passtime provider migration pending'],
        ]);
    }

    public function admin_getVehicleDynamicFare(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('vehicleid', 0);
        $tag = (string)$request->input('tag', 'D');
        $vehicle = $vehicleId > 0 ? LegacyVehicle::query()->find($vehicleId) : null;
        if (!$vehicle) {
            return response()->json(['status' => 'error', 'msg' => 'Sorry, something went wrong. Please try again']);
        }
        $estimate = $tag === 'D' ? (float)$vehicle->rate : (float)$vehicle->day_rent;
        return response()->json(['status' => 'success', 'data' => ['estimated_fare' => $estimate], 'msg' => '']);
    }

    public function admin_getvehicledetails(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string)$request->input('vehicleid', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Invalid Vehicle ID']);
        }
        $vehicle = LegacyVehicle::query()->find($vehicleId, [
            'id', 'plate_number', 'inspection_image', 'registration_image', 'gps_serialno',
            'passtime_serialno', 'registered_state', 'reg_name_date', 'reg_name_exp_date', 'wireless_gps_serial',
        ]);
        return response()->json(['status' => true, 'vehicle' => $vehicle, 'orderid' => $this->decodeId((string)$request->input('orderid', ''))]);
    }

    public function admin_updateVehicleDetails(Request $request): JsonResponse
    {
        if ($request->ajax() && $request->filled('pk')) {
            $pk = (int)$request->input('pk');
            $name = (string)$request->input('name');
            $value = $request->input('value');
            if ($pk > 0 && $name !== '') {
                LegacyVehicle::query()->whereKey($pk)->update([$name => $value]);
                return response()->json(['status' => true, 'message' => '']);
            }
        }

        $vehicleId = (int)$request->input('Vehicle.id', 0);
        if ($vehicleId <= 0) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $dataToSave = ['id' => $vehicleId];
        foreach (['registration_image', 'insurance_image', 'inspection_image'] as $field) {
            $file = $request->file('Vehicle.' . $field);
            if (!$file) {
                continue;
            }
            $ext = strtolower((string)$file->getClientOriginalExtension());
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
        LegacyVehicle::query()->whereKey($vehicleId)->update($dataToSave);
        return response()->json(['status' => true, 'message' => '']);
    }

    public function admin_getVehicleGps(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('vehicleid', 0);
        $type = (string)$request->input('type', 'gps_provider');
        $vehicle = $vehicleId > 0 ? LegacyVehicle::query()->find($vehicleId, ['gps_serialno', 'passtime_serialno']) : null;
        if (!$vehicle) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $serial = $type === 'passtime' ? (string)($vehicle->passtime_serialno ?? '') : (string)($vehicle->gps_serialno ?? '');
        return response()->json(['status' => true, 'message' => '', 'gps_serialno' => $serial]);
    }

    public function admin_gps_setting(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string)$request->input('vehicle_id', ''));
        if (!$request->ajax() || !$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $exists = LegacyVehicleSetting::query()->where('vehicle_id', $vehicleId)->first();
        $settingData = [];
        if ($exists && !empty($exists->data)) {
            $settingData = json_decode((string)$exists->data, true) ?: [];
        }
        $html = view('admin.vehicles.gps_setting', [
            'vehicle' => base64_encode((string)$vehicleId),
            'vehicledepndend' => $exists && !empty($exists->data),
            'csSetting' => $settingData,
        ])->render();
        return response()->json(['status' => true, 'message' => '', 'html' => $html]);
    }

    public function admin_save_gpssetting(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $payload = $request->input('CsSetting', []);
        $vehicleId = $this->decodeId((string)data_get($payload, 'vehicle_id', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $exists = LegacyVehicleSetting::query()->where('vehicle_id', $vehicleId)->first();
        $gpsProvider = (string)data_get($payload, 'gps_provider', '');
        $passtime = (string)data_get($payload, 'passtime', '');
        if ($gpsProvider === '' && $passtime === '') {
            if ($exists) {
                if (!empty($exists->financing)) {
                    LegacyVehicleSetting::query()->whereKey((int)$exists->id)->update(['data' => null]);
                } else {
                    LegacyVehicleSetting::query()->whereKey((int)$exists->id)->delete();
                }
            }
            return response()->json(['status' => true, 'message' => 'Setting saved successfully']);
        }
        $data = json_encode($payload);
        if ($exists) {
            LegacyVehicleSetting::query()->whereKey((int)$exists->id)->update(['data' => $data]);
        } else {
            LegacyVehicleSetting::query()->create(['vehicle_id' => $vehicleId, 'data' => $data]);
        }
        return response()->json(['status' => true, 'message' => 'Setting saved successfully']);
    }

    public function admin_delete_gpssetting(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $vehicleId = $this->decodeId((string)$request->input('vehicle_id', ''));
        if (!$vehicleId) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.']);
        }
        $exists = LegacyVehicleSetting::query()->where('vehicle_id', $vehicleId)->first();
        if ($exists) {
            if (!empty($exists->financing)) {
                LegacyVehicleSetting::query()->whereKey((int)$exists->id)->update(['data' => null]);
            } else {
                LegacyVehicleSetting::query()->whereKey((int)$exists->id)->delete();
            }
        }
        return response()->json(['status' => true, 'message' => 'Setting deleted successfully']);
    }

    public function admin_changePasstimeVehicleStatus(Request $request): JsonResponse
    {
        $vehicleId = $this->decodeId((string)$request->input('vehicleid', ''));
        $status = trim((string)$request->input('status', ''));
        if (!$vehicleId || !in_array($status, ['active', 'inactive'], true)) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'vehicleid' => $vehicleId]);
        }
        // External starter activation/deactivation migration is pending;
        // preserve DB status toggle endpoint contract for admin UI.
        LegacyVehicle::query()->whereKey($vehicleId)->update(['passtime_status' => $status === 'active' ? 1 : 0]);
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
            $allowed = array_flip(Schema::getColumnListing((new LegacyVehicle())->getTable()));
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
            $ext = strtolower((string)$file->getClientOriginalExtension());
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
        LegacyVehicleLocation::query()->where('vehicle_id', $vehicleId)->delete();
        $locTable = (new LegacyVehicleLocation())->getTable();
        $hasGeoCol = Schema::hasColumn($locTable, 'geo');
        $geoType = $hasGeoCol ? Schema::getColumnType($locTable, 'geo') : null;
        foreach ($rows as $loc) {
            if (!is_array($loc)) {
                continue;
            }
            $lat = isset($loc['lat']) ? trim((string)$loc['lat']) : '';
            $lng = isset($loc['lng']) ? trim((string)$loc['lng']) : '';
            if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }
            $latf = (float)$lat;
            $lngf = (float)$lng;
            $insert = [
                'vehicle_id' => $vehicleId,
                'lat' => $latf,
                'lng' => $lngf,
                'address' => isset($loc['address']) ? (string)$loc['address'] : '',
            ];
            if ($hasGeoCol) {
                if (in_array($geoType, ['integer', 'bigint', 'smallint', 'tinyint'], true)) {
                    $insert['geo'] = 0;
                } else {
                    $insert['geo'] = DB::raw('POINT(' . $lngf . ',' . $latf . ')');
                }
            }
            LegacyVehicleLocation::query()->create($insert);
        }
    }

    private function uploadMaxBytes(): int
    {
        return min($this->iniToBytes((string)ini_get('upload_max_filesize')), $this->iniToBytes((string)ini_get('post_max_size')));
    }

    private function iniToBytes(string $val): int
    {
        $val = trim($val);
        if ($val === '') {
            return 0;
        }
        $n = (int)$val;
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
        $year = isset($data['year']) && $data['year'] !== '' ? substr((string)$data['year'], -2) . '-' : '';
        $make = isset($data['make']) && $data['make'] !== '' ? str_replace(' ', '_', (string)$data['make']) . '-' : '';
        $model = isset($data['model']) && $data['model'] !== '' ? str_replace(' ', '_', (string)$data['model']) : '';
        $vinTail = isset($data['vin_no']) && $data['vin_no'] !== '' ? '-' . substr((string)$data['vin_no'], -6) : '';
        return $year . $make . $model . $vinTail;
    }

    private function decodeId(?string $id): ?int
    {
        if (is_numeric($id)) {
            return (int)$id;
        }
        if (is_string($id) && $id !== '') {
            $decoded = base64_decode($id, true);
            if ($decoded !== false && is_numeric($decoded)) {
                return (int)$decoded;
            }
        }
        return null;
    }

    private function normalizeAmountOptions($input): array
    {
        if (!is_array($input)) {
            return [];
        }
        $rows = [];
        foreach ($input as $row) {
            $day = (int)data_get($row, 'after_day', 0);
            $amount = (float)data_get($row, 'amount', 0);
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

