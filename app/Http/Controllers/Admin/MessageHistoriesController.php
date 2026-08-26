<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsTwilioOrder;
use App\Services\Legacy\TwilioClient;
use Illuminate\Http\Request;

class MessageHistoriesController extends LegacyAppController
{
    public function loadmessagehistory(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid')));
        $csTwilioOrder = CsTwilioOrder::with('csTwilioLogs')
            ->where('cs_order_id', $orderId)
            ->first();

        return view('admin.message_histories.loadmessagehistory', [
            'csTwilioOrder' => $csTwilioOrder,
            'orderId' => base64_encode($orderId),
        ]);
    }
    public function loadnewmessage(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('orderid')));
        $csTwilioOrder = CsTwilioOrder::select('id', 'cs_order_id')
            ->with(['csOrder:id,renter_id', 'csOrder.user:id,contact_number'])
            ->where('cs_order_id', $orderId)
            ->first();

        return view('admin.message_histories.loadnewmessage', [
            'csTwilioOrder' => $csTwilioOrder,
            'orderId' => base64_encode($orderId),
        ]);
    }
    public function sendnewmessage(Request $request)
    {
        $orderId = $this->decodeId(trim($request->input('cs_order_id')));
        $msg = trim($request->input('details'));
        $return = [
            'status' => false,
            'message' => 'Sorry, related order not found.'
        ];

        $orderData = CsOrder::with('user:id,contact_number')->find($orderId);

        if ($orderData) {
            $return['message'] = 'Sorry, required data not passed.';
            $renterPhone = $orderData->user?->contact_number;

            if (!empty($renterPhone) && !empty($msg)) {
                $alreadySent = CsTwilioOrder::where('cs_order_id', $orderId)->first();

                if (!$alreadySent) {
                    $twilioOrder = CsTwilioOrder::create([
                        'cs_order_id' => $orderData->id,
                        'renter_phone' => $renterPhone,
                        'user_id' => $orderData->user_id,
                        'vehicle_id' => $orderData->vehicle_id,
                        'status' => 0,
                    ]);

                    $csTwilioOrderId = $twilioOrder->id;
                } else {
                    $csTwilioOrderId = $alreadySent->id;
                }

                $return = (new TwilioClient())->autonotifyByTwilio(
                    $renterPhone,
                    $msg,
                    $csTwilioOrderId,
                    $orderData->user_id
                );
            }
        }

        return response()->json($return);
    }
}

