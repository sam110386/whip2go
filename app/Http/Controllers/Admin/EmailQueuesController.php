<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\EmailQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Migrated from: app/Plugin/EmailQueue/Controller/EmailQueuesController.php
 *
 * Payment receipt download for admin and cloud/user contexts.
 */
class EmailQueuesController extends LegacyAppController
{
    /**
     * admin_payment_receipt → paymentReceipt (admin)
     */
    /**
     * @return JsonResponse|BinaryFileResponse
     */
    public function paymentReceipt(string $paymentid)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = ['status' => false, 'message' => 'Sorry, something went wrong'];
        $paymentid = base64_decode($paymentid);

        if (empty($paymentid)) {
            return response()->json($return);
        }

        $orderData = DB::table('cs_order_payments as CsOrderPayment')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsOrderPayment.cs_order_id')
            ->where('CsOrderPayment.id', $paymentid)
            ->select(
                'CsOrderPayment.*',
                'CsOrder.increment_id', 'CsOrder.renter_id',
                'CsOrder.start_datetime', 'CsOrder.end_datetime',
                'CsOrder.timezone', 'CsOrder.vehicle_name'
            )
            ->first();

        if (empty($orderData)) {
            return response()->json($return);
        }

        $renter = DB::table('users')
            ->where('id', $orderData->renter_id)
            ->select('first_name', 'last_name', 'email', 'address', 'city', 'state', 'zip')
            ->first();

        $paymentTypes = (new \App\Services\Legacy\Common())->getPayoutTypeValue(true);
        $msg = 'Payment was successful for the ' . ($paymentTypes[$orderData->type] ?? 'Fee') . ' charges of your DriveItAway order ';

        $service = new EmailQueueService();
        $resp = $service->generateReceipt($orderData, $renter, 'card', $msg);

        if (!$resp['status']) {
            return response()->json($resp);
        }

        return response()->download($resp['filefullname']);
    }

    /**
     * payment_receipt → userPaymentReceipt (user/cloud facing)
     */
    /**
     * @return JsonResponse|BinaryFileResponse
     */
    public function userPaymentReceipt(Request $request, string $paymentid)
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $return = ['status' => false, 'message' => 'Sorry, something went wrong'];
        $paymentid = base64_decode($paymentid);

        if (empty($paymentid)) {
            return response()->json($return);
        }

        $orderData = DB::table('cs_order_payments as CsOrderPayment')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'CsOrderPayment.cs_order_id')
            ->where('CsOrderPayment.id', $paymentid)
            ->where('CsOrder.user_id', $userid)
            ->select(
                'CsOrderPayment.*',
                'CsOrder.increment_id', 'CsOrder.renter_id',
                'CsOrder.start_datetime', 'CsOrder.end_datetime',
                'CsOrder.timezone', 'CsOrder.vehicle_name'
            )
            ->first();

        if (empty($orderData)) {
            return response()->json($return);
        }

        $renter = DB::table('users')
            ->where('id', $orderData->renter_id)
            ->select('first_name', 'last_name', 'email', 'address', 'city', 'state', 'zip')
            ->first();

        $paymentTypes = (new \App\Services\Legacy\Common())->getPayoutTypeValue(true);
        $msg = 'Payment was successful for the ' . ($paymentTypes[$orderData->type] ?? 'Fee') . ' charges of your DriveItAway order ';

        $service = new EmailQueueService();
        $resp = $service->generateReceipt($orderData, $renter, 'card', $msg);

        if (!$resp['status']) {
            return response()->json($resp);
        }

        return response()->download($resp['filefullname']);
    }
}
