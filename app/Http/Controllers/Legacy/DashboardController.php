<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Legacy\User;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\CsTwilioLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        
        $inbounds = CsTwilioLog::where('cs_twilio_logs.user_id', $userId)
            ->where('cs_twilio_logs.type', 2)
            ->leftJoin('cs_twilio_orders as CsTwilioOrder', 'CsTwilioOrder.id', '=', 'cs_twilio_logs.cs_twilio_order_id')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsTwilioOrder.cs_order_id')
            ->select('cs_twilio_logs.id', 'cs_twilio_logs.renter_phone', 'cs_twilio_logs.msg', 'cs_twilio_logs.created', 'CsOrder.increment_id')
            ->orderBy('cs_twilio_logs.id', 'DESC')
            ->limit(10)
            ->get();

        $outbounds = CsTwilioLog::where('cs_twilio_logs.user_id', $userId)
            ->where('cs_twilio_logs.type', 1)
            ->leftJoin('cs_twilio_orders as CsTwilioOrder', 'CsTwilioOrder.id', '=', 'cs_twilio_logs.cs_twilio_order_id')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsTwilioOrder.cs_order_id')
            ->select('cs_twilio_logs.id', 'cs_twilio_logs.renter_phone', 'cs_twilio_logs.msg', 'cs_twilio_logs.created', 'CsOrder.increment_id')
            ->orderBy('cs_twilio_logs.id', 'DESC')
            ->limit(10)
            ->get();

        return view('legacy.dashboard.index', compact('inbounds', 'outbounds'));
    }

    public function loadvehiclesummary(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');

        $activeVehicles = Vehicle::where('user_id', $userId)->where('status', 1)->where('waitlist', 0)->count();
        $availableVehicles = Vehicle::where('user_id', $userId)->where('status', 1)->where('waitlist', 0)->where('booked', 0)->count();
        $bookedVehicles = Vehicle::where('user_id', $userId)->where('status', 1)->where('waitlist', 0)->where('booked', 1)->count();
        $waitlistVehicles = Vehicle::where('user_id', $userId)->where('status', 1)->where('waitlist', 1)->where('booked', 0)->count();
        $totalVehicles = Vehicle::where('user_id', $userId)->count();

        return view('legacy.elements.dashboard.vehicle_summary', compact('activeVehicles', 'availableVehicles', 'bookedVehicles', 'waitlistVehicles', 'totalVehicles'));
    }

    public function loadbookingsummary(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');

        $activeBooking = CsOrder::where('user_id', $userId)->where('status', 1)->count();
        $pendingBooking = VehicleReservation::where('user_id', $userId)->where('status', 0)->count();
        $completed = CsOrder::where('user_id', $userId)->whereIn('status', [2, 3])->count();

        return view('legacy.elements.dashboard.booking_summary', compact('activeBooking', 'pendingBooking', 'completed'));
    }

    public function loadsalestatics(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $totalSales = (float) CsOrder::where('user_id', $userId)
            ->whereIn('status', [2, 3])
            ->sum('paid_amount');

        $monthlySales = (float) CsOrder::where('user_id', $userId)
            ->whereIn('status', [2, 3])
            ->whereBetween('created', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('paid_amount');

        return view('legacy.elements.dashboard.sales_statics', compact('totalSales', 'monthlySales'));
    }

    public function loadvehiclereport(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $vehicles = Vehicle::where('user_id', $userId)
            ->select('id', 'vehicle_name', 'status', 'booked', 'waitlist')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('legacy.elements.dashboard.vehicle_report', compact('vehicles'));
    }
}
