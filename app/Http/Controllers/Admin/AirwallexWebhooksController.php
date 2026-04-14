<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AirwallexWebhooksController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request)
    {
        $postData = $request->getContent();
        if (empty($postData)) {
            return response('Sorry, wrong effort!!', 400);
        }

        Log::channel('daily')->info('Airwallex Webhook', [
            'headers' => $request->headers->all(),
            'body' => $postData,
        ]);

        return response('finished');
    }
}
