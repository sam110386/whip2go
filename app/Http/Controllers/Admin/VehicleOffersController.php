<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\DriverBackgroundReport;
use App\Http\Controllers\Traits\VehicleDynamicFareMatrix;
use App\Http\Controllers\Traits\VehicleOffersTrait;
use App\Models\Legacy\User;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleOffer;
use App\Services\Legacy\PubnubClient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleOffersController extends LegacyAppController
{
    use VehicleOffersTrait, DriverBackgroundReport, VehicleDynamicFareMatrix;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = "Manage Vehicle Offers";
        $sessionLimitKey = "vehicle_offers_limit";
        $adminUser = $this->getAdminUserid();
        $timezone = $adminUser['timezone'];

        $query = VehicleOffer::with([
            'owner:id,first_name,last_name',
            'vehicle:id,vehicle_unique_id,vehicle_name'
        ]);

        if (!$adminUser['administrator']) {
            $query->where('admin_id', $adminUser['parent_id']);
        }

        $searchin = $request->input('Search.searchin', $request->query('searchin', ''));
        $keyword = $request->input('Search.keyword', $request->query('keyword', ''));
        $showtype = $request->input('Search.showtype', $request->query('showtype', ''));
        $user_id = $request->input('Search.user_id', $request->query('user_id', ''));

        if (!empty($keyword)) {
            if ($searchin === 'All' || empty($searchin)) {
                $query->whereHas('vehicle', function ($q) use ($keyword) {
                    $q->where('vehicle_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('vehicle_unique_id', 'LIKE', "%{$keyword}%");
                });
            } else {
                $query->whereHas('vehicle', function ($q) use ($searchin, $keyword) {
                    $q->where($searchin, 'LIKE', "%{$keyword}%");
                });
            }
        }

        if (!empty($showtype)) {
            $query->where('status', $showtype);
        }

        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitKey => $limit]);
        } else {
            $limit = session($sessionLimitKey, $this->recordsPerPage);
        }

        $vehicleOffers = $query->orderBy('id', 'DESC')->paginate($limit);

        $options = [
            'vehicle_name' => 'Vehicle Name',
            'vehicle_unique_id' => 'Vehicle Number',
            'plate_number' => 'Plate Number'
        ];

        if ($request->ajax()) {
            return view('admin.vehicle_offers.elements.index', compact('vehicleOffers', 'options', 'searchin', 'keyword', 'showtype', 'user_id', 'timezone', 'limit'));
        }

        return view('admin.vehicle_offers.index', compact('title', 'vehicleOffers', 'options', 'searchin', 'keyword', 'showtype', 'user_id', 'timezone', 'limit'));
    }
    public function add(Request $request, $offer_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $adminUser = $this->getAdminUserid();
        $timezone = $adminUser['timezone'];
        $offer_id = $this->decodeId($offer_id);
        $title = $offer_id ? 'Edit' : 'Add';

        if ($request->isMethod('post') || $request->isMethod('put')) {

            if ($request->input('VehicleOffer.duration') === 'custom' && !$request->filled('VehicleOffer.duration1')) {
                return redirect()->back()->with('error', 'Sorry, please select the correct duration in days');
            }

            $offerData = $request->input('VehicleOffer');
            $cleanPhone = substr(preg_replace("/[^0-9]/", "", $offerData['driver_phone'] ?? ''), -10);
            $offerData['driver_phone'] = $cleanPhone;

            $userData = User::where('username', $cleanPhone)->first();
            $userId = $userData ? $userData->id : null;

            if (empty($offerData['id'])) {
                $offer = new VehicleOffer();
                $offer->admin_id = $adminUser['parent_id'];
                $offer->user_id = $userId;

                $vehicleInfo = Vehicle::select('user_id')->find($offerData['vehicle_id']);
                $offer->dealer_id = $vehicleInfo && $vehicleInfo->user_id ? $vehicleInfo->user_id : 0;
            } else {
                $offer = VehicleOffer::findOrFail($offerData['id']);

                if ($offer->status == 1 && in_array($offerData['status'], [0, 2])) {
                    return redirect()->back()->with('error', 'Sorry, selected offer already accepted by driver, you cant cancel now.');
                }

                if ($offer->status != 1 && $userId) {
                    $offer->user_id = $userId;
                }
            }

            if ($offerData['duration'] === 'custom') {
                $offerData['duration'] = $offerData['duration1'];
            }

            $offer->start_datetime = Carbon::parse($offerData['start_datetime'], $adminUser['timezone'])
                ->setTimezone(config('app.timezone', 'UTC'))
                ->toDateTimeString();
            $depositAmt = $offerData['deposit_amt'] ?? 0;
            $depositOpts = collect($offerData['deposit_opt'] ?? []);
            $depositOptSum = $depositOpts->sum('amount');
            $offer->deposit_amt = $depositAmt;
            $offer->total_deposit_amt = $depositAmt + $depositOptSum;
            $offer->deposit_opt = $depositOptSum > 0 ? json_encode($depositOpts->values()->toArray()) : "";
            $initialFee = $offerData['initial_fee'] ?? 0;
            $initialFeeOpts = collect($offerData['initial_fee_opt'] ?? []);
            $initialFeeSum = $initialFeeOpts->sum('amount');
            $offer->initial_fee = $initialFee;
            $offer->total_initial_fee = $initialFee + $initialFeeSum;
            $offer->initial_fee_opt = $initialFeeSum > 0 ? json_encode($initialFeeOpts->values()->toArray()) : "";
            $durationOpts = collect($offerData['duration_opt'] ?? []);
            $totalDurationSum = $durationOpts->sum('duration');
            $offer->duration_opt = $totalDurationSum > 0 ? json_encode($durationOpts->values()->toArray()) : "";
            $offer->vehicle_id = $offerData['vehicle_id'];
            $offer->status = $offerData['status'] ?? 0;
            $offer->duration = $offerData['duration'];
            $isNew = !$offer->exists;
            $offer->save();

            $flashMessage = $isNew ? 'Offer data saved successfully' : 'Offer data updated successfully';

            if ($offer->user_id) {
                $pubnub = new PubnubClient();
                $msg = $isNew
                    ? "Wow!! A new offer is created for you. Click here for more info"
                    : "Your offer is updated. Click here for more info";

                $pubnub->notifyForOffer(["user_id" => $offer->user_id, "msg" => $msg]);
            }

            return redirect('admin/vehicle_offers/index')->with('success', $flashMessage);
        }

        $offer = null;

        if (!empty($offer_id)) {
            $query = VehicleOffer::query()->where('id', $offer_id);

            if (!$adminUser['administrator']) {
                $query->where('admin_id', $adminUser['admin_id']);
            }

            $offer = $query->first();

            if (!$offer) {
                return redirect('admin/vehicle_offers/index')->with('error', 'Sorry, you are not authorized user for this action');
            }

            $offer->rent_opt = json_decode($offer->rent_opt ?? '', true) ?? [];
            $offer->initial_fee_opt = json_decode($offer->initial_fee_opt ?? '', true) ?? [];
            $offer->deposit_opt = json_decode($offer->deposit_opt ?? '', true) ?? [];
            $offer->duration_opt = json_decode($offer->duration_opt ?? '', true) ?? [];
        }

        return view('admin.vehicle_offers.add', compact('title', 'timezone', 'offer'));
    }
    public function userautocomplete(Request $request)
    {
        $users = $this->_userautocomplete($request->query());
        return response()->json($this->_userautocomplete($users));
    }
    public function vehicleautocomplete(Request $request)
    {
        $vehicles = $this->_vehicleautocomplete($request->query());
        return response()->json($vehicles);
    }
    public function cancel($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $offer_id = $this->decodeId($id);
        $vehicleOffer = VehicleOffer::findOrFail($offer_id);

        if ($vehicleOffer && $vehicleOffer->status != 1) {
            $vehicleOffer->status = 2;
            $vehicleOffer->save();
            return redirect('/admin/vehicle_offers/index')->with('success', 'Your request processed successfully.');
        }

        return redirect('/admin/vehicle_offers/index')->with('error', 'Sorry, selected offer already accepted by driver, you cant cancel now.');
    }
    public function delete($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $offer_id = $this->decodeId($id);
        $vehicleOffer = VehicleOffer::findOrFail($offer_id);

        if ($vehicleOffer && $vehicleOffer->status != 1) {
            $vehicleOffer->delete();
            return redirect('/admin/vehicle_offers/index')->with('success', 'Your request processed successfully.');
        }

        return redirect('/admin/vehicle_offers/index')->with('error', 'Sorry, selected offer already accepted by driver, you cant cancel now.');
    }
    public function view($offer_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'View';
        $offer_id = $this->decodeId($offer_id);
        $adminUser = $this->getAdminUserid();
        $timezone = $adminUser['timezone'];

        $query = VehicleOffer::with('vehicle:id,vehicle_name')
            ->where('id', $offer_id);

        if (!$adminUser['administrator']) {
            $query->where('admin_id', $adminUser['admin_id']);
        }

        $offer = $query->first();

        if (!$offer) {
            return redirect('/admin/vehicle_offers/index')->with('error', 'Sorry, you are not an authorized user for this action.');
        }

        $offer->rent_opt = json_decode($offer->rent_opt ?? '', true) ?: [];
        $offer->initial_fee_opt = json_decode($offer->initial_fee_opt ?? '', true) ?: [];
        $offer->deposit_opt = json_decode($offer->deposit_opt ?? '', true) ?: [];
        $offer->duration_opt = json_decode($offer->duration_opt ?? '', true) ?: [];

        return view('admin.vehicle_offers.view', compact('title', 'offer', 'timezone'));
    }
    public function qualify(Request $request)
    {
        $offer = $request->input('VehicleOffer', []);
        $return = $this->qualifyCheckr($offer);
        return response()->json($return);
    }
    public function qualifyIncome(Request $request)
    {
        $offer = $request->input('VehicleOffer', []);
        $return = $this->_qualifyIncome($offer);
        return response()->json($return);
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
            return redirect('/admin/vehicle_offers/index');
        }
        $offer = DB::table('vehicle_offers')->where('id', $id)->first();
        if (!$offer) {
            return redirect('/admin/vehicle_offers/index');
        }

        $newId = $this->_duplicate($offer);

        return redirect('/admin/vehicle_offers/add/' . base64_encode((string) $newId))
            ->with('success', 'Offer duplicated');
    }
}

