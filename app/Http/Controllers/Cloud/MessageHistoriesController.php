<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\MessageHistoriesTrait;
use App\Models\Legacy\CsTwilioOrder;
use Illuminate\Http\Request;

class MessageHistoriesController extends LegacyAppController
{
    use MessageHistoriesTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function cloud_loadmessagehistory(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $orderId       = (int)base64_decode(trim($request->input('orderid', '')));
        $CsTwilioOrder = $this->getTwilioOrder($orderId);

        return view('cloud.message_histories.load_message_history', [
            'CsTwilioOrder' => $CsTwilioOrder,
            'orderid'       => base64_encode((string)$orderId),
        ]);
    }

    public function cloud_loadnewmessage(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $orderId       = (int)base64_decode(trim($request->input('orderid', '')));
        $CsTwilioOrder = $this->getTwilioOrder($orderId);
        $CsOrder       = $this->getOrderWithRenter($orderId);

        return view('cloud.message_histories.load_new_message', [
            'CsTwilioOrder' => $CsTwilioOrder,
            'CsOrder'       => $CsOrder,
            'orderid'       => base64_encode((string)$orderId),
        ]);
    }

    public function cloud_sendnewmessage(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        return $this->processSendNewMessage($request);
    }
}
