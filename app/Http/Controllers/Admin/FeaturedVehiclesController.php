<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleLocationTrait;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleVariation;
use App\Services\Legacy\Colors;
use App\Services\Legacy\DynamicFare;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

                $this->saveVariationVehicles($vehicleData, $vehicleIdSaved, $variationsData);
                $this->saveVehicleLocation($vehicleLocationData, $vehicleIdSaved);

                $msg = $isNew ? 'Vehicle data saved successfully' : 'Vehicle data updated successfully';
                session()->flash('success', $msg);
            });

            return redirect()->route('admin/vehicles/index');
        }

        $colors = (new Colors())->getColors();
        $vehicle = null;

        if (!empty($vehicleId)) {
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
                ->where('id', $vehicleId)
                ->where('is_featured', 1)
                ->first();

            if (!$vehicle) {
                return redirect()->route('admin.vehicles.index')->with('error', 'Sorry, something went wrong. Please try again later');
            }

            $vehicle->rent_opt = json_decode($vehicle->rent_opt, true);
            $vehicle->accudata = json_decode($vehicle->accudata, true);

            $vehicleVariants = VehicleVariation::where('vehicle_id', $vehicle->id)
                ->with('variant:id,msrp,premium_msrp,vin_no,stock_no,config')
                ->get()
                ->toArray();

            $vehicle->vehicle_variation = $vehicleVariants;

            if (!empty($vehicleObj->color)) {
                $colors[$vehicle->color] = $vehicle->color;
            }
            if (!empty($vehicleObj->interior_color)) {
                $colors[$vehicle->interior_color] = $vehicle->interior_color;
            }
        }

        return view('admin.featured_vehicles.add', compact('listTitle', 'titleForLayout', 'colors', 'vehicle'));
    }

    /**
     * AJAX: render attribute popup (step 1).
     */
    public function loadAttributePopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return view('admin.featured_vehicles._attributes');
    }

    /**
     * AJAX: render attribute step 2 popup with color dropdowns.
     */
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

    /**
     * AJAX: generate attribute combinations, render variation list.
     */
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

        $customAttributes = $this->generateCombinations($attributes);
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

    /**
     * JSON: check if stock number already exists.
     */
    public function checkStockDuplicate(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized', 'result' => []]);
        }

        $stock_no = trim($request->input('stock_no', ''));
        $return = ['status' => 'error', 'message' => 'Invalid Json', 'result' => []];

        if (!empty($stock_no)) {
            $exists = DB::table('vehicles')
                ->where('stock_no', 'LIKE', $stock_no . '%')
                ->count();

            if ($exists) {
                return response()->json(['status' => 'error', 'message' => 'record found', 'result' => []]);
            }

            $return = ['status' => 'success', 'message' => 'record not found', 'result' => []];
        }

        return response()->json($return);
    }

    /**
     * AJAX: load existing child vehicles for adding new variants.
     */
    public function loadNewVariant(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!$request->ajax() || empty($request->input('parentid'))) {
            abort(404);
        }

        $parentid = $request->input('parentid');

        $vehicleObj = DB::table('vehicles')
            ->where('id', $parentid)
            ->where('is_featured', 1)
            ->select('id', 'user_id', 'stock_no', 'config')
            ->first();

        if (!$vehicleObj) {
            abort(404);
        }

        $vehicleObj = (array) $vehicleObj;

        $existsVariants = DB::table('vehicle_variations')
            ->where('vehicle_id', $vehicleObj['id'])
            ->pluck('variant_id')
            ->toArray();

        $childs = DB::table('vehicles')
            ->where('user_id', $vehicleObj['user_id'])
            ->where('is_featured', 0)
            ->where('stock_no', 'LIKE', $vehicleObj['stock_no'] . '-%')
            ->get()
            ->map(fn($row) => ['Vehicle' => (array) $row])
            ->toArray();

        return view('admin.featured_vehicles._add_new_variant', compact(
            'vehicleObj',
            'childs',
            'existsVariants'
        ));
    }

    /**
     * AJAX: step 2 of adding existing variants.
     */
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

    /**
     * AJAX: step 3 of adding existing variants.
     */
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

    /**
     * JSON: delete a vehicle variant.
     */
    public function deleteVariant(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized', 'result' => []]);
        }

        $return = ['status' => 'error', 'message' => 'Invalid Json', 'result' => []];

        if ($request->ajax() && !empty($request->input('variantid'))) {
            $variantid = $request->input('variantid');
            DB::table('vehicles')->where('id', $variantid)->delete();
            DB::table('vehicle_variations')->where('variant_id', $variantid)->delete();
            $return = ['status' => 'success', 'message' => 'Variant deleted successfully', 'result' => []];
        }

        return response()->json($return);
    }

    /**
     * Save variation vehicles for a featured parent vehicle.
     */
    private function saveVariationVehicles(array $parentValues, int $parentId, array $variations = []): void
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
                DB::table('vehicles')->where('id', $variantExistingId)->update([
                    'trim' => $dataValues['trim'] ?? null,
                    'color' => $dataValues['color'] ?? null,
                    'msrp' => $dataValues['msrp'],
                    'premium_msrp' => $dataValues['premium_msrp'],
                    'config' => $dataValues['config'],
                ]);
                DB::table('vehicle_variations')
                    ->where('vehicle_id', $parentId)
                    ->where('variant_id', $variantExistingId)
                    ->delete();
                DB::table('vehicle_variations')->insert([
                    'vehicle_id' => $parentId,
                    'variant_id' => $variantExistingId,
                ]);
                continue;
            }

            $vehicleid = DB::table('vehicles')->insertGetId($dataValues);
            if ($vehicleid < 999) {
                $uniqueNo = '1' . sprintf('%04d', $vehicleid);
            } else {
                $uniqueNo = $vehicleid;
            }
            DB::table('vehicles')->where('id', $vehicleid)->update(['vehicle_unique_id' => $uniqueNo]);
            DB::table('vehicle_variations')->insert([
                'vehicle_id' => $parentId,
                'variant_id' => $vehicleid,
            ]);

            if (($dataValues['fare_type'] ?? '') === 'D') {
                $fareData = $dataValues;
                $fareData['id'] = $vehicleid;
                $this->calculateDynamicFareLegacy($fareData);
            }
        }
    }

    /**
     * Recursive combination generator for attribute arrays.
     */
    private function generateCombinations(array $arrays, array $prefix = []): array
    {
        $result = [];
        $arrayKeys = array_keys($arrays);
        $array = array_shift($arrays);

        foreach ($array as $value) {
            $newPrefix = $prefix;
            $newPrefix[$arrayKeys[0]] = trim($value);
            if (count($arrays) > 0) {
                $result = array_merge($result, $this->generateCombinations($arrays, $newPrefix));
            } else {
                $result[] = $newPrefix;
            }
        }

        return $result;
    }

    /**
     * Placeholder for DynamicFare calculation until that model is migrated.
     * TODO: Replace with proper DynamicFare service when available.
     */
    private function calculateDynamicFareLegacy(array $data): void
    {
        try {
            DB::statement(
                "CALL calculateDynamicFare(?, ?, ?, ?, ?, 1)",
                [
                    $data['id'] ?? 0,
                    $data['user_id'] ?? 0,
                    $data['msrp'] ?? 0,
                    $data['fare_type'] ?? 'D',
                    $data['vehicleCostInclRecon'] ?? 0,
                ]
            );
        } catch (\Exception $e) {
            // Stored procedure may not exist yet; log and continue
            \Illuminate\Support\Facades\Log::warning('calculateDynamicFare failed: ' . $e->getMessage());
        }
    }
}
