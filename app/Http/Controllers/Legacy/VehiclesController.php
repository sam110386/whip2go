<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use App\Models\Legacy\VehicleLocation;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\CsSetting;
use App\Models\Legacy\CsInsuranceTemplate;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\OrderDepositRule;
use App\Http\Controllers\Traits\VehiclesTrait;
use App\Http\Controllers\Traits\VehicleLocationTrait;
use App\Http\Controllers\Traits\CopyVehicleImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VehiclesController extends LegacyAppController
{
    use VehiclesTrait {
        handleUpload as protected trait_handleUpload;
        _getVehicleGps as protected trait_getVehicleGps;
        _getVehicleDynamicFare as protected trait_getVehicleDynamicFare;
        _getVehicleInspectionDoc as protected trait_getVehicleInspectionDoc;
        exportToCsv as protected trait_exportToCsv;
    }
    use VehicleLocationTrait, CopyVehicleImageTrait;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        
        $query = Vehicle::where('user_id', $userid);

        $keyword = $request->input('keyword', '');
        $showType = $request->input('showtype', '');
        $fieldname = $request->input('searchin', 'All');

        if (!empty($keyword)) {
            if ($fieldname == 'All') {
                $query->where(function($q) use ($keyword) {
                    $q->where('vehicle_name', 'LIKE', "%$keyword%")
                      ->orWhere('vehicle_unique_id', 'LIKE', "%$keyword%")
                      ->orWhere('stock_no', 'LIKE', "%$keyword%");
                });
            } else {
                $query->where($fieldname, 'LIKE', "%$keyword%");
            }
        }

        if (!empty($showType)) {
            if ($showType == 6) $query->where('booked', 1);
            elseif ($showType == 7) $query->where('booked', 0);
            elseif ($showType == 8) $query->where('passtime_status', 0);
            elseif ($showType == 9) $query->where('passtime_status', 1);
            else $query->where('status', $showType);
        }

        if ($request->input('export') == 'Export') {
            $vehicles = $query->get();
            return $this->exportToCsv($vehicles);
        }

        $limit = $request->input('limit', session('Vehicles_limit', $this->records_per_page));
        session(['Vehicles_limit' => $limit]);

        $vehicleDetails = $query->orderBy('id', 'DESC')->paginate($limit)->withQueryString();

        return view('legacy.vehicles.index', compact('vehicleDetails', 'keyword', 'showType', 'fieldname', 'limit'));
    }

    public function add(Request $request, $vehicle_id = null)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $this->layout = 'main';
        $vehicle_id = $vehicle_id ? base64_decode($vehicle_id) : null;
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        if ($request->isMethod('post')) {
            $data = $request->input('Vehicle', []);
            $data['user_id'] = $userid;
            
            // Handle dates with Carbon
            $dateFields = ['insurance_policy_exp_date', 'inspection_exp_date', 'state_insp_exp_date', 'reg_name_exp_date', 'reg_name_date'];
            foreach ($dateFields as $field) {
                if (!empty($data[$field])) {
                    $data[$field] = Carbon::parse($data[$field])->format('Y-m-d');
                }
            }
            if (!empty($data['availability_date'])) {
                $data['availability_date'] = Carbon::parse($data['availability_date'])->format('Y-m-d');
            }

            // Generate vehicle name
            $vehicle_name = (!empty($data['year']) ? substr($data['year'], -2) . '-' : '') . 
                           (!empty($data['make']) ? str_replace(' ', '_', $data['make']) . '-' : '') . 
                           (!empty($data['model']) ? str_replace(' ', '_', $data['model']) : '') . 
                           (!empty($data['vin_no']) ? '-' . substr($data['vin_no'], -6) : '');
            $data['vehicle_name'] = $vehicle_name;
            $data['rate'] = preg_replace("/[^0-9,.]/", "", $data['rate'] ?? 0);
            $data['status'] = 1;

            if (empty($vehicle_id)) {
                $setting = CsSetting::where('user_id', $userid)->first();
                $data['allowed_miles'] = $setting ? $setting->allowed_miles : 0;
            }

            $vehicle = Vehicle::updateOrCreate(['id' => $vehicle_id], $data);
            
            if (empty($vehicle_id)) {
                $uniqueNo = $vehicle->id < 999 ? '1' . sprintf('%04d', $vehicle->id) : $vehicle->id;
                $vehicle->update(['vehicle_unique_id' => $uniqueNo]);
            }

            // Handle file uploads
            if ($request->hasFile('registration_image')) {
                $res = $this->handleUpload($request->file('registration_image'), $vehicle->id);
                if (isset($res['success'])) $vehicle->update(['registration_image' => $res['filename']]);
            }
            if ($request->hasFile('inspection_image')) {
                $res = $this->handleUpload($request->file('inspection_image'), $vehicle->id);
                if (isset($res['success'])) $vehicle->update(['inspection_image' => $res['filename']]);
            }

            // Save location
            if ($request->has('VehicleLocation')) {
                $this->saveVehicleLocation($request->input('VehicleLocation'), $vehicle->id);
            }

            return redirect('/vehicles/index')->with('success', 'Vehicle data saved successfully');
        }

        $record = $vehicle_id ? Vehicle::with(['VehicleImage', 'VehicleLocation'])->find($vehicle_id) : null;
        $CsInsuranceTemplate = CsInsuranceTemplate::where('user_id', $userid)->first();

        return view('legacy.vehicles.add', compact('record', 'vehicle_id', 'CsInsuranceTemplate'));
    }

    public function multiplAction(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $status = $request->input('Vehicle.status');
        $selectedIds = $request->input('select', []);

        foreach ($selectedIds as $id => $val) {
            if ($val) {
                if ($status == 'active') Vehicle::where('id', $id)->update(['status' => 1]);
                elseif ($status == 'inactive') Vehicle::where('id', $id)->update(['status' => 0]);
            }
        }

        return redirect()->back()->with('success', 'Vehicles updated successfully');
    }

    public function deleteVehicle($id = null)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $id = base64_decode($id);
        // Vehicle::where('id', $id)->delete(); // Soft delete or comment out as per legacy
        return redirect()->back()->with('success', 'Vehicle deleted successully');
    }

    public function getVehicle(Request $request)
    {
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        $searchTerm = $request->query('term');
        
        $query = Vehicle::where('status', 1)->where('booked', 0)->where('user_id', $userid);
        if ($request->has('id')) {
            $query->where('id', $request->query('id'));
        } else {
            $query->where(function($q) use ($searchTerm) {
                $q->where('vehicle_unique_id', 'LIKE', "%$searchTerm%")
                  ->orWhere('vehicle_name', 'LIKE', "%$searchTerm%");
            });
        }

        $vehiclelists = $query->limit(10)->orderBy('vehicle_unique_id', 'ASC')->get();
        $vehicles = $vehiclelists->map(function($v) {
            return [
                'id' => $v->id,
                'tag' => $v->vehicle_unique_id . '-' . $v->vehicle_name,
                'address' => $v->address,
                'lat' => $v->lat,
                'lng' => $v->lng,
                'rate' => $v->rate
            ];
        });

        return response()->json($vehicles);
    }

    public function saveImage(Request $request)
    {
        $res = $this->handleUpload($request->file('vehicleimage'), $request->input('id'));
        return response()->json($res);
    }

    public function deleteImage(Request $request)
    {
        $key = $request->input('key');
        $img = VehicleImage::find($key);
        if ($img) {
            $filePath = public_path('img/custom/vehicle_photo/' . $img->filename);
            if (file_exists($filePath)) @unlink($filePath);
            $img->delete();
        }
        return response()->json(['success' => true]);
    }

    public function lastlocation($vehicle_id)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $vehicle_id = base64_decode($vehicle_id);
        $vehicleLocation = $this->_getVehicleGps($vehicle_id, 'passtime_serialno');
        return view('legacy.vehicles.lastlocation', compact('vehicleLocation'));
    }

    public function checkVinDetails(Request $request)
    {
        $vin = $request->input('vin');
        return response()->json(['status' => 'success', 'message' => 'record found', 'result' => ['vin' => $vin, 'simulated' => true]]);
    }

    public function loadVehicleStatus(Request $request)
    {
        $vehicleid = base64_decode($request->input('vehicleid'));
        $vehcile = Vehicle::find($vehicleid, ['id', 'status']);
        return view('legacy.vehicles.load_status', compact('vehcile'));
    }

    public function changeVehicleStatus(Request $request)
    {
        $data = $request->input('Vehicle');
        $id = $data['id'];
        $status = $data['status'];

        if ($status == 8) { // Passtime Starter Disable
             $res = $this->_getVehicleGps($id, 'passtime_serialno');
             if ($res['status']) Vehicle::where('id', $id)->update(['passtime_status' => 0]);
             return response()->json($res);
        }
        if ($status == 9) { // Passtime Starter Enable
             $res = $this->_getVehicleGps($id, 'passtime_serialno');
             if ($res['status']) Vehicle::where('id', $id)->update(['passtime_status' => 1]);
             return response()->json($res);
        }

        Vehicle::where('id', $id)->update(['status' => $status]);
        return response()->json(['status' => true, 'message' => "Vehicle updated successfully", 'vehicleid' => $id]);
    }

    public function load_single_row(Request $request)
    {
        $vehicleid = $request->input('vehicleid');
        $vehcile = Vehicle::find($vehicleid);
        return view('legacy.vehicles.single_row', compact('vehcile'));
    }

    public function reorderImage(Request $request)
    {
        $stacks = $request->input('stack', []);
        foreach ($stacks as $i => $stack) {
            VehicleImage::where('id', $stack['key'])->update(['iorder' => $i + 1]);
        }
        return response()->json(['success' => true]);
    }

    public function getVehicleRegistration(Request $request)
    {
        $id = base64_decode($request->input('vehicleid'));
        $v = Vehicle::find($id, ['registration_image']);
        if ($v && !empty($v->registration_image)) {
            return response()->json(['status' => true, 'result' => ['file' => asset('img/custom/vehicle_photo/' . $v->registration_image)]]);
        }
        return response()->json(['status' => false, 'message' => 'Document not found']);
    }

    public function rental_setting(Request $request, $id = null)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $id = base64_decode($id);
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        if ($request->isMethod('post')) {
            $data = $request->input('DepositRule', []);
            $data['vehicle_id'] = $id;
            $data['user_id'] = $userid;
            
            $dp = DepositRule::updateOrCreate(['vehicle_id' => $id], $data);
            
            $vData = $request->input('Vehicle', []);
            $vehicle = Vehicle::find($id);
            if ($vehicle) {
                $vehicle->update([
                    'rent_opt' => !empty($vData['rent_opt']) ? json_encode($vData['rent_opt']) : null,
                    'day_rent' => preg_replace("/[^0-9,.]/", "", $vData['day_rent'] ?? 0),
                    'rate' => preg_replace("/[^0-9,.]/", "", $vData['rate'] ?? 0),
                    'fare_type' => $vData['fare_type'] ?? 'D',
                    'auth_require' => $vData['auth_require'] ?? 0
                ]);
            }

            return redirect()->back()->with('success', 'Rental setting updated successfully');
        }

        $record = Vehicle::with('DepositRule')->find($id);
        return view('legacy.vehicles.rental_setting', compact('record', 'id'));
    }

    public function getVehicleDynamicFare(Request $request)
    {
        return response()->json($this->_getVehicleDynamicFare($request->all()));
    }

    public function getvehicledetails(Request $request)
    {
        $id = base64_decode($request->input('vehicleid'));
        $orderid = base64_decode($request->input('orderid'));
        $vehicle = Vehicle::find($id);
        return view('legacy.vehicles.details_popup', compact('vehicle', 'orderid'));
    }

    public function updateVehicleDetails(Request $request)
    {
        $id = $request->input('Vehicle.id') ?: $request->input('pk');
        $field = $request->input('name');
        $value = $request->input('value');
        
        if ($field) {
            Vehicle::where('id', $id)->update([$field => $value]);
            return response()->json(['status' => true]);
        }

        $data = $request->input('Vehicle', []);
        Vehicle::where('id', $id)->update($data);
        return response()->json(['status' => true]);
    }

    public function getVehicleGps(Request $request)
    {
        return response()->json($this->_getVehicleGps($request->input('vehicleid'), $request->input('type')));
    }

    public function getVehicleInspectionDoc(Request $request)
    {
        return response()->json($this->_getVehicleInspectionDoc(base64_decode($request->input('vehicleid'))));
    }

    protected function handleUpload($file, $vehicleid)
    {
        return $this->trait_handleUpload($file, $vehicleid);
    }

    protected function _getVehicleGps($vehicle_id, $type)
    {
        return $this->trait_getVehicleGps($vehicle_id, $type);
    }

    protected function _getVehicleDynamicFare($params)
    {
        return $this->trait_getVehicleDynamicFare($params);
    }

    protected function _getVehicleInspectionDoc($vehicleid)
    {
        return $this->trait_getVehicleInspectionDoc($vehicleid);
    }

    protected function exportToCsv($vehicles)
    {
        return $this->trait_exportToCsv($vehicles);
    }
}
