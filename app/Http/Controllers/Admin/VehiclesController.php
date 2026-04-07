<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\VehiclesController as LegacyVehiclesController;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleImage;
use App\Models\Legacy\VehicleLocation;
use App\Models\Legacy\VehicleSetting;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\User;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Legacy\CsSetting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VehiclesController extends LegacyVehiclesController
{
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $adminUser = session('SESSION_ADMIN');
        
        $query = Vehicle::with('User');

        if (!$adminUser['administrator']) {
            return redirect('/admin/linked_vehicles/index')->with('error', "Sorry, you are not authorized for this action.");
        }

        $keyword = $request->input('keyword', '');
        $showType = $request->input('show', '');
        $user_id = $request->input('user_id', '');
        $fieldname = $request->input('searchin', 'All');

        if (!empty($keyword)) {
            if ($fieldname == 'All') {
                $query->where(function($q) use ($keyword) {
                    $q->where('vehicle_name', 'LIKE', "%$keyword%")
                      ->orWhere('vin_no', 'LIKE', "%$keyword%")
                      ->orWhere('vehicle_unique_id', 'LIKE', "%$keyword%");
                });
            } else {
                $query->where($fieldname, 'LIKE', "%$keyword%");
            }
        }

        if ($showType !== '') {
            if ($showType == 10) $query->where('waitlist', 1);
            else $query->where('status', $showType);
        }

        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }

        if ($request->input('export') == 'Export') {
            $vehicles = $query->get();
            return $this->exportToCsv($vehicles);
        }

        $limit = $request->input('limit', session('Vehicles_admin_limit', $this->records_per_page ?? 20));
        session(['Vehicles_admin_limit' => $limit]);

        $vehicleDetails = $query->orderBy('id', 'DESC')->paginate($limit)->withQueryString();

        return view('admin.vehicles.index', compact('vehicleDetails', 'keyword', 'showType', 'user_id', 'fieldname', 'limit'));
    }

    public function admin_add(Request $request, $vehicle_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $this->layout = 'admin';
        $vehicle_id = $vehicle_id ? base64_decode($vehicle_id) : null;
        $adminUser = session('SESSION_ADMIN');

        if ($request->isMethod('post')) {
            $data = $request->input('Vehicle', []);
            
            // Handle dates
            $dateFields = ['insurance_policy_exp_date', 'inspection_exp_date', 'state_insp_exp_date', 'reg_name_exp_date', 'reg_name_date'];
            $timezone = $adminUser['timezone'];
            foreach ($dateFields as $field) {
                if (!empty($data[$field])) {
                    $data[$field] = Carbon::parse($data[$field], $timezone)->setTimezone('UTC')->format('Y-m-d');
                }
            }
            if (!empty($data['availability_date'])) {
                $data['availability_date'] = Carbon::parse($data['availability_date'], $timezone)->format('Y-m-d');
            }

            // Generate vehicle name
            $vehicle_name = (!empty($data['year']) ? substr($data['year'], -2) . '-' : '') . 
                           (!empty($data['make']) ? str_replace(' ', '_', $data['make']) . '-' : '') . 
                           (!empty($data['model']) ? str_replace(' ', '_', $data['model']) : '') . 
                           (!empty($data['vin_no']) ? '-' . substr($data['vin_no'], -6) : '');
            $data['vehicle_name'] = $vehicle_name;
            $data['rate'] = preg_replace("/[^0-9,.]/", "", $data['rate'] ?? 0);
            $data['vin_no'] = strtoupper($data['vin_no'] ?? '');

            $vehicle = Vehicle::updateOrCreate(['id' => $vehicle_id], $data);
            
            if (empty($vehicle_id)) {
                $uniqueNo = $vehicle->id < 999 ? '1' . sprintf('%04d', $vehicle->id) : $vehicle->id;
                $vehicle->update(['vehicle_unique_id' => $uniqueNo]);
            }

            // File uploads
            if ($request->hasFile('registration_image')) {
                $res = $this->handleUpload($request->file('registration_image'), $vehicle->id);
                if (isset($res['success'])) $vehicle->update(['registration_image' => $res['filename'] ?? '']);
            }
            if ($request->hasFile('inspection_image')) {
                $res = $this->handleUpload($request->file('inspection_image'), $vehicle->id);
                if (isset($res['success'])) $vehicle->update(['inspection_image' => $res['filename'] ?? '']);
            }
            if ($request->hasFile('insurance_image')) {
                $res = $this->handleUpload($request->file('insurance_image'), $vehicle->id);
                if (isset($res['success'])) $vehicle->update(['insurance_image' => $res['filename'] ?? '']);
            }

            // Save location
            if ($request->has('VehicleLocation')) {
                $this->saveVehicleLocation($request->input('VehicleLocation'), $vehicle->id);
            }

            return redirect('/admin/vehicles/index')->with('success', 'Vehicle data saved successfully');
        }

        $record = $vehicle_id ? Vehicle::with(['VehicleImage', 'VehicleLocation', 'CsSetting', 'User'])->find($vehicle_id) : null;
        
        return view('admin.vehicles.add', compact('record', 'vehicle_id'));
    }

    public function admin_ownerautocomplete(Request $request) {
        $searchTerm = $request->query('term');
        $user_id = $request->query('user_id');

        if ($user_id) {
            $user = User::where('id', $user_id)->first();
            return response()->json([
                'id' => $user->id,
                'tag' => $user->first_name . ' - ' . $user->contact_number
            ]);
        }

        $query = User::where('status', 1);
        if (!empty($searchTerm)) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('contact_number', 'LIKE', "%$searchTerm%")
                  ->orWhere('first_name', 'LIKE', "%$searchTerm%")
                  ->orWhere('email', 'LIKE', "%$searchTerm%")
                  ->orWhere('last_name', 'LIKE', "%$searchTerm%");
            });
        }

        $users = $query->limit(10)->orderBy('first_name', 'ASC')->get()->map(function($u) {
            return [
                'id' => $u->id,
                'tag' => $u->first_name . ' - ' . $u->contact_number
            ];
        });

        return response()->json($users);
    }

    public function admin_multiplAction(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        return $this->multiplAction($request);
    }

    public function admin_lastlocation($vehicle_id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        return $this->lastlocation($vehicle_id);
    }

    public function admin_saveImage(Request $request)
    {
        return $this->saveImage($request);
    }

    public function admin_deleteImage(Request $request)
    {
        return $this->deleteImage($request);
    }

    public function admin_checkVinDetails(Request $request)
    {
        return $this->checkVinDetails($request);
    }

    public function admin_loadVehicleStatus(Request $request)
    {
        return $this->loadVehicleStatus($request);
    }

    public function admin_changeVehicleStatus(Request $request)
    {
        return $this->changeVehicleStatus($request);
    }

    public function admin_loadSingleRow(Request $request)
    {
        return $this->load_single_row($request);
    }

    public function admin_reorderImage(Request $request)
    {
        return $this->reorderImage($request);
    }

    public function admin_getVehicleRegistration(Request $request)
    {
        return $this->getVehicleRegistration($request);
    }

    public function admin_rental_setting(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        return $this->rental_setting($request, $id);
    }

    public function admin_getVehicleDynamicFare(Request $request)
    {
        return $this->getVehicleDynamicFare($request);
    }

    public function admin_getvehicledetails(Request $request)
    {
        return $this->getvehicledetails($request);
    }

    public function admin_updateVehicleDetails(Request $request)
    {
        return $this->updateVehicleDetails($request);
    }

    public function admin_getVehicleGps(Request $request)
    {
        return $this->getVehicleGps($request);
    }

    public function admin_getVehicleInspectionDoc(Request $request)
    {
        return $this->getVehicleInspectionDoc($request);
    }

    public function admin_gps_setting(Request $request, $vehicleid = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        $vehicleid = base64_decode($vehicleid);
        $record = Vehicle::with('CsSetting')->find($vehicleid);
        return view('admin.vehicles.gps_setting', compact('record', 'vehicleid'));
    }

    public function admin_save_gpssetting(Request $request)
    {
        $data = $request->input('CsSetting', []);
        $vehicleid = $data['vehicle_id'];
        
        CsSetting::updateOrCreate(['user_id' => $data['user_id']], $data);
        
        return redirect('/admin/vehicles/gps_setting/' . base64_encode($vehicleid))->with('success', 'GPS setting saved successfully');
    }

    public function admin_delete_gpssetting(Request $request)
    {
        $id = $request->input('id');
        CsSetting::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function admin_changePasstimeVehicleStatus(Request $request)
    {
        return $this->changeVehicleStatus($request);
    }

    public function admin_duplicate(Request $request, $vehicleid = '')
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        
        $vehicleid = base64_decode($vehicleid);
        if ($request->isMethod('post')) {
            try {
                return DB::transaction(function() use ($request, $vehicleid) {
                    $original = Vehicle::with(['VehicleImage', 'VehicleLocation', 'VehicleSetting', 'DepositRule'])->findOrFail($vehicleid);
                    
                    $newVehicle = $original->replicate();
                    $newVehicle->vin_no = strtoupper(preg_replace("/[^0-9A-Z]/", "", $request->input('Vehicle.vin_no')));
                    $newVehicle->user_id = $request->input('Vehicle.user_id') ?: $original->user_id;
                    $newVehicle->booked = 0;
                    $newVehicle->trash = 0;
                    $newVehicle->status = 1;
                    $newVehicle->save();

                    // Generate new vehicle name and unique ID
                    $vehicle_name = (!empty($newVehicle->year) ? substr($newVehicle->year, -2) . '-' : '') . 
                                   (!empty($newVehicle->make) ? str_replace(' ', '_', $newVehicle->make) . '-' : '') . 
                                   (!empty($newVehicle->model) ? str_replace(' ', '_', $newVehicle->model) : '') . 
                                   (!empty($newVehicle->vin_no) ? '-' . substr($newVehicle->vin_no, -6) : '');
                    
                    $uniqueNo = $newVehicle->id < 999 ? '1' . sprintf('%04d', $newVehicle->id) : $newVehicle->id;
                    $newVehicle->update(['vehicle_name' => $vehicle_name, 'vehicle_unique_id' => $uniqueNo]);

                    // Duplicate images
                    foreach ($original->VehicleImage as $img) {
                        $newImg = $img->replicate();
                        $newImg->vehicle_id = $newVehicle->id;
                        $newImg->save();
                    }

                    // Duplicate locations
                    foreach ($original->VehicleLocation as $loc) {
                        $newLoc = $loc->replicate();
                        $newLoc->vehicle_id = $newVehicle->id;
                        $newLoc->save();
                    }

                    // Duplicate settings
                    if ($original->VehicleSetting) {
                        $newSetting = $original->VehicleSetting->replicate();
                        $newSetting->vehicle_id = $newVehicle->id;
                        $newSetting->save();
                    }

                    // Duplicate deposit rules
                    if ($original->DepositRule) {
                        $newRule = $original->DepositRule->replicate();
                        $newRule->vehicle_id = $newVehicle->id;
                        $newRule->user_id = $newVehicle->user_id;
                        $newRule->save();
                    }

                    Log::info("Admin duplicate vehicle $vehicleid to " . $newVehicle->id);
                    return redirect('/admin/vehicles/index')->with('success', 'Vehicle duplicated successfully');
                });
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        $dealerid = Vehicle::where('id', $vehicleid)->value('user_id');
        return view('admin.vehicles.duplicate', compact('vehicleid', 'dealerid'));
    }
}
