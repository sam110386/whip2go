<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleVariation;
use App\Services\Legacy\Colors;
use App\Services\Legacy\DynamicFare;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleLocationTrait;
use Carbon\Carbon;

class FeaturedVehiclesController extends LegacyAppController
{
    use VehicleLocationTrait;

    public function add(Request $request, $vehicle_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $vehicle_id = $this->decodeId($vehicle_id);
        $listTitle = !empty($vehicle_id) ? 'Edit Featured Vehicle' : 'Add Featured Vehicle';
        $titleForLayout = 'Featured Vehicle';

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $validatedData = $request->validate([
                'Vehicle.vehicle_name' => 'bail|required|string',
                'Vehicle.vin_no' => 'bail|required|unique:vehicles,vin_no' . (!empty($request->input('Vehicle.id')) ? ',' . $request->input('Vehicle.id') : ''),
                'Vehicle.user_id' => 'bail|required|integer',
            ], [
                'Vehicle.vehicle_name.required' => 'Please enter the Vehicle Name.',
                'Vehicle.vin_no.required' => 'Please enter VIN number.',
                'Vehicle.vin_no.unique' => 'Entered VIN number already registered.',
                'Vehicle.user_id.required' => 'Please enter Vehicle owner Id.',
            ]);

            $input = $request->all();
            $vehicleData = array_merge($input['Vehicle'] ?? [], $validatedData['Vehicle'] ?? []);
            $vehicleLocationData = $input['VehicleLocation'] ?? [];
            $variationsData = $vehicleData['varitaions'] ?? [];
            unset($vehicleData['last_mile'], $vehicleData['accudata'], $vehicleData['varitaions']);
            $vehicleData['cab_type'] ??= 'Regular Sedan';
            $dateFields = [
                'insurance_policy_exp_date',
                'inspection_exp_date',
                'state_insp_exp_date',
                'reg_name_exp_date',
                'reg_name_date',
                'availability_date'
            ];

            foreach ($dateFields as $field) {
                $vehicleData[$field] = !empty($vehicleData[$field]) ? Carbon::parse($vehicleData[$field])->format('Y-m-d') : null;
            }

            $yearSuffix = !empty($vehicleData['year']) ? substr($vehicleData['year'], -2) . '-' : '';
            $makeClean = !empty($vehicleData['make']) ? str_replace(' ', '_', $vehicleData['make']) . '-' : '';
            $modelClean = !empty($vehicleData['model']) ? str_replace(' ', '_', $vehicleData['model']) : '';
            $vinSuffix = !empty($vehicleData['vin_no']) ? '-' . substr($vehicleData['vin_no'], -6) : '';
            $vehicleData['vehicle_name'] = "{$yearSuffix}{$makeClean}{$modelClean}{$vinSuffix}";
            $vehicleData['rate'] = (float) preg_replace("/[^0-9,.]/", "", $vehicleData['rate'] ?? 0);
            $vehicleData['status'] = 1;
            $vehicleData['rent_opt'] = "";

            if (($vehicleData['fare_type'] ?? '') === 'D') {
                $vehicleData['day_rent'] = 0;
            }

            $vehicleData['vehicleCostInclRecon'] = (float) ($vehicleData['vehicleCostInclRecon'] ?? 0);
            $vehicleData['kbbnadaWholesaleBook'] = (float) ($vehicleData['kbbnadaWholesaleBook'] ?? 0);
            $vehicleData['doors'] = (int) ($vehicleData['doors'] ?? 0);
            $vehicleData['allowed_miles'] = (float) ($vehicleData['allowed_miles'] ?? 0);
            $vehicleData['day_rent'] = (float) ($vehicleData['day_rent'] ?? 0);
            $vehicleData['is_featured'] = 1;
            $vehicleData['config'] = $vehicleData['attributes'] ?? null;
            $vehicleData['vin_no'] = isset($vehicleData['vin_no']) ? strtoupper($vehicleData['vin_no']) : null;

            DB::transaction(function () use (&$vehicleData, $vehicleLocationData, $variationsData) {
                $isNew = empty($vehicleData['id']);
                $vehicle = Vehicle::updateOrCreate(['id' => $vehicleData['id'] ?? null], $vehicleData);
                $vehicleIdSaved = $vehicle->id;

                if ($isNew) {
                    $uniqueNo = ($vehicleIdSaved < 999) ? '1' . sprintf('%04d', $vehicleIdSaved) : $vehicleIdSaved;
                    $vehicle->update(['vehicle_unique_id' => $uniqueNo]);
                }

                if (($vehicleData['fare_type'] ?? '') === 'D') {
                    $fareData = [
                        'id' => $vehicleIdSaved,
                        'user_id' => $vehicleData['user_id'] ?? null,
                        'msrp' => $vehicleData['msrp'] ?? 0,
                        'fare_type' => $vehicleData['fare_type'],
                        'vehicleCostInclRecon' => $vehicleData['vehicleCostInclRecon'] ?? 0
                    ];

                    DynamicFare::calculateDynamicFare($fareData, 1);
                }

                $this->_saveVariationVehicles($vehicleData, $vehicleIdSaved, $variationsData);
                $this->saveVehicleLocation($vehicleLocationData, $vehicleIdSaved);

                $msg = $isNew ? 'Vehicle data saved successfully' : 'Vehicle data updated successfully';
                session()->flash('success', $msg);
            });

            return redirect()->route('admin/vehicles/index');
        }

