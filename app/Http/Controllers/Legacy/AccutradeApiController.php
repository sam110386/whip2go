<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;

class AccutradeApiController extends AccutradeController
{
    /**
     * Dispatcher to enforce version checks and map REST actions to the controller functions.
     * In Cake PHP, this handled the /AccutradeApi/v1.0/:action routing while extending AccutradeController.
     */
    public function dispatch(Request $request, string $ver, string $action)
    {
        $version = 'v1.0';
        if ($ver !== $version) {
            return $this->unsupportedVersion($ver);
        }

        if (!method_exists($this, $action)) {
            return $this->notImplemented($request, $ver, $action, 404);
        }

        return $this->{$action}($request);
    }

    /**
     * Helper to gracefully handle mismatched versions like the legacy Cake PHP code:
     * -> echo json_encode(array('status' => 0, 'message' => "Sorry, Please update your app to latest version"));
     */
    protected function unsupportedVersion(string $ver)
    {
        return response()->json([
            'status' => 0,
            'message' => 'Sorry, Please update your app to latest version'
        ], 302)->header('Content-Type', 'application/json; charset=utf-8');
    }

    // Wrapper methods kept for CakePHP action-name parity in this controller.
    public function getAuthToken(Request $request) { return parent::getAuthToken($request); }
    public function refreshToken(Request $request) { return parent::refreshToken($request); }
    public function addVehicles(Request $request) { return parent::addVehicles($request); }
    public function addVehicle(Request $request) { return parent::addVehicle($request); }
    public function removeVehicles(Request $request) { return parent::removeVehicles($request); }
    public function removeVehicle(Request $request) { return parent::removeVehicle($request); }
    public function checkVehicleStatus(Request $request) { return parent::checkVehicleStatus($request); }
    public function checkDealerExists(Request $request) { return parent::checkDealerExists($request); }
}
