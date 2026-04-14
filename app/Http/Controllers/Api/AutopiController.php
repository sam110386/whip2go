<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Migrated from: app/Plugin/AutoPi/Controller/AutopiController.php
 *                app/Plugin/AutoPi/Controller/AutoServicesController.php
 *
 * API endpoint for AutoPi device token generation.
 */
class AutopiController extends Controller
{
    protected static int $STATUS_FAIL = 0;
    protected static int $STATUS_SUCCESS = 1;

    private string $xsecurity = 'ca3559e5b8e1f442103368fc16';
    private string $autopiToken = 'cb15fde607a65088b3d0d1d980695393dbdaa38f';
    private string $version = 'v1.0';

    public function token(Request $request): JsonResponse
    {
        $xsecurity = $request->header('X-Security', $request->header('x-security', ''));
        if (empty($xsecurity) || strtolower($xsecurity) !== $this->xsecurity) {
            return response()->json([
                'status'  => static::$STATUS_FAIL,
                'message' => 'Sorry, seems you are spam bot',
            ], 402);
        }

        if ($request->route('ver') !== $this->version) {
            return response()->json([
                'status'  => 0,
                'message' => 'Sorry, Please update your app to latest version',
            ], 302);
        }

        $postData = $this->getPostData($request);
        $dataValues = json_decode($postData);

        if (empty($dataValues) || empty($dataValues->device_id)) {
            return response()->json([
                'status'  => static::$STATUS_FAIL,
                'message' => __('Invalid request payload'),
                'result'  => [],
            ]);
        }

        $autoPi = new \App\Services\Legacy\AutoPiFleetClient();
        $result = $autoPi->generateAccessToken($dataValues->device_id, $this->autopiToken);

        return response()->json($result);
    }

    protected function getPostData(Request $request): string
    {
        $raw = $request->getContent();

        Log::channel('daily')->info('autopi', [
            'url'  => $request->fullUrl(),
            'body' => $raw,
        ]);

        return $raw ?: '';
    }
}
