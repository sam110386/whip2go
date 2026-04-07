<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleReservationsTrait;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderStatuslog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VehicleReservationsController extends LegacyAppController
{
    use VehicleReservationsTrait;

    /**
     * admin_index: Manage Reservations
     */
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $loggedId = session('SESSION_ADMIN.id');
        $query = VehicleReservation::leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'vehicle_reservations.vehicle_id')
            ->leftJoin('cs_order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'vehicle_reservations.id')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'vehicle_reservations.renter_id')
            ->select(
                'vehicle_reservations.*', 
                'Vehicle.msrp', 'Vehicle.vin_no', 'Vehicle.vehicle_name', 'Vehicle.plate_number',
                'OrderDepositRule.downpayment', 'OrderDepositRule.insurance_payer', 'OrderDepositRule.id as rule_id', 'OrderDepositRule.financing',
                'Renter.first_name', 'Renter.last_name', 'Renter.email', 'Renter.contact_number'
            );

        if (session('adminRoleId') != 1) {
            $query->where('vehicle_reservations.user_id', $loggedId);
        }

        // Filtering logic...
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('Renter.first_name', 'LIKE', "%$keyword%")
                  ->orWhere('Renter.email', 'LIKE', "%$keyword%")
                  ->orWhere('Vehicle.vin_no', 'LIKE', "%$keyword%");
            });
        }

        $bookings = $query->orderBy('vehicle_reservations.id', 'DESC')->paginate(50)->withQueryString();

        return view('admin.vehiclereservation.index', [
            'bookings' => $bookings,
            'readyForDealerStatus' => $this->readyForDealerStatus,
            'checklist' => $this->checklist
        ]);
    }

    /**
     * admin_edit: View/Edit Reservation details
     */
    public function admin_edit(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        $reservation = VehicleReservation::with(['Vehicle', 'User', 'Renter', 'OrderDepositRule'])->findOrFail($id);
        
        $statusLogs = CsOrderStatuslog::where('reservation_id', $id)->orderBy('id', 'DESC')->get();

        return view('admin.vehiclereservation.edit', compact('reservation', 'statusLogs'));
    }

    /**
     * admin_updatestatus: Update Reservation Status
     */
    public function admin_updatestatus(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);

        $id = $request->input('id');
        $status = $request->input('status');

        if ($this->_changeStatus($id, $status)) {
            return response()->json(['status' => true, 'message' => "Status updated successfully"]);
        }
        return response()->json(['status' => false, 'message' => "Failed to update status"]);
    }

    /**
     * admin_updatechecklist: Update individual checklist items
     */
    public function admin_updatechecklist(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);

        $id = $request->input('id');
        $key = $request->input('key');
        $value = $request->input('value');

        $reservation = VehicleReservation::find($id);
        if ($reservation) {
            $checklists = json_decode($reservation->checklists, true) ?: [];
            $checklists[$key] = $value;
            $reservation->update(['checklists' => json_encode($checklists)]);
            return response()->json(['status' => true, 'message' => "Checklist updated"]);
        }
        return response()->json(['status' => false, 'message' => "Reservation not found"]);
    }

    /**
     * admin_delete: Delete reservation
     */
    public function admin_delete($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        VehicleReservation::where('id', $id)->delete();
        
        return redirect()->back()->with('success', 'Reservation deleted successfully');
    }
}
