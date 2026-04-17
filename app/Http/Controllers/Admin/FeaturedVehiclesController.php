<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleLocationTrait;
use App\Services\Legacy\Colors;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeaturedVehiclesController extends LegacyAppController
{
    use VehicleLocationTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * Add / Edit featured vehicle (admin_add).
     */
    public function add(Request $request, $vehicle_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $vehicle_id = $vehicle_id ? base64_decode($vehicle_id) : null;
        $listTitle = !empty($vehicle_id) ? 'Edit Featured Vehicle' : 'Add Featured Vehicle';
        $colors = null;

        if ($request->isMethod('post')) {
            $data = $request->input('Vehicle', []);
            unset($data['last_mile']);

            $data['cab_type'] = !empty($data['cab_type']) ? $data['cab_type'] : 'Regular Sedan';
            $data['insurance_policy_exp_date'] = !empty($data['insurance_policy_exp_date'])
                ? Carbon::createFromFormat('m/d/Y', $data['insurance_policy_exp_date'])->format('Y-m-d')
                : '';
            $data['inspection_exp_date'] = !empty($data['inspection_exp_date'])
                ? Carbon::createFromFormat('m/d/Y', $data['inspection_exp_date'])->format('Y-m-d')
                : '';
            $data['state_insp_exp_date'] = !empty($data['state_insp_exp_date'])
                ? Carbon::createFromFormat('m/d/Y', $data['state_insp_exp_date'])->format('Y-m-d')
                : '';
            $data['reg_name_exp_date'] = !empty($data['reg_name_exp_date'])
                ? Carbon::createFromFormat('m/d/Y', $data['reg_name_exp_date'])->format('Y-m-d')
                : '';
            $data['reg_name_date'] = !empty($data['reg_name_date'])
                ? Carbon::createFromFormat('m/d/Y', $data['reg_name_date'])->format('Y-m-d')
                : '';
            $data['availability_date'] = !empty($data['availability_date'])
                ? date('Y-m-d', strtotime($data['availability_date']))
                : null;

            $vehicle_name = (!empty($data['year']) ? substr($data['year'], -2) . '-' : '')
                . (!empty($data['make']) ? str_replace(' ', '_', $data['make']) . '-' : '')
                . (!empty($data['model']) ? str_replace(' ', '_', $data['model']) : '')
                . (!empty($data['vin_no']) ? '-' . substr($data['vin_no'], -6) : '');

            $data['vehicle_name'] = $vehicle_name;
            $data['rate'] = preg_replace('/[^0-9,.]/', '', $data['rate'] ?? '');
            $data['status'] = 1;
            $data['rent_opt'] = '';

            if (($data['fare_type'] ?? '') === 'D') {
                $data['day_rent'] = 0;
            }

            $data['vehicleCostInclRecon'] = (float) ($data['vehicleCostInclRecon'] ?? 0);
            $data['kbbnadaWholesaleBook'] = (float) ($data['kbbnadaWholesaleBook'] ?? 0);
            $data['doors'] = (int) ($data['doors'] ?? 0);
            $data['allowed_miles'] = (float) ($data['allowed_miles'] ?? 0);
            $data['rate'] = (float) ($data['rate'] ?? 0);
            $data['day_rent'] = (float) ($data['day_rent'] ?? 0);
            $data['is_featured'] = 1;
            $data['config'] = $data['attributes'] ?? null;
            $data['vin_no'] = strtoupper($data['vin_no'] ?? '');

            $variations = $data['varitaions'] ?? [];
            unset($data['accudata'], $data['varitaions']);

            $vehicleId = $data['id'] ?? null;
            unset($data['id']);

            if (!empty($vehicleId)) {
                DB::table('vehicles')->where('id', $vehicleId)->update($data);
            } else {
                $vehicleId = DB::table('vehicles')->insertGetId($data);
                if ($vehicleId < 999) {
                    $uniqueNo = '1' . sprintf('%04d', $vehicleId);
                } else {
                    $uniqueNo = $vehicleId;
                }
                DB::table('vehicles')->where('id', $vehicleId)->update(['vehicle_unique_id' => $uniqueNo]);
            }

            if (($data['fare_type'] ?? '') === 'D') {
                // TODO: port DynamicFare::calculateDynamicFare() when that model is migrated
                $fareData = [
                    'id' => $vehicleId,
                    'user_id' => $data['user_id'] ?? null,
                    'msrp' => $data['msrp'] ?? 0,
                    'fare_type' => $data['fare_type'],
                    'vehicleCostInclRecon' => $data['vehicleCostInclRecon'] ?? 0,
                ];
                $this->calculateDynamicFareLegacy($fareData);
            }

            $this->saveVariationVehicles($data, $vehicleId, $variations);

            $locationData = $request->input('VehicleLocation', []);
            $this->saveVehicleLocation($locationData, $vehicleId);

            return redirect('/admin/vehicles/index')
                ->with('success', empty($request->input('Vehicle.id'))
                    ? 'Vehicle data saved successfully'
                    : 'Vehicle data updated successfully');
        }

        if (!empty($vehicle_id)) {
            $colors = (new Colors())->getColors();

            $vehicleObj = DB::table('vehicles as Vehicle')
                ->leftJoin('cs_settings as CsSetting', 'CsSetting.user_id', '=', 'Vehicle.user_id')
                ->leftJoin('users as User', 'User.id', '=', 'Vehicle.user_id')
                ->select(
                    'Vehicle.*',
                    'CsSetting.passtime',
                    'CsSetting.gps_provider',
                    'User.distance_unit'
                )
                ->where('Vehicle.id', $vehicle_id)
                ->where('Vehicle.is_featured', 1)
                ->first();

            if (empty($vehicleObj)) {
                return redirect('/admin/vehicles/index')
                    ->with('error', 'Sorry, something went wrong. Please try again later');
            }

            $vehicleObj = (array) $vehicleObj;
            $vehicleObj['rent_opt'] = json_decode($vehicleObj['rent_opt'] ?? '', true);
            $vehicleObj['accudata'] = json_decode($vehicleObj['accudata'] ?? '', true);

            $vehicleImages = DB::table('vehicle_images')
                ->where('vehicle_id', $vehicle_id)
                ->select('id', 'filename', 'iorder', 'remote')
                ->orderBy('iorder', 'ASC')
                ->get()
                ->toArray();

            $vehicleLocations = DB::table('vehicle_locations')
                ->where('vehicle_id', $vehicle_id)
                ->select('id', 'lat', 'lng', 'address')
                ->orderBy('id', 'ASC')
                ->get()
                ->map(fn($loc) => (array) $loc)
                ->toArray();

            $vehicleVariants = DB::table('vehicle_variations as VehicleVariation')
                ->leftJoin('vehicles as Variant', 'Variant.id', '=', 'VehicleVariation.variant_id')
                ->where('VehicleVariation.vehicle_id', $vehicleObj['id'])
                ->select(
                    'VehicleVariation.*',
                    'Variant.msrp as variant_msrp',
                    'Variant.id as variant_id_ref',
                    'Variant.premium_msrp as variant_premium_msrp',
                    'Variant.vin_no as variant_vin_no',
                    'Variant.stock_no as variant_stock_no',
                    'Variant.config as variant_config'
                )
                ->get()
                ->map(function ($row) {
                    return [
                        'VehicleVariation' => (array) $row,
                        'Variant' => [
                            'id' => $row->variant_id_ref,
                            'msrp' => $row->variant_msrp,
                            'premium_msrp' => $row->variant_premium_msrp,
                            'vin_no' => $row->variant_vin_no,
                            'stock_no' => $row->variant_stock_no,
                            'config' => $row->variant_config,
                        ],
                    ];
                })
                ->toArray();

            if (!empty($vehicleObj['color'])) {
                $colors[$vehicleObj['color']] = $vehicleObj['color'];
            }
            if (!empty($vehicleObj['interior_color'])) {
                $colors[$vehicleObj['interior_color']] = $vehicleObj['interior_color'];
            }

            $vehicle = $vehicleObj;
            $vehicle['VehicleVariation'] = $vehicleVariants;

            return view('admin.featured_vehicles.add', [
                'listTitle' => $listTitle,
                'title_for_layout' => 'Featured Vehicle',
                'colors' => $colors,
                'vehicle' => $vehicle,
                'vehicleImages' => $vehicleImages,
                'vehicleLocations' => $vehicleLocations,
            ]);
        }

        return view('admin.featured_vehicles.add', [
            'listTitle' => $listTitle,
            'title_for_layout' => 'Featured Vehicle',
            'colors' => $colors,
            'vehicle' => null,
            'vehicleImages' => [],
            'vehicleLocations' => [],
        ]);
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
