<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\LinkedVehiclesTrait;
use Illuminate\Http\Request;

class LinkedVehiclesController extends LegacyAppController
{
    use LinkedVehiclesTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function saveImage(Request $request)
    {
        $vehicleId = $request->input('id');
        $return = $this->handleUpload($request, $vehicleId, 'vehicleimage');
        return response()->json($return);
    }
}
