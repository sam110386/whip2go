<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\LinkedVehiclesTrait;
use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\CsSetting;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use App\Models\Legacy\User;
use Illuminate\Http\Request;

class LinkedVehiclesController extends LegacyAppController
{
    use LinkedVehiclesTrait {
        handleUpload as protected trait_handleUpload;
        _getVehicleGps as protected trait_getVehicleGps;
    }

    protected bool $shouldLoadLegacyModules = true;

    private function getDealerIds($adminParentId)
    {
        return AdminUserAssociation::where('admin_id', $adminParentId)->pluck('user_id')->toArray();
    }

    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $adminUser = session('AdminUser');
        if (!empty($adminUser['administrator'])) {
            return redirect('/admin/vehicles/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $dealerIds = $this->getDealerIds($adminUser['parent_id']);

        $searchData = $request->input('Search', []);
        $namedData  = $request->query();

        $searchin = $namedData['searchin'] ?? $searchData['searchin'] ?? '';
        $value    = $namedData['keyword']  ?? $searchData['keyword']  ?? '';
        $showtype = $namedData['showtype'] ?? $searchData['show']     ?? '';
        $userId   = $namedData['user_id']  ?? $searchData['user_id']  ?? '';

        $query = Vehicle::query()
            ->from('vehicles as Vehicle')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'Vehicle.user_id')
            ->select('Vehicle.*', 'Owner.first_name', 'Owner.last_name')
            ->whereIn('Vehicle.user_id', $dealerIds);

        if ($value !== '') {
            $value1    = strip_tags($value);
            $fieldname = empty($searchin) ? 'All' : $searchin;
            if ($fieldname === 'All') {
                $query->where(function ($q) use ($value1) {
                    $q->where('Vehicle.vehicle_name',      'LIKE', "%{$value1}%")
                      ->orWhere('Vehicle.vehicle_unique_id', 'LIKE', "%{$value1}%");
                });
            } elseif ($fieldname === 'cab_name') {
                $query->where('Vehicle.cab_type', 'LIKE', "%{$value1}%");
            } else {
                $query->where("Vehicle.{$fieldname}", 'LIKE', "%{$value1}%");
            }
        }

        if ($showtype === 'Active') {
            $query->where('Vehicle.status', 1);
        } elseif ($showtype === 'Deactive') {
            $query->where('Vehicle.status', 0);
        }

        if (!empty($userId)) {
            $query->where('Vehicle.user_id', $userId);
        }

        if ($request->input('export') === 'Export') {
            return $this->exportToCsv($query->get());
        }

        $sessionLimitKey  = 'LinkedVehicles_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $vehicleDetails = $query->orderBy('Vehicle.id', 'DESC')->paginate($limit)->withQueryString();

        $viewData = [
            'title_for_layout' => 'Manage Vehicles',
            'vehicleDetails'   => $vehicleDetails,
            'keyword'          => $value,
            'show'             => $showtype,
            'fieldname'        => empty($searchin) ? 'All' : $searchin,
            'user_id'          => $userId,
            'options'          => [
                'vehicle_name'   => 'Car #',
                'vehicle_number' => 'Vehicle Number',
                'cab_name'       => 'Vehicle Type',
                'plate_number'   => 'Plate Number',
            ],
        ];

        if ($request->ajax()) {
            return view('cloud.linked_vehicles.elements.index_ajax', $viewData);
        }

        return view('cloud.linked_vehicles.index', $viewData);
    }

