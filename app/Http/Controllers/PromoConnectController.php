<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\PromoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromoConnectController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index($userid)
    {
        $decodedUserid = base64_decode($userid);
        $ouruser = DB::table('users')->where('id', $decodedUserid)->count();

        if (!$ouruser) {
            return redirect('/promo/promoconnect/error');
        }

        $promos = DB::table('promotion_rules')
            ->where('status', 1)
            ->select('id', 'promo', 'title', 'logo', 'list')
            ->get();

        $promoterm = DB::table('promo_terms')
            ->where('status', 1)
            ->where('user_id', $decodedUserid)
            ->first();

        $code = '';
        $provider = '';

        if (!empty($promoterm)) {
            foreach ($promos as $promo) {
                if ($promo->id == $promoterm->promo_rule_id) {
                    if ($promo->list == 1) {
                        $provider = $promo->id;
                    } else {
                        $code = $promo->promo;
                    }
                    break;
                }
            }
        }

        return view('promo_connect.index', compact('userid', 'promos', 'code', 'provider'));
    }

    public function error()
    {
        return view('promo_connect.error');
    }

    public function success()
    {
        return view('promo_connect.success');
    }

    public function apply(Request $request)
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];

        if (!$request->isMethod('post')) {
            $return['msg'] = 'sorry wrong page';
            return response()->json($return);
        }

        $promouser = $request->input('promouser');
        [$promo, $encodedUserid] = explode('-', base64_decode($promouser));
        $userid = base64_decode($encodedUserid);

        if ($promo === '' || empty($userid)) {
            return response()->json($return);
        }

        $pendingBooking = DB::table('vehicle_reservations')
            ->where('renter_id', $userid)
            ->where('status', 0)
            ->count();

        if ($pendingBooking) {
            $return['message'] = 'Sorry, promo code is not valid for you.';
            return response()->json($return);
        }

        $activeBooking = DB::table('cs_orders')
            ->where('renter_id', $userid)
            ->whereIn('status', [0, 1])
            ->count();

        if ($activeBooking) {
            $return['message'] = 'Sorry, promo code is not valid for you.';
            return response()->json($return);
        }

        $resp = (new PromoService())->applyPromoIdToUser($promo, $userid);
        return response()->json($resp);
    }

    public function applypromo(Request $request, $userid)
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];

        if (!$request->isMethod('post')) {
            $return['msg'] = 'sorry wrong page';
            return response()->json($return);
        }

        $promocode = $request->input('PromotionRule.code');
        $userid = base64_decode($userid);

        if ($promocode === '' || empty($userid)) {
            return response()->json($return);
        }

        $pendingBooking = DB::table('vehicle_reservations')
            ->where('renter_id', $userid)
            ->where('status', 0)
            ->count();

        if ($pendingBooking) {
            $return['message'] = 'Sorry, promo code is not valid for you.';
            return response()->json($return);
        }

        $activeBooking = DB::table('cs_orders')
            ->where('renter_id', $userid)
            ->whereIn('status', [0, 1])
            ->count();

        if ($activeBooking) {
            $return['message'] = 'Sorry, promo code is not valid for you.';
            return response()->json($return);
        }

        $resp = (new PromoService())->validatePromo($promocode, $userid);
        return response()->json($resp);
    }

    public function removepromo(Request $request, $userid)
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];

        if (!$request->isMethod('post')) {
            $return['msg'] = 'sorry wrong page';
            return response()->json($return);
        }

        $userid = base64_decode($userid);
        if (empty($userid)) {
            return response()->json($return);
        }

        $resp = (new PromoService())->removePromoIdToUser($userid);
        return response()->json($resp);
    }
}
