<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\MessageHistoriesController as AdminMessageHistoriesController;
use Illuminate\Http\Request;

class MessageHistoriesController extends AdminMessageHistoriesController
{
    public function cloud_loadmessagehistory(Request $request)
    {
        return $this->admin_loadmessagehistory($request);
    }

    public function cloud_loadnewmessage(Request $request)
    {
        return $this->admin_loadnewmessage($request);
    }

    public function cloud_sendnewmessage(Request $request)
    {
        return $this->admin_sendnewmessage($request);
    }
}