    public function cloud_add(Request $request, $vehicle_id = null)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $adminUser = session('AdminUser');
        if (!empty($adminUser['administrator'])) {
            return redirect('/admin/vehicles/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $dealerIds = $this->getDealerIds($adminUser['parent_id']);
        $decodedId = !empty($vehicle_id) ? base64_decode($vehicle_id) : null;
        $listTitle = !empty($decodedId) ? 'Edit Vehicle' : 'Add Vehicle';

        if ($request->isMethod('post') || $request->isMethod('put')) {
            if (empty($decodedId)) {
                return redirect('/cloud/linked_vehicles/index')->with('error', 'Sorry, you are not authorized for this action');
            }

            $dataValues = $request->input('Vehicle', []);
            $dataValues['cab_id']   = '';
            $dataValues['cab_type'] = !empty($dataValues['cab_type']) ? $dataValues['cab_type'] : 'Regular Sedan';
            $dataValues['status']   = 1;

            foreach (['insurance_policy_exp_date', 'inspection_exp_date', 'state_insp_exp_date', 'reg_name_exp_date', 'reg_name_date'] as $dateField) {
                if (!empty($dataValues[$dateField])) {
                    $dataValues[$dateField] = date('Y-m-d', strtotime($dataValues[$dateField]));
                }
            }

            $dataValues['vehicle_name'] = ltrim(
                (!empty($dataValues['year'])   ? substr($dataValues['year'], -2) . '-'            : '') .
                (!empty($dataValues['make'])   ? str_replace(' ', '_', $dataValues['make'])  . '-' : '') .
                (!empty($dataValues['model'])  ? str_replace(' ', '_', $dataValues['model'])       : '') .
                (!empty($dataValues['vin_no']) ? '-' . substr($dataValues['vin_no'], -6)           : ''),
                '-'
            );

            $dataValues['rate'] = preg_replace('/[^0-9,.]/', '', $dataValues['rate'] ?? '');

            $rentOpt = $dataValues['rent_opt'] ?? [];
            $totalRent = array_sum(array_column(array_filter($rentOpt), 'amount'));
            $dataValues['rent_opt'] = $totalRent ? json_encode(array_values(array_filter(array_map('array_filter', $rentOpt)))) : '';

            if (($dataValues['fare_type'] ?? '') === 'D') {
                $dataValues['day_rent'] = 0;
            }
            unset($dataValues['accudata']);

            if (empty($dataValues['id'])) {
                $settingObj = CsSetting::where('user_id', $dataValues['user_id'] ?? 0)->first();
                $dataValues['allowed_miles'] = $settingObj->allowed_miles ?? 0;
            }

            $vehicle = !empty($dataValues['id']) ? Vehicle::find($dataValues['id']) : new Vehicle();
            $vehicle->fill($dataValues)->save();
            $vehicleId = $vehicle->id;

            if (empty($dataValues['id'])) {
                $vehicle->vehicle_unique_id = $vehicleId < 999 ? '1' . sprintf('%04d', $vehicleId) : (string)$vehicleId;
                $vehicle->save();
            }

            foreach (['registration_image', 'insurance_image', 'inspection_image'] as $fileField) {
                if ($request->hasFile($fileField)) {
                    $file = $request->file($fileField);
                    $ext  = strtolower($file->getClientOriginalExtension());
                    if (in_array($ext, $this->allowedExtensions)) {
                        $suffix   = str_replace('_image', '', $fileField);
                        $filename = 'vehi_' . $vehicleId . '_' . $suffix . '.' . $ext;
                        $file->move(public_path('img/custom/vehicle_photo/'), $filename);
                        $vehicle->{$fileField} = $filename;
                    }
                }
            }
            $vehicle->save();

            if (($dataValues['fare_type'] ?? '') === 'D') {
                $dynamicFareClass = '\\App\\Models\\Legacy\\DynamicFare';
                if (class_exists($dynamicFareClass)) {
                    (new $dynamicFareClass())->calculateDynamicFare(array_merge($dataValues, ['id' => $vehicleId]), 1);
                }
            }

            if (empty($dataValues['id'])) {
                return redirect('/cloud/linked_vehicles/addVehicle/' . base64_encode($vehicleId))->with('success', 'Vehicle data saved successfully');
            }
            return redirect('/cloud/linked_vehicles/index')->with('success', 'Vehicle data updated successfully');
        }

        // Load for edit
        if (!empty($decodedId)) {
            $vehicle = Vehicle::whereIn('user_id', $dealerIds)->where('id', $decodedId)->first();
            if (!$vehicle) {
                return redirect('/cloud/linked_vehicles/index')->with('error', 'Sorry, you are not authorized user for this action');
            }
            $vehicleArr = $vehicle->toArray();
            $vehicleArr['rent_opt'] = !empty($vehicleArr['rent_opt']) ? json_decode($vehicleArr['rent_opt'], true) : [];
            $vehicleArr['accudata'] = !empty($vehicleArr['accudata']) ? json_decode($vehicleArr['accudata'], true) : [];
            $data = ['Vehicle' => $vehicleArr];
        } else {
            return redirect('/cloud/linked_vehicles/index')->with('error', 'Sorry, you are not authorized for this action');
        }

        return view('cloud.linked_vehicles.cloud_add', compact('listTitle', 'data'));
    }

    public function cloud_saveImage(Request $request)
    {
        return response()->json($this->handleUpload($request, $request->input('id'), 'vehicleimage'));
    }

    public function cloud_deleteImage(Request $request)
    {
        $imageData = VehicleImage::find($request->input('key'));
        if ($imageData) {
            $path = public_path('img/custom/vehicle_photo/' . $imageData->filename);
            if (!empty($imageData->filename) && file_exists($path)) {
                @unlink($path);
            }
            $imageData->delete();
        }
        return response()->json(['success' => true, 'key' => '']);
    }

    public function cloud_checkVinDetails(Request $request)
    {
        $vin    = $request->input('vin');
        $return = ['status' => 'error', 'message' => 'Invalid Json', 'result' => []];

        if (!empty($vin)) {
            $resp = \Illuminate\Support\Facades\Http::get("https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValuesExtended/{$vin}?format=json");
            if ($resp->successful()) {
                $return = ['status' => 'success', 'message' => 'record found', 'result' => $resp->json('Results.0') ?? []];
            }
        }

        return response()->json($return);
    }

    public function cloud_ownerautocomplete(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $adminUser  = session('AdminUser');
        if (!empty($adminUser['administrator'])) {
            return response()->json([]);
        }

        $dealerIds  = $this->getDealerIds($adminUser['parent_id']);
        $searchTerm = $request->query('term', '');
        $userId     = $request->query('user_id', '');

        if (!empty($userId)) {
            $user   = User::find($userId, ['id', 'first_name', 'contact_number']);
            $result = $user ? ['id' => $user->id, 'tag' => $user->first_name . ' - ' . $user->contact_number] : [];
        } else {
            $result = User::where('status', 1)
                ->whereIn('id', $dealerIds)
                ->where(function ($q) use ($searchTerm) {
                    $q->where('contact_number', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('first_name',    'LIKE', "%{$searchTerm}%")
                      ->orWhere('email',          'LIKE', "%{$searchTerm}%")
                      ->orWhere('last_name',      'LIKE', "%{$searchTerm}%");
                })
                ->orderBy('first_name')
                ->limit(10)
                ->get(['id', 'first_name', 'contact_number'])
                ->map(fn($u) => ['id' => $u->id, 'tag' => $u->first_name . ' - ' . $u->contact_number])
                ->values()
                ->toArray();
        }

        return response()->json($result);
    }

    public function cloud_loadVehicleStatus(Request $request)
    {
        $vehicleId = base64_decode(trim($request->input('vehicleid')));
        $vehicle   = Vehicle::find($vehicleId, ['id', 'status']);
        return view('cloud.linked_vehicles.cloud_load_vehicle_status', ['vehcile' => $vehicle]);
    }

    public function cloud_changeVehicleStatus(Request $request)
    {
        $data      = $request->input('Vehicle', []);
        $vehicleId = $data['id'] ?? null;
        if ($vehicleId) {
            Vehicle::where('id', $vehicleId)->update(array_filter($data, fn($v) => $v !== null));
        }
        return response()->json(['status' => true, 'vehicleid' => $vehicleId]);
    }

    public function cloud_loadSingleRow(Request $request)
    {
        $vehicleId = trim($request->input('vehicleid'));
        $vehicle   = Vehicle::query()
            ->from('vehicles as Vehicle')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'Vehicle.user_id')
            ->select('Vehicle.*', 'Owner.first_name', 'Owner.last_name')
            ->where('Vehicle.id', $vehicleId)
            ->first();
        return view('cloud.linked_vehicles.cloud_single_row', ['vehcile' => $vehicle]);
    }

    public function cloud_changePasstimeVehicleStatus(Request $request)
    {
        $vehicleId = base64_decode($request->input('vehicleid'));
        $status    = trim($request->input('status'));
        $return    = ['status' => false, 'message' => 'Something went wrong', 'vehicleid' => $vehicleId];

        if ($vehicleId) {
            $vehicleData = Vehicle::query()
                ->from('vehicles as Vehicle')
                ->leftJoin('cs_settings as CsSetting', 'CsSetting.user_id', '=', 'Vehicle.user_id')
                ->leftJoin('vehicle_settings as VehicleSetting', 'VehicleSetting.vehicle_id', '=', 'Vehicle.id')
                ->select('Vehicle.id', 'Vehicle.passtime_serialno', 'Vehicle.passtime_status', 'CsSetting.*', 'Vehicle.user_id', 'VehicleSetting.*')
                ->where('Vehicle.id', $vehicleId)
                ->first();

            $passtimeClass = '\\App\\Models\\Legacy\\Passtime';
            if (!empty($vehicleData->passtime_serialno) && class_exists($passtimeClass)) {
                $passtime   = new $passtimeClass();
                $vehicleArr = ['CsSetting' => $vehicleData->toArray(), 'Vehicle' => $vehicleData->toArray(), 'VehicleSetting' => []];

                if ($status === 'active') {
                    $resp = $passtime->ActivateVehicle($vehicleArr);
                    if ($resp['status']) {
                        Vehicle::where('id', $vehicleId)->update(['passtime_status' => 1]);
                        $return['status'] = true;
                    }
                } elseif ($status === 'inactive') {
                    $resp = $passtime->deActivateVehicle($vehicleArr);
                    if ($resp['status']) {
                        Vehicle::where('id', $vehicleId)->update(['passtime_status' => 0]);
                        $return['status'] = true;
                    }
                }
            } else {
                $return['message'] = empty($vehicleData->passtime_serialno)
                    ? 'Vehicle Passtime serial # not set'
                    : "Vehicle Owner's GPS not set";
            }
        }

        return response()->json($return);
    }

    public function cloud_reorderImage(Request $request)
    {
        $i = 1;
        foreach ($request->input('stack', []) as $stack) {
            VehicleImage::where('id', $stack['key'])->update(['iorder' => $i++]);
        }
        return response()->json(['success' => true]);
    }

    public function cloud_getVehicleRegistration(Request $request)
    {
        $vehicleId = base64_decode($request->input('vehicleid'));
        $return    = ['status' => false, 'message' => 'Invalid Vehicle ID', 'result' => []];

        if (!empty($vehicleId)) {
            $vehicle = Vehicle::find($vehicleId, ['registration_image']);
            if (!empty($vehicle->registration_image)) {
                $path = public_path('img/custom/vehicle_photo/' . $vehicle->registration_image);
                $return = file_exists($path)
                    ? ['status' => true,  'message' => 'Success',                           'result' => ['file' => url('img/custom/vehicle_photo/' . $vehicle->registration_image)]]
                    : ['status' => false, 'message' => 'sorry, document not exists',        'result' => []];
            } else {
                $return = ['status' => false, 'message' => 'sorry, document not added yet by owner', 'result' => []];
            }
        }

        return response()->json($return);
    }

    public function cloud_getvehicledetails(Request $request)
    {
        $vehicleId = base64_decode($request->input('vehicleid'));
        $orderId   = base64_decode($request->input('orderid'));
        $vehicle   = Vehicle::find($vehicleId, ['id', 'plate_number', 'inspection_image', 'registration_image', 'gps_serialno', 'passtime_serialno']);

        return view('cloud.linked_vehicles.cloud_getvehicledetails', compact('vehicle', 'orderId'));
    }

    public function cloud_updateVehicleDetails(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, something went wrong.'];

        if (!$request->ajax()) {
            return response()->json($return);
        }

        // X-editable single-field update
        if ($request->has('pk') && !empty($request->input('pk'))) {
            Vehicle::where('id', $request->input('pk'))->update([
                $request->input('name') => $request->input('value')
            ]);
            return response()->json(['status' => true]);
        }

        $vehicleId   = $request->input('Vehicle.id');
        $allowedSize = $this->fileSizeInBytes(ini_get('upload_max_filesize'));
        $toSave      = [];

        foreach (['registration_image', 'insurance_image', 'inspection_image'] as $field) {
            if ($request->hasFile("Vehicle.{$field}")) {
                $file = $request->file("Vehicle.{$field}");
                if ($file->getSize() > $allowedSize) {
                    $return['message'] = "Sorry {$field} could not be uploaded, it must be in proper size";
                    continue;
                }
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, $this->allowedExtensions)) {
                    $suffix       = str_replace('_image', '', $field);
                    $filename     = 'vehi_' . $vehicleId . '_' . $suffix . '.' . $ext;
                    $file->move(public_path('img/custom/vehicle_photo/'), $filename);
                    $toSave[$field] = $filename;
                }
            }
        }

