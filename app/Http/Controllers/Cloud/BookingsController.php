<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\BookingsTrait;
use App\Models\Legacy\CsOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class BookingsController extends LegacyAppController
{
    use BookingsTrait;

    /**
     * cloud_index: Super Admin Booking View
     */
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return $redirect;

        $query = CsOrder::leftJoin('users as Owner', 'Owner.id', '=', 'cs_orders.user_id')
            ->leftJoin('users as Driver', 'Driver.id', '=', 'cs_orders.renter_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'cs_orders.vehicle_id')
            ->select(
                'cs_orders.*', 
                'Owner.company_name as owner_company', 'Owner.first_name as owner_first', 'Owner.last_name as owner_last', 
                'Driver.first_name as driver_first', 'Driver.last_name as driver_last',
                'Vehicle.vehicle_name', 'Vehicle.vin_no'
            );

        // Global search for cloud admin
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('Driver.first_name', 'LIKE', "%$keyword%")
                  ->orWhere('Driver.email', 'LIKE', "%$keyword%")
                  ->orWhere('Owner.company_name', 'LIKE', "%$keyword%")
                  ->orWhere('Vehicle.vin_no', 'LIKE', "%$keyword%")
                  ->orWhere('cs_orders.id', 'LIKE', "%$keyword%");
            });
        }

        $tripLog = $query->orderBy('cs_orders.id', 'DESC')->paginate(100)->withQueryString();

        return view('cloud.booking.index', [
            'tripLog' => $tripLog,
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
        $CsOrder = CsOrder::with(['Vehicle', 'User', 'Renter', 'OrderDepositRule'])->findOrFail($id);

        return view('cloud.booking.view', compact('CsOrder'));
    }

    /**
     * cloud_startBooking: Global start
     */
    public function cloud_startBooking(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('orderid'));
        $CsOrder = CsOrder::find($orderid);
        
        if (!$CsOrder) return response()->json(['status' => false, 'message' => "Booking not found"]);
        return response()->json($this->_startBooking($CsOrder));
    }

    /**
     * cloud_completeBooking: Global completion
     */
    public function cloud_completeBooking(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
        
        $orderid = base64_decode($request->input('Text.orderid'));
        $json = json_decode(base64_decode($request->input('Text.json')), true);
        
        $CsOrder = CsOrder::find($orderid);
        if (!$CsOrder) return response()->json(['status' => false, 'message' => "Booking not found"]);

        $CsOrder->update([
            'status' => 3,
            'rent' => $json['rent'] ?? $CsOrder->rent,
            'tax' => $json['tax'] ?? $CsOrder->tax,
            'end_timing' => now()
        ]);

        return response()->json(['status' => true, 'message' => "Booking completed by super-admin"]);
    }
}
