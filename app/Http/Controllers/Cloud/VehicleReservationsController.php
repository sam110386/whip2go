<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleReservationsTrait;
use App\Models\Legacy\VehicleReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class VehicleReservationsController extends LegacyAppController
{
    use VehicleReservationsTrait;

    /**
     * cloud_index: Super Admin Reservation View
     */
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return $redirect;

        $query = VehicleReservation::leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'vehicle_reservations.vehicle_id')
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'vehicle_reservations.id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'vehicle_reservations.renter_id')
            ->leftJoin('users as Owner', 'Owner.id', '=', 'vehicle_reservations.user_id')
            ->select(
                'vehicle_reservations.*', 
                'Vehicle.msrp', 'Vehicle.vin_no', 'Vehicle.vehicle_name',
                'OrderDepositRule.downpayment', 'OrderDepositRule.insurance_payer', 'OrderDepositRule.financing',
                'Renter.first_name as renter_first_name', 'Renter.last_name as renter_last_name',
                'Owner.company_name as owner_company'
            );

        // Global search for cloud admin
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('Renter.first_name', 'LIKE', "%$keyword%")
                  ->orWhere('Renter.email', 'LIKE', "%$keyword%")
                  ->orWhere('Owner.company_name', 'LIKE', "%$keyword%")
                  ->orWhere('Vehicle.vin_no', 'LIKE', "%$keyword%");
            });
        }

        $bookings = $query->orderBy('vehicle_reservations.id', 'DESC')->paginate(100)->withQueryString();

        return view('cloud.vehiclereservation.index', [
            'bookings' => $bookings,
            'readyForDealerStatus' => $this->readyForDealerStatus
        ]);
    }

    /**
     * cloud_view: Detailed view for cloud admin
     */
    public function cloud_view(Request $request, $id)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return $redirect;

        $id = base64_decode($id);
        $reservation = VehicleReservation::with(['Vehicle', 'User', 'Renter', 'OrderDepositRule'])->findOrFail($id);

        return view('cloud.vehiclereservation.view', compact('reservation'));
    }

    /**
     * cloud_updatestatus: Global status update
     */
    public function cloud_updatestatus(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);

        $id = $request->input('id');
        $status = $request->input('status');

        if ($this->_changeStatus($id, $status)) {
            return response()->json(['status' => true, 'message' => "Global status updated"]);
        }
        return response()->json(['status' => false, 'message' => "Failed to update status"]);
    }
}
