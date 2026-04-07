<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\VehicleOffersController as LegacyVehicleOffersController;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleOffer;
use App\Models\Legacy\User;
use App\Models\Legacy\UserReport;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VehicleOffersController extends LegacyVehicleOffersController
{
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $adminUser = session('SESSION_ADMIN');
        $query = VehicleOffer::with(['Vehicle', 'User']);

        if (!$adminUser['administrator']) {
            $query->where('admin_id', $adminUser['parent_id']);
        }

        $keyword = $request->input('keyword', '');
        $showType = $request->input('show', '');
        $user_id = $request->input('user_id', '');
        $fieldname = $request->input('searchin', 'All');

        if (!empty($keyword)) {
            $query->whereHas('Vehicle', function($q) use ($keyword, $fieldname) {
                if ($fieldname == 'All') {
                    $q->where('vehicle_name', 'LIKE', "%$keyword%")
                      ->orWhere('vehicle_unique_id', 'LIKE', "%$keyword%");
                } else {
                    $q->where($fieldname, 'LIKE', "%$keyword%");
                }
            });
        }

        if ($showType !== '') {
            $query->where('status', $showType);
        }

        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }

        $vehicleOffers = $query->orderBy('id', 'DESC')->paginate(20)->withQueryString();
        $timezone = $adminUser['timezone'];

        return view('admin.vehicleoffers.index', compact('vehicleOffers', 'keyword', 'showType', 'user_id', 'fieldname', 'timezone'));
    }

    public function admin_add(Request $request, $offer_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $this->layout = 'admin';
        $adminUser = session('SESSION_ADMIN');
        $offer_id = $offer_id ? base64_decode($offer_id) : null;
        
        if ($request->isMethod('post')) {
            $data = $request->input('VehicleOffer', []);
            $data['driver_phone'] = substr(preg_replace("/[^0-9]/", "", $data['driver_phone'] ?? ''), -10);
            
            if (empty($offer_id)) {
                $data['admin_id'] = $adminUser['parent_id'];
                $user = User::where('username', $data['driver_phone'])->first();
                if ($user) $data['user_id'] = $user->id;

                $vehicleInfo = Vehicle::find($data['vehicle_id']);
                $data['dealer_id'] = $vehicleInfo ? ($vehicleInfo->user_id ?: 0) : 0;
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

            $timezone = $adminUser['timezone'];
            if (!empty($data['start_datetime'])) {
                $data['start_datetime'] = Carbon::parse($data['start_datetime'], $timezone)->setTimezone('UTC')->toDateTimeString();
            }

            $deposit_opt_sum = !empty($data['deposit_opt']) ? collect(array_values($data['deposit_opt']))->sum('amount') : 0;
            $data['total_deposit_amt'] = ($data['deposit_amt'] ?? 0) + $deposit_opt_sum;
            $data['deposit_opt'] = $deposit_opt_sum ? json_encode(array_values($data['deposit_opt'])) : "";

            $initial_fee_opt_sum = !empty($data['initial_fee_opt']) ? collect(array_values($data['initial_fee_opt']))->sum('amount') : 0;
            $data['total_initial_fee'] = ($data['initial_fee'] ?? 0) + $initial_fee_opt_sum;
            $data['initial_fee_opt'] = $initial_fee_opt_sum ? json_encode(array_values($data['initial_fee_opt'])) : "";

            $duration_opt_sum = !empty($data['duration_opt']) ? collect(array_values($data['duration_opt']))->sum('duration') : 0;
            $data['duration_opt'] = $duration_opt_sum > 0 ? json_encode(array_values($data['duration_opt'])) : "";

            $offer = VehicleOffer::updateOrCreate(['id' => $offer_id], $data);

            if (!empty($offer->user_id)) {
                Log::info("Pubnub: notifyForOffer for user " . $offer->user_id);
            }

            return redirect('/admin/vehicle_offers/index')->with('success', 'Offer data saved successfully');
        }

        $record = $offer_id ? VehicleOffer::find($offer_id) : null;
        if ($record) {
             $record->rent_opt = !empty($record->rent_opt) ? json_decode($record->rent_opt, true) : [];
             $record->initial_fee_opt = !empty($record->initial_fee_opt) ? json_decode($record->initial_fee_opt, true) : [];
             $record->deposit_opt = !empty($record->deposit_opt) ? json_decode($record->deposit_opt, true) : [];
             $record->duration_opt = !empty($record->duration_opt) ? json_decode($record->duration_opt, true) : [];
        }

        $timezone = $adminUser['timezone'];
        return view('admin.vehicleoffers.add', compact('record', 'offer_id', 'timezone'));
    }

    public function admin_userautocomplete(Request $request)
    {
        return response()->json($this->_userautocomplete($request->query()));
    }

    public function admin_vehicleautocomplete(Request $request)
    {
        $adminUser = session('SESSION_ADMIN');
        $dealers = null;
        if (!$adminUser['administrator']) {
            $dealers = AdminUserAssociation::where('admin_id', $adminUser['parent_id'])->pluck('user_id')->toArray();
        }
        return response()->json($this->_vehicleautocomplete($request->query(), $dealers, true));
    }

    public function admin_getVehicleDynamicFareMatrix(Request $request)
    {
        return $this->getVehicleDynamicFareMatrix($request);
    }

    public function admin_cancel($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        $id = base64_decode($id);
        $offer = VehicleOffer::findOrFail($id);
        if ($offer->status != 1) {
            $offer->update(['status' => 2]);
            return redirect()->back()->with('success', 'Your request processed successfully.');
        }
        return redirect()->back()->with('error', "Sorry, selected offer already accepted by driver, you can't cancel now.");
    }

    public function admin_delete($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        $id = base64_decode($id);
        $offer = VehicleOffer::findOrFail($id);
        if ($offer->status != 1) {
            $offer->delete();
            return redirect()->back()->with('success', 'Your request processed successfully.');
        }
        return redirect()->back()->with('error', "Sorry, selected offer already accepted by driver, you can't cancel now.");
    }

    public function admin_view($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        $this->layout = 'admin';
        $adminUser = session('SESSION_ADMIN');
        $id = base64_decode($id);

        $query = VehicleOffer::where('id', $id)->with('Vehicle');
        if (!$adminUser['administrator']) {
            $query->where('admin_id', $adminUser['parent_id']);
        }
        $offer = $query->firstOrFail();
        
        $offer->rent_opt = !empty($offer->rent_opt) ? json_decode($offer->rent_opt, true) : [];
        $offer->initial_fee_opt = !empty($offer->initial_fee_opt) ? json_decode($offer->initial_fee_opt, true) : [];
        $offer->deposit_opt = !empty($offer->deposit_opt) ? json_decode($offer->deposit_opt, true) : [];
        $offer->duration_opt = !empty($offer->duration_opt) ? json_decode($offer->duration_opt, true) : [];

        $timezone = $adminUser['timezone'];
        return view('admin.vehicleoffers.view', compact('offer', 'timezone'));
    }

    public function admin_qualify(Request $request)
    {
        return $this->qualify($request);
    }

    public function admin_qualifyIncome(Request $request)
    {
        return $this->qualifyIncome($request);
    }

    public function admin_duplicate($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;
        $id = base64_decode($id);
        $offer = VehicleOffer::findOrFail($id);
        $newId = $this->_duplicate($offer);
        return redirect('/admin/vehicle_offers/add/' . base64_encode($newId))->with('success', 'Offer data is copied successfully');
    }
}
