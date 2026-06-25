<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\DriverBackgroundReport;
use App\Http\Controllers\Traits\VehicleDynamicFareMatrix;
use App\Http\Controllers\Traits\VehicleOffersTrait;
use App\Models\Legacy\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleOffersController extends LegacyAppController
{
    use VehicleOffersTrait, DriverBackgroundReport, VehicleDynamicFareMatrix;
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $adminUser = $this->getAdminUserid();
        $query = $this->offerQuery();

        if (!$adminUser['administrator']) {
            $query->where('vo.admin_id', $adminUser['parent_id']);
        }

        $options = [
            'vo.id' => 'Offer ID',
            'v.vehicle_name' => 'Vehicle Name',
            'v.vehicle_unique_id' => 'Vehicle ID',
            'u.first_name' => 'First Name',
            'u.last_name' => 'Last Name',
            'vo.driver_phone' => 'Phone',
            'u.email' => 'Email'
        ];

        $keyword = $request->input('Search.keyword');
        $fieldname = $request->input('Search.searchin');
        $show = $request->input('Search.show');
        $user_id = $request->input('Search.user_id');

        if ($keyword && $fieldname) {
            $query->where($fieldname, 'LIKE', "%{$keyword}%");
        }

        if ($show !== null && $show !== '') {
            $query->where('vo.status', $show);
        }

        if ($user_id) {
            $query->where('vo.user_id', $user_id);
        }

        $limit = $this->resolveLimit($request);
        $offers = $query
            ->orderByDesc('vo.id')
            ->paginate($limit)
            ->withQueryString();

        $getFinancing = function ($id) {
            $map = [0 => 'None', 1 => 'Rent', 2 => 'Rent To Own', 3 => 'Buy', 4 => 'Lease'];
            return $map[$id] ?? 'None';
        };

        $viewData = [
            'offers' => $offers,
            'options' => $options,
            'keyword' => $keyword,
            'fieldname' => $fieldname,
            'show' => $show,
            'user_id' => $user_id,
            'basePath' => $this->offerBasePath(),
            'limit' => $limit,
            'getFinancing' => $getFinancing,
        ];

        if ($request->ajax()) {
            return view('admin.vehicle_offers.elements.index', $viewData);
        }

        return view('admin.vehicle_offers.index', $viewData);
    }

    public function add(Request $request, $offer_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $id = $this->decodeId((string) $offer_id);
        $offer = $id ? DB::table('vehicle_offers')->where('id', $id)->first() : null;

        if ($request->isMethod('POST')) {
            $payload = (array) $request->input('VehicleOffer', []);
            $save = $this->filterOfferPayload($payload);

            // Derive user_id from driver_phone if provided
            if (!empty($payload['driver_phone'])) {
                $phone = substr(preg_replace("/[^0-9]/", "", $payload['driver_phone']), -10);
                $user = DB::table('users')->where('username', $phone)->first();
                if ($user) {
                    $save['user_id'] = $user->id;
                }
                $save['driver_phone'] = $phone;
            }

            // Derive dealer_id and admin_id
            if (!empty($payload['vehicle_id'])) {
                $vehicle = DB::table('vehicles')->where('id', $payload['vehicle_id'])->first();
                if ($vehicle) {
                    $save['dealer_id'] = $vehicle->user_id ?? 0;
                    $adminUser = $this->getAdminUserid();
                    $save['admin_id'] = $save['admin_id'] ?? ($adminUser['parent_id'] ?? 0);
                }
            }

            if ($id) {
                DB::table('vehicle_offers')->where('id', $id)->update($save);
            } else {
                $save['created'] = $save['created'] ?? now()->toDateTimeString();
                $id = (int) DB::table('vehicle_offers')->insertGetId($save);
            }

            // Notify Simulation (Matching legacy behavior)
            if (!empty($save['user_id'])) {
                Log::info("Pubnub: notifyForOffer for user " . $save['user_id']);
            }

            return redirect($this->offerBasePath() . '/index')
                ->with('success', 'Offer saved successfully');
        }

        if ($id && $offer) {
            $offer->rent_opt = !empty($offer->rent_opt) ? json_decode($offer->rent_opt, true) : [];
            $offer->initial_fee_opt = !empty($offer->initial_fee_opt) ? json_decode($offer->initial_fee_opt, true) : [];
            $offer->deposit_opt = !empty($offer->deposit_opt) ? json_decode($offer->deposit_opt, true) : [];
            $offer->duration_opt = !empty($offer->duration_opt) ? json_decode($offer->duration_opt, true) : [];
        }

        return view('admin.vehicle_offers.add', [
            'offer' => $offer,
            'basePath' => $this->offerBasePath(),
            'timezone' => $adminUser['timezone'] ?? 'UTC',
        ]);
    }

    public function userautocomplete(Request $request): JsonResponse
    {
        return response()->json($this->_userautocomplete($request->query()));
    }

    public function vehicleautocomplete(Request $request): JsonResponse
    {
        return response()->json($this->_vehicleautocomplete($request->query(), null, true));
    }

    public function cancel($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $offerId = $this->decodeId((string) $id);
        if ($offerId) {
            DB::table('vehicle_offers')->where('id', $offerId)->update(['status' => 2]);
        }

        return redirect($this->offerBasePath() . '/index')->with('success', 'Offer cancelled');
    }

    public function delete($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $offerId = $this->decodeId((string) $id);
        if ($offerId) {
            DB::table('vehicle_offers')->where('id', $offerId)->delete();
        }

        return redirect($this->offerBasePath() . '/index')->with('success', 'Offer deleted');
    }

    public function view($offer_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $id = $this->decodeId((string) $offer_id);
        if (!$id) {
            return redirect($this->offerBasePath() . '/index');
        }
        $offer = $this->offerQuery()->where('vo.id', $id)->first();
        if ($offer) {
            $offer->rent_opt = !empty($offer->rent_opt) ? json_decode($offer->rent_opt, true) : [];
            $offer->initial_fee_opt = !empty($offer->initial_fee_opt) ? json_decode($offer->initial_fee_opt, true) : [];
            $offer->deposit_opt = !empty($offer->deposit_opt) ? json_decode($offer->deposit_opt, true) : [];
            $offer->duration_opt = !empty($offer->duration_opt) ? json_decode($offer->duration_opt, true) : [];
        }

        return view('admin.vehicle_offers.view', [
            'offer' => $offer,
            'basePath' => $this->offerBasePath(),
            'timezone' => session('default_timezone', 'UTC')
        ]);
    }

    public function qualify(Request $request): JsonResponse
    {
        return response()->json($this->qualifyCheckr($request->input('VehicleOffer', [])));
    }

    public function qualifyIncome(Request $request): JsonResponse
    {
        return response()->json($this->_qualifyIncome($request->input('VehicleOffer', [])));
    }

    public function getVehicleDynamicFareMatrix(Request $request): JsonResponse
    {
        $data = $request->input('VehicleOffer', []);
        $data['driver_phone'] = substr(preg_replace("/[^0-9]/", "", $data['driver_phone'] ?? ''), -10);
        $user = User::where('username', $data['driver_phone'])->first();

        // Calculations for opt sums (matching legacy logic)
        if (!empty($data['deposit_opt'])) {
            $data['total_deposit_amt'] = ($data['deposit_amt'] ?? 0) + collect(array_values($data['deposit_opt']))->sum('amount');
            $data['deposit_opt'] = json_encode(array_values($data['deposit_opt']));
        }
        if (!empty($data['initial_fee_opt'])) {
            $data['total_initial_fee'] = ($data['initial_fee'] ?? 0) + collect(array_values($data['initial_fee_opt']))->sum('amount');
            $data['initial_fee_opt'] = json_encode(array_values($data['initial_fee_opt']));
        }
        if (!empty($data['duration_opt'])) {
            $data['duration_opt'] = json_encode(array_values($data['duration_opt']));
        }

        return response()->json($this->_getVehicleDynamicFareMatrix($data, $user));
    }

    public function duplicate($offerid)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $id = $this->decodeId((string) $offerid);
        if (!$id) {
            return redirect($this->offerBasePath() . '/index');
        }
        $offer = DB::table('vehicle_offers')->where('id', $id)->first();
        if (!$offer) {
            return redirect($this->offerBasePath() . '/index');
        }

        $newId = $this->_duplicate($offer);

        return redirect($this->offerBasePath() . '/add/' . base64_encode((string) $newId))
            ->with('success', 'Offer duplicated');
    }

    protected function offerBasePath(): string
    {
        return '/admin/vehicle_offers';
    }

    protected function offerQuery()
    {
        return DB::table('vehicle_offers as vo')
            ->leftJoin('users as u', 'u.id', '=', 'vo.user_id')
            ->leftJoin('users as d', 'd.id', '=', 'vo.dealer_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vo.vehicle_id')
            ->select([
                'vo.*',
                'd.first_name as owner_first_name',
                'd.last_name as owner_last_name',
                'u.first_name as renter_first_name',
                'u.last_name as renter_last_name',
                'v.vehicle_unique_id',
                'v.vehicle_name',
            ]);
    }
    protected function filterOfferPayload(array $payload): array
    {
        $allowed = [
            'user_id',
            'dealer_id',
            'admin_id',
            'vehicle_id',
            'status',
            'offer_price',
            'finance_type',
            'term',
            'down_payment',
            'apr',
            'monthly_payment',
            'note',
            'start_datetime',
            'end_datetime',
            'driver_phone',
            'totalcost',
            'goal',
            'downpayment',
            'target_days',
            'duration',
            'fare_type',
            'pto',
            'financing',
            'miles',
            'insurance',
            'emf',
            'program_fee',
            'total_insurance',
            'total_program_cost',
            'equityshare',
            'write_down_allocation',
            'finance_allocation',
            'maintenance_allocation',
            'depreciation_rate',
            'disposition_fee',
            'calculation',
            'day_rent',
            'rent_opt',
            'initial_fee_opt',
            'deposit_opt',
            'duration_opt',
            'total_initial_fee',
            'total_deposit_amt',
            'days'
        ];
        $out = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $val = $payload[$key];
                if (in_array($key, ['rent_opt', 'initial_fee_opt', 'deposit_opt', 'duration_opt']) && is_array($val)) {
                    $val = json_encode(array_values($val));
                }
                $out[$key] = $val;
            }
        }

        // Fix duration if custom
        if (($payload['duration'] ?? '') === 'custom' && !empty($payload['duration1'])) {
            $out['duration'] = $payload['duration1'];
        }

        // Timezone conversion for start_datetime
        if (!empty($out['start_datetime'])) {
            try {
                $tz = session('default_timezone', 'UTC');
                $out['start_datetime'] = Carbon::parse($out['start_datetime'], $tz)
                    ->setTimezone('UTC')
                    ->toDateTimeString();
            } catch (\Exception $e) {
                Log::error("Error parsing start_datetime: " . $e->getMessage());
            }
        }

        $out['modified'] = now()->toDateTimeString();

        return $out;
    }

    protected function resolveLimit(Request $request): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int) $request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['vehicle_offers_limit' => $lim]);
            }
        }
        $limit = (int) session('vehicle_offers_limit', 50);

        return $limit > 0 ? $limit : 50;
    }
}

