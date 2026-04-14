<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\MessageHistoriesController as AdminMessageHistoriesController;
use Illuminate\Http\Request;

class MessageHistoriesController extends AdminMessageHistoriesController
{
    public function loadmessagehistory(Request $request)
    {
        return parent::loadmessagehistory($request);
    }

    public function loadnewmessage(Request $request)
    {
        return parent::loadnewmessage($request);
    }

    public function sendnewmessage(Request $request)
    {
        return parent::sendnewmessage($request);
    }
}

