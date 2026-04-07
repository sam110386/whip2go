<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class LegacyApiBaseController extends BaseController
{
    /**
     * Deterministic placeholder response while real Cake logic is ported.
     */
    protected function notImplemented(Request $request, string $ver, string $action, int $status = 200)
    {
        return response()->json([
            'status' => false,
            'message' => 'Not implemented (migration placeholder)',
            'version' => $ver,
            'action' => $action,
            'http_method' => $request->method(),
        ], $status)->header('Content-Type', 'application/json; charset=utf-8');
    }

    protected function unsupportedVersion(string $ver): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 0,
            'message' => 'Sorry, Please update your app to latest version',
            'version' => $ver,
        ], 302)->header('Content-Type', 'application/json; charset=utf-8');
    }
}

