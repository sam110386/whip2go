<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HitchBookingsController extends LegacyAppController
{
    public function index(Request $request, $ajax_flag = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $userid = session('SESSION_ADMIN.id');

        $tripLog = DB::table('cs_orders as CsOrder')
            ->leftJoin('hitch_leads as HitchLead', 'HitchLead.user_id', '=', 'CsOrder.renter_id')
            ->where('HitchLead.dealer_id', $userid)
            ->whereNotNull('HitchLead.id')
            ->whereIn('CsOrder.status', [0, 1])
            ->select('CsOrder.*')
            ->orderByDesc('CsOrder.id')
            ->get();

        if ($ajax_flag == 1) {
            return response()->view('cloud.hitch.bookings._booking_table', [
                'trip_Log' => $tripLog,
            ]);
        }

        return view('cloud.hitch.bookings.index', [
            'trip_Log' => $tripLog,
            'title_for_layout' => 'Rental Orders',
        ]);
    }
}
