<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsTwilioOrder;
use Illuminate\Http\Request;

trait MessageHistoriesTrait
{
    /**
     * Shared: load the twilio order for a given cs_order_id (optionally scoped to user)
     */
    protected function getTwilioOrder(int $orderId, ?int $userId = null): ?CsTwilioOrder
    {
        $query = CsTwilioOrder::where('cs_order_id', $orderId);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        return $query->first();
    }

    /**
     * Shared: load the cs_order + renter contact_number for a given order id
     */
    protected function getOrderWithRenter(int $orderId): ?object
    {
        return CsOrder::query()
            ->from('cs_orders as CsOrder')
            ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
            ->select('CsOrder.*', 'User.contact_number')
            ->where('CsOrder.id', $orderId)
            ->first();
    }

    /**
     * Shared: send a new Twilio message for a booking (used by Legacy, Admin, Cloud)
     */
    protected function processSendNewMessage(Request $request): \Illuminate\Http\JsonResponse
    {
        $rawOrderId = $request->input('CsTwilioOrder.cs_order_id', '');
        $orderId    = (int)base64_decode(trim($rawOrderId));
        $msg        = trim($request->input('CsTwilioOrder.details', ''));

        $return = ['status' => false, 'message' => 'Sorry, related order not found.'];

        if (empty($orderId)) {
            return response()->json($return);
        }

        $orderData = $this->getOrderWithRenter($orderId);

        if (empty($orderData)) {
            return response()->json($return);
        }

        $msg = strip_tags($msg); // Sanitize::clean equivalent
        $return['message'] = 'Sorry, required data not passed.';

        if (!empty($orderData->contact_number) && !empty($msg)) {
            // Get or create CsTwilioOrder record
            $existing = CsTwilioOrder::where('cs_order_id', $orderId)->first();

            if (empty($existing)) {
                $twilioOrder = new CsTwilioOrder();
                // Copy over cs_order fields (except reserved columns)
                $orderArr = $orderData->toArray();
                foreach (['created_at', 'updated_at', 'status', 'id'] as $skip) {
                    unset($orderArr[$skip]);
                }
                $twilioOrder->fill($orderArr);
                $twilioOrder->renter_phone  = $orderData->contact_number;
                $twilioOrder->cs_order_id   = $orderId;
                $twilioOrder->status        = 0;
                $twilioOrder->created_at    = now();
                $twilioOrder->save();
                $csOrderId = $twilioOrder->id;
            } else {
                $csOrderId = $existing->id;
            }

            // Call Twilio lib (dynamic, to avoid hard dependency)
            $twilioClass = '\\App\\Lib\\Legacy\\Twilio';
            if (class_exists($twilioClass)) {
                $TwilioObj = new $twilioClass();
                $return    = $TwilioObj->autonotifyByTwilio(
                    $orderData->contact_number,
                    $msg,
                    $csOrderId,
                    $orderData->user_id
                );
            } else {
                $return = ['status' => false, 'message' => 'Twilio not configured.'];
            }
        }

        return response()->json($return);
    }
}
