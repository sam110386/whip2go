<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * CakePHP `PlaidController` — Plaid Link (Plaid API not ported; stubs only).
 */
class PlaidController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request, ?string $userid = ''): View
    {
        return view('plaid.index', [
            'userid' => $userid,
        ]);
    }

    public function paystub(?string $userid = null): never
    {
        exit('Sorry, this feature is discontinued.');
    }

    public function success(): View
    {
        return view('plaid.success');
    }

    public function saveUser(Request $request): JsonResponse
    {
        return response()->json([
            'status' => false,
            'msg' => 'Plaid saveUser not yet ported',
        ]);
    }

    public function webhook(Request $request): Response
    {
        $raw = $request->getContent();
        $logPath = storage_path('logs/plaid_webhook' . date('Y-m-d') . '.log');
        File::append($logPath, "\n" . date('Y-m-d H:i:s') . '=' . $raw);

        // Stub: LINK / INCOME webhook handling not ported
        return response('finished', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function callback(Request $request): RedirectResponse|string
    {
        $oauthStateId = $request->query('oauth_state_id');
        if ($oauthStateId === null || $oauthStateId === '') {
            exit('do nothing');
        }

        $logPath = storage_path('logs/plaid_webhook' . date('Y-m-d') . '.log');
        File::append($logPath, "\ncallback\n" . date('Y-m-d H:i:s') . '=' . json_encode($request->query()));

        session(['oauth_state_id' => $oauthStateId]);

        return redirect('/plaid/index');
    }
}
