<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Free2MoveWebhooksController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private string $WEBHOOK_SECRET = 'GJHGJHGHG788768UYT';

    public function index(Request $request)
    {
        $authToken = $request->header('X-Token', $request->header('x-token', ''));
        if ($authToken !== $this->WEBHOOK_SECRET) {
            return response()->json(['status' => false, 'message' => 'Sorry, seems like you are not authorized']);
        }

        $postData = $request->getContent();
        Log::info('Free2Move webhook', ['payload' => $postData]);

        if (empty($postData)) {
            return response()->json(['status' => false, 'message' => 'Sorry, body payload required']);
        }

        $dataValues = json_decode($postData, true);
        if (empty($dataValues)) {
            return response()->json(['status' => false, 'message' => 'Sorry, body payload is not a valid json']);
        }

        if (empty($dataValues['vehicle_id']) || !isset($dataValues['monthly_price'])) {
            return response()->json(['status' => false, 'message' => 'Sorry, invalid payload']);
        }

        $vehicle = DB::table('vehicles')->where('id', $dataValues['vehicle_id'])->first();
        if (empty($vehicle)) {
            return response()->json(['status' => false, 'message' => 'Sorry, invalid vehicle id']);
        }

        $dataValues['datetime'] = date('Y-m-d H:i:s');
        $residualValue = $dataValues['residual_value'] ?? '0';
        if (strpos($residualValue, '%') !== false) {
            $residualValue = sprintf('%0.2f', preg_replace('/[^0-9,.]/', '', $residualValue) / 100);
        }
        $dataValues['residual_value'] = $residualValue;

        $dayrent = sprintf('%0.4f', ($dataValues['monthly_price'] * 12 / 365));

        DB::table('vehicles')
            ->where('id', $vehicle->id)
            ->update(['day_rent' => $dayrent, 'rent_opt' => '', 'status' => 1]);

        DB::table('deposit_rules')
            ->where('vehicle_id', $vehicle->id)
            ->update(['free_two_move' => json_encode($dataValues)]);

        return response()->json(['status' => true, 'message' => 'Your request processed successfully']);
    }
}