        $colors = (new Colors())->getColors();
        $vehicle = null;

        if (!empty($vehicle_id)) {
            $vehicle = Vehicle::with([
                'csSetting:user_id,passtime,gps_provider',
                'user:id,distance_unit',
                'images' => function ($query) {
                    $query->orderBy('iorder', 'asc');
                },
                'locations' => function ($query) {
                    $query->orderBy('id', 'asc');
                }
            ])
                ->where('id', $vehicle_id)
                ->where('is_featured', 1)
                ->first();

            if (!$vehicle) {
                return redirect()->route('admin.vehicles.index')->with('error', 'Sorry, something went wrong. Please try again later');
            }

            $vehicle->rent_opt = json_decode($vehicle->rent_opt, true);
            $vehicle->accudata = json_decode($vehicle->accudata, true);

            if (!empty($vehicleObj->color)) {
                $colors[$vehicle->color] = $vehicle->color;
            }

            if (!empty($vehicleObj->interior_color)) {
                $colors[$vehicle->interior_color] = $vehicle->interior_color;
            }
            
            $vehicleVariants = VehicleVariation::with('variant')
                ->where('vehicle_id', $vehicle_id)
                ->get();
        }

        return view('admin.featured_vehicles.add', compact('listTitle', 'titleForLayout', 'colors', 'vehicle', 'vehicleVariants'));
    }
    public function loadAttributePopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return view('admin.featured_vehicles._attributes');
    }
    public function loadAttributeStep2Popup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$request->ajax()) {
            abort(404);
        }

        $colors = (new Colors())->all();
        $attributes = $request->input('FeaturedVehicle.attribute', []);

        return view('admin.featured_vehicles._attribute_step2', compact('colors', 'attributes'));
    }
    public function loadAttributeStep3List(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$request->ajax()) {
            abort(404);
        }

        $rawAttributes = $request->input('FeaturedVehicle.attributes', []);
        $attributes = [];

        foreach ($rawAttributes as $key => $values) {
            if (is_array($values)) {
                $attributes[$key] = $values;
            } else {
                $attributes[$key] = preg_split('/\r\n/', $values);
            }
        }

        $customAttributes = $this->_generateCombinations($attributes);
        $stock_no = trim($request->input('stock_no', ''));
        $vin = str_pad(trim(strtoupper($request->input('vin', ''))), 16, 'X');
        $msrp = $request->input('msrp', 0);
        $premium_msrp = $request->input('premium_msrp', 0);

        return view('admin.featured_vehicles._variation_list', compact(
            'customAttributes',
            'attributes',
            'stock_no',
            'vin',
            'msrp',
            'premium_msrp'
        ));
    }
    public function checkStockDuplicate(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized', 'result' => []]);
        }

        $stock_no = trim($request->input('stock_no', ''));
        $return = ['status' => 'error', 'message' => 'Invalid Json', 'result' => []];

        if (!empty($stock_no)) {
            $exists = Vehicle::where('stock_no', 'LIKE', "{$stock_no}%")->count();

            if ($exists) {
                return response()->json(['status' => 'error', 'message' => 'record found', 'result' => []]);
            }

            $return = ['status' => 'success', 'message' => 'record not found', 'result' => []];
        }

        return response()->json($return);
    }
    public function loadNewVariant(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$request->ajax() || empty($request->input('parentid'))) {
            abort(404);
        }

        $parentid = $request->input('parentid');

        $vehicle = Vehicle::with([
            'variations' => function ($query) {
                $query->select('vehicle_id', 'variant_id')->orderBy('variant_id', 'ASC');
            }
        ])
            ->where('id', $parentid)
            ->where('is_featured', 1)
            ->select('id', 'user_id', 'stock_no', 'config')
            ->first();

        if (!$vehicle) {
            abort(404);
        }

        $existsVariants = $vehicle->variations->pluck('variant_id')->toArray();

        $childs = Vehicle::where('user_id', $vehicle->user_id)
            ->where('is_featured', 0)
            ->where('stock_no', 'LIKE', "{$vehicle->stock_no}-%")
            ->get();

        return view('admin.featured_vehicles._add_new_variant', compact(
            'vehicle',
            'childs',
            'existsVariants'
        ));
    }
    public function addExistingStep2(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$request->ajax()) {
            abort(404);
        }

        $variations = $request->input('Vehicle.variations', []);
        $attributes = json_decode($request->input('Vehicle.attributes', '{}'), true);

        return view('admin.featured_vehicles._add_new_variant_step2', compact('variations', 'attributes'));
    }
    public function addExistingStep3(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$request->ajax()) {
            abort(404);
        }

        $variations = $request->input('variations', []);
        $attributes = [];

        foreach ($variations as $variation) {
            $configs = $variation['config'] ?? [];
            foreach ($configs as $key => $config) {
                $attributes[$key][] = $config;
                $attributes[$key] = array_unique($attributes[$key]);
            }
        }

        return view('admin.featured_vehicles._add_new_variant_step3', compact('variations', 'attributes'));
    }
    public function deleteVariant(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $return = [
            'status' => 'error',
            'message' => 'Invalid Json',
            'result' => []
        ];

        if ($request->ajax() && !empty($request->input('variantid'))) {
            $variantid = $request->input('variantid');

            Vehicle::where('id', $variantid)->delete();
            VehicleVariation::where('variant_id', $variantid)->delete();

            $return = [
                'status' => 'success',
                'message' => 'Variant deleted successfully',
                'result' => []
            ];
        }

        return response()->json($return);
    }
    private function _saveVariationVehicles(array $parentValues, int $parentId, array $variations = []): void
    {
        foreach ($variations as $stockKey => $variation) {
            $config = json_decode($variation['config'] ?? '{}', true);
            $dataValues = $parentValues;

            if (empty($dataValues['availability_date'])) {
                unset($dataValues['availability_date']);
            }

            $configKeys = array_keys($config);
            $dataValues['vin_no'] = end($configKeys);
            $dataValues['stock_no'] = $stockKey;
            $dataValues['is_featured'] = 0;

            $variantExistingId = $variation['id'] ?? '';
            $dataValues['msrp'] = $variation['dprice'] ?? 0;
            $dataValues['premium_msrp'] = $variation['lprice'] ?? 0;

            $configValues = array_values($config);
            $dataValues['config'] = json_encode(end($configValues));
            $dataValues['visibility'] = 0;

            if (strpos(strtolower($dataValues['config']), 'color') !== false) {
                $colorValue = $dataValues['color'] ?? '';
                $decoded = json_decode($dataValues['config'], true);

                if (is_array($decoded)) {
                    foreach ($decoded as $k => $v) {
                        if (strpos(strtolower($k), 'color') !== false) {
                            $colorValue = $v;
                            break;
                        }
                    }
                }

                $dataValues['color'] = $colorValue;
            }

            if (strpos(strtolower($dataValues['config']), 'trim') !== false) {
                $trim = $dataValues['trim'] ?? '';
                $decoded = json_decode($dataValues['config'], true);

                if (is_array($decoded)) {
                    foreach ($decoded as $k => $v) {
                        if (strpos(strtolower($k), 'trim') !== false) {
                            $trim = $v;
                            break;
                        }
                    }
                }

                $dataValues['trim'] = $trim;
            }

            unset(
                $dataValues['id'],
                $dataValues['attributes'],
                $dataValues['varitaions'],
                $dataValues['accudata']
            );

            if (!empty($variantExistingId)) {

                Vehicle::where('id', $variantExistingId)->update([
                    'trim' => $dataValues['trim'] ?? null,
                    'color' => $dataValues['color'] ?? null,
                    'msrp' => $dataValues['msrp'],
                    'premium_msrp' => $dataValues['premium_msrp'],
                    'config' => $dataValues['config'],
                ]);

                VehicleVariation::where('vehicle_id', $parentId)
                    ->where('variant_id', $variantExistingId)
                    ->delete();

                VehicleVariation::create([
                    'vehicle_id' => $parentId,
                    'variant_id' => $variantExistingId,
                ]);

                continue;
            }

            $vehicle = Vehicle::create($dataValues);
            $vehicleid = $vehicle->id;
            $uniqueNo = $vehicleid;

            if ($vehicleid < 999) {
                $uniqueNo = '1' . sprintf('%04d', $vehicleid);
            }

            $vehicle->update(['vehicle_unique_id' => $uniqueNo]);

            VehicleVariation::create([
                'vehicle_id' => $parentId,
                'variant_id' => $vehicleid,
            ]);

            if (($dataValues['fare_type'] ?? '') === 'D') {
                $fareData = $dataValues;
                $fareData['id'] = $vehicleid;
                DynamicFare::calculateDynamicFare($fareData, 1);
            }
        }
    }
    private function _generateCombinations(array $arrays, array $prefix = []): array
    {
        $result = [];
        $arrayKeys = array_keys($arrays);
        $array = array_shift($arrays);

        foreach ($array as $value) {
            $newPrefix = $prefix;
            $newPrefix[$arrayKeys[0]] = trim($value);
            if (count($arrays) > 0) {
                $result = array_merge($result, $this->_generateCombinations($arrays, $newPrefix));
            } else {
                $result[] = $newPrefix;
            }
        }

        return $result;
    }
}
