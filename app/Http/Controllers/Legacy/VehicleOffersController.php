<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleOffer;
use App\Models\Legacy\User;
use App\Models\Legacy\UserReport;
use App\Models\Legacy\AdminUserAssociation;
use App\Http\Controllers\Traits\VehicleOffersTrait;
use App\Http\Controllers\Traits\DriverBackgroundReport;
use App\Http\Controllers\Traits\VehicleDynamicFareMatrix;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VehicleOffersController extends LegacyAppController
{
    use VehicleOffersTrait {
        _userautocomplete as protected trait_userautocomplete;
        _vehicleautocomplete as protected trait_vehicleautocomplete;
        qualifyCheckr as protected trait_qualifyCheckr;
        _qualifyIncome as protected trait_qualifyIncome;
        _duplicate as protected trait_duplicate;
    }
    use DriverBackgroundReport, VehicleDynamicFareMatrix;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $this->layout = "main";
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        $query = VehicleOffer::where('dealer_id', $userid)
            ->with(['Vehicle', 'User']);

        $keyword = $request->input('keyword', '');
        $showType = $request->input('showtype', '');
        $user_id = $request->input('user_id', '');
        $fieldname = $request->input('searchin', 'All');

        if (!empty($keyword)) {
            $query->whereHas('Vehicle', function($q) use ($keyword, $fieldname) {
                if ($fieldname == 'All') {
                    $q->where('vehicle_name', 'LIKE', "%$keyword%");
                } else {
                    $q->where($fieldname, 'LIKE', "%$keyword%");
                }
            });
        }

        if (!empty($showType)) {
            $query->where('status', $showType);
        }

        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }

        $vehicleOffers = $query->orderBy('id', 'DESC')->paginate(20)->withQueryString();
        
        $timezone = session('default_timezone', 'UTC');

        return view('legacy.vehicleoffers.index', compact('vehicleOffers', 'keyword', 'showType', 'user_id', 'fieldname', 'timezone'));
    }

    public function add(Request $request, $offer_id = null)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;

        $this->layout = 'main';
        $offer_id = $offer_id ? base64_decode($offer_id) : null;
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        
        if ($request->isMethod('post')) {
            $data = $request->input('VehicleOffer', []);
            $data['driver_phone'] = substr(preg_replace("/[^0-9]/", "", $data['driver_phone'] ?? ''), -10);
            
            if (empty($offer_id)) {
                $data['dealer_id'] = $userid;
                $user = User::where('username', $data['driver_phone'])->first();
                if ($user) $data['user_id'] = $user->id;
            } else {
                $existing = VehicleOffer::findOrFail($offer_id);
                if ($existing->status == 1 && in_array($data['status'], [0, 2])) {
                    return redirect()->back()->with('error', "Sorry, selected offer already accepted by driver, you can't cancel now.");
                }
                $user = User::where('username', $data['driver_phone'])->first();
                if ($user && $existing->status != 1) $data['user_id'] = $user->id;
            }

            if (($data['duration'] ?? '') == 'custom') {
                $data['duration'] = $data['duration1'] ?? 0;
            }

            $timezone = session('default_timezone', 'UTC');
            if (!empty($data['start_datetime'])) {
                $data['start_datetime'] = Carbon::parse($data['start_datetime'], $timezone)->setTimezone('UTC')->toDateTimeString();
            }

            $deposit_opt = !empty($data['deposit_opt']) ? collect(array_values($data['deposit_opt']))->sum('amount') : 0;
            $data['total_deposit_amt'] = ($data['deposit_amt'] ?? 0) + $deposit_opt;
            $data['deposit_opt'] = $deposit_opt ? json_encode(array_values($data['deposit_opt'])) : "";

            $initial_fee_opt = !empty($data['initial_fee_opt']) ? collect(array_values($data['initial_fee_opt']))->sum('amount') : 0;
            $data['total_initial_fee'] = ($data['initial_fee'] ?? 0) + $initial_fee_opt;
            $data['initial_fee_opt'] = $initial_fee_opt ? json_encode(array_values($data['initial_fee_opt'])) : "";

            $duration_opt_sum = !empty($data['duration_opt']) ? collect(array_values($data['duration_opt']))->sum('duration') : 0;
            $data['duration_opt'] = $duration_opt_sum > 0 ? json_encode(array_values($data['duration_opt'])) : "";

            $offer = VehicleOffer::updateOrCreate(['id' => $offer_id], $data);

            if (!empty($offer->user_id)) {
                Log::info("Pubnub: notifyForOffer for user " . $offer->user_id);
            }

            return redirect('/vehicle_offers/index')->with('success', 'Offer data saved successfully');
        }

        $record = $offer_id ? VehicleOffer::find($offer_id) : null;
        if ($record) {
             $record->rent_opt = !empty($record->rent_opt) ? json_decode($record->rent_opt, true) : [];
             $record->initial_fee_opt = !empty($record->initial_fee_opt) ? json_decode($record->initial_fee_opt, true) : [];
             $record->deposit_opt = !empty($record->deposit_opt) ? json_decode($record->deposit_opt, true) : [];
             $record->duration_opt = !empty($record->duration_opt) ? json_decode($record->duration_opt, true) : [];
        }

        $timezone = session('default_timezone', 'UTC');
        return view('legacy.vehicleoffers.add', compact('record', 'offer_id', 'timezone'));
    }

    public function userautocomplete(Request $request)
    {
        return response()->json($this->_userautocomplete($request->query()));
    }

    public function vehicleautocomplete(Request $request)
    {
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');
        return response()->json($this->_vehicleautocomplete($request->query(), $userid));
    }

    public function getVehicleDynamicFareMatrix(Request $request)
    {
        $data = $request->input('VehicleOffer', []);
        $data['driver_phone'] = substr(preg_replace("/[^0-9]/", "", $data['driver_phone'] ?? ''), -10);
        
        $user = User::where('username', $data['driver_phone'])->first();

        $deposit_opt_sum = !empty($data['deposit_opt']) ? collect(array_values($data['deposit_opt']))->sum('amount') : 0;
        $data['total_deposit_amt'] = ($data['deposit_amt'] ?? 0) + $deposit_opt_sum;
        $data['deposit_opt'] = $deposit_opt_sum ? json_encode(array_values($data['deposit_opt'])) : "";

        $initial_fee_opt_sum = !empty($data['initial_fee_opt']) ? collect(array_values($data['initial_fee_opt']))->sum('amount') : 0;
        $data['total_initial_fee'] = ($data['initial_fee'] ?? 0) + $initial_fee_opt_sum;
        $data['initial_fee_opt'] = $initial_fee_opt_sum ? json_encode(array_values($data['initial_fee_opt'])) : "";

        $duration_opt_sum = !empty($data['duration_opt']) ? collect(array_values($data['duration_opt']))->sum('duration') : 0;
        $data['duration_opt'] = $duration_opt_sum > 0 ? json_encode(array_values($data['duration_opt'])) : "";

        return response()->json($this->_getVehicleDynamicFareMatrix($data, $user));
    }

    public function cancel($id)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $id = base64_decode($id);
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        $offer = VehicleOffer::where('id', $id)->where('dealer_id', $userid)->firstOrFail();
        if ($offer->status != 1) {
            $offer->update(['status' => 2]);
            return redirect()->back()->with('success', 'Your request processed successfully.');
        }
        return redirect()->back()->with('error', "Sorry, selected offer already accepted by driver, you can't cancel now.");
    }

    public function delete($id)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $id = base64_decode($id);
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        $offer = VehicleOffer::where('id', $id)->where('dealer_id', $userid)->firstOrFail();
        if ($offer->status != 1) {
            $offer->delete();
            return redirect()->back()->with('success', 'Your request processed successfully.');
        }
        return redirect()->back()->with('error', "Sorry, selected offer already accepted by driver, you can't cancel now.");
    }

    public function view($id)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $this->layout = 'main';
        $id = base64_decode($id);
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        $offer = VehicleOffer::where('id', $id)->where('dealer_id', $userid)->with('Vehicle')->firstOrFail();
        
        $offer->rent_opt = !empty($offer->rent_opt) ? json_decode($offer->rent_opt, true) : [];
        $offer->initial_fee_opt = !empty($offer->initial_fee_opt) ? json_decode($offer->initial_fee_opt, true) : [];
        $offer->deposit_opt = !empty($offer->deposit_opt) ? json_decode($offer->deposit_opt, true) : [];
        $offer->duration_opt = !empty($offer->duration_opt) ? json_decode($offer->duration_opt, true) : [];

        $timezone = session('default_timezone', 'UTC');
        return view('legacy.vehicleoffers.view', compact('offer', 'timezone'));
    }

    public function qualify(Request $request)
    {
        return response()->json($this->qualifyCheckr($request->input('VehicleOffer', [])));
    }

    public function qualifyIncome(Request $request)
    {
        return response()->json($this->_qualifyIncome($request->input('VehicleOffer', [])));
    }

    public function duplicate($id)
    {
        if ($redirect = $this->ensureUserSession()) return $redirect;
        $id = base64_decode($id);
        $userid = session('userParentId', 0) == 0 ? session('userid') : session('userParentId');

        $offer = VehicleOffer::where('id', $id)->where('dealer_id', $userid)->firstOrFail();
        $newId = $this->_duplicate($offer);
        
        return redirect('/vehicle_offers/add/' . base64_encode($newId))->with('success', 'Offer data is copied successfully');
    }

    public function qualifyCheckr($offer)
    {
        return $this->trait_qualifyCheckr($offer);
    }

    protected function _userautocomplete($params)
    {
        return $this->trait_userautocomplete($params);
    }

    protected function _vehicleautocomplete($params, $dealerid = null, $isAdmin = false)
    {
        return $this->trait_vehicleautocomplete($params, $dealerid, $isAdmin);
    }

    protected function _qualifyIncome($offer)
    {
        return $this->trait_qualifyIncome($offer);
    }

    protected function _duplicate($offerData)
    {
        return $this->trait_duplicate($offerData);
    }
}