        if (!empty($toSave)) {
            Vehicle::where('id', $vehicleId)->update($toSave);
        }

        $return['status'] = true;
        return response()->json($return);
    }

    public function cloud_lastlocation(Request $request, $vehicle_id)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $vehicleId   = base64_decode($vehicle_id);
        $vehicleData = Vehicle::query()
            ->from('vehicles as Vehicle')
            ->leftJoin('cs_settings as CsSetting', 'CsSetting.user_id', '=', 'Vehicle.user_id')
            ->leftJoin('vehicle_settings as VehicleSetting', 'VehicleSetting.vehicle_id', '=', 'Vehicle.id')
            ->select('Vehicle.*', 'CsSetting.*', 'VehicleSetting.*')
            ->where('Vehicle.id', $vehicleId)
            ->first();

        $vehicleLocation = ['status' => false];
        $passtimeClass   = '\\App\\Models\\Legacy\\Passtime';

        if (class_exists($passtimeClass)) {
            $vehicleLocation = (new $passtimeClass())->getVehicleLocation([
                'Vehicle'        => $vehicleData ? $vehicleData->toArray() : [],
                'CsSetting'      => [],
                'VehicleSetting' => [],
            ]);
        }

        if (!$vehicleLocation['status']) {
            return redirect('/cloud/vehicles/index')->with('error', 'Sorry, this vehicle data not found.');
        }

        return view('cloud.linked_vehicles.cloud_lastlocation', compact('vehicleLocation'));
    }

    public function cloud_getVehicleGps(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, something went wrong.'];

        if ($request->ajax() && !empty($request->input('vehicleid'))) {
            $return = $this->_getVehicleGps($request->input('vehicleid'), $request->input('type'));
        }

        return response()->json($return);
    }

    private function exportToCsv($vehicles)
    {
        return response()->stream(function () use ($vehicles) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['Vehicle#', 'Vehicle Name', 'Plate Number', 'VIN #', 'Stock #', 'Color', 'Make', 'Model', 'Status']);

            foreach ($vehicles as $v) {
                if ($v->passtime_status == 0 || $v->passtime_status == 2) {
                    $status = 'Starter Disabled';
                } elseif ($v->passtime_status == 1 && $v->booked == 1) {
                    $status = 'Booked';
                } else {
                    $status = $v->status == 1 ? 'Active' : 'Inactive';
                }

                fputcsv($fp, [$v->vehicle_unique_id, $v->vehicle_name, $v->plate_number, $v->vin_no, $v->stock_no, $v->color, $v->make, $v->model, $status]);
            }
            fclose($fp);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=vehicle_data_' . date('Y-m-d') . '.csv',
        ]);
    }

    private function fileSizeInBytes($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val  = (int)$val;
        switch ($last) {
            case 'g': $val *= 1024;
            // no break
            case 'm': $val *= 1024;
            // no break
            case 'k': $val *= 1024;
        }
        return $val;
    }

    protected function handleUpload(Request $request, $vehicleId, $fileKey = 'vehicleimage')
    {
        return $this->trait_handleUpload($request, $vehicleId, $fileKey);
    }

    protected function _getVehicleGps($vehicleId, $type)
    {
        return $this->trait_getVehicleGps($vehicleId, $type);
    }
}
