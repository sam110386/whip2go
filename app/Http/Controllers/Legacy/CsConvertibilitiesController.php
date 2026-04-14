<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CakePHP `CsConvertibilitiesController` — cron + external score webhook (no session).
 */
class CsConvertibilitiesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private const WEBHOOK_KEY = 'CXYHSGGYSY23GT';

    /**
     * Cron entry: legacy model ran `processNewRecords()` + `deactiavteRecords()`.
     * Stubbed here until those flows are ported to a service.
     */
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function runbyCron()
    {
        // Stub: legacy `CsUserConvertibility::processNewRecords()` and
        // `deactiavteRecords()` (SQL + notifications + outbound HTTP) not yet ported.

        return response('', 200);
    }

    /** @return \Symfony\Component\HttpFoundation\Response */
    public function runbycron()
    {
        return $this->runbyCron();
    }

    /**
     * Webhook: `key=CXYHSGGYSY23GT&contactId=…&score=…` (POST, Cake used `$_POST`).
     */
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive_score(Request $request)
    {
        $line = date('Y-m-d H:i:s') . '=' . print_r($request->query->all(), true) . print_r($request->request->all(), true);
        @file_put_contents(storage_path('logs/Convertibility.log'), "\n" . $line, FILE_APPEND);

        $key = (string) $request->input('key', '');
        if ($key !== self::WEBHOOK_KEY) {
            return response('sorry, key dont match', 200);
        }

        $contactId = $request->input('contactId');
        if ($contactId === null || $contactId === '') {
            return response('sorry, you missed to pass contactId', 200);
        }

        $referenceId = is_numeric($contactId) ? (int) $contactId : 0;
        if ($referenceId <= 0) {
            return response('sorry, you missed to pass contactId', 200);
        }

        $scoreRaw = $request->input('score');
        $score = is_numeric($scoreRaw) ? (int) $scoreRaw : 0;
        if ($score > 0) {
            $old = DB::table('cs_user_convertibilities')
                ->where('reference_id', $referenceId)
                ->value('score');

            $oldScore = is_numeric($old) ? (int) $old : 0;
            $change = $score - $oldScore;

            DB::table('cs_user_convertibilities')
                ->where('reference_id', $referenceId)
                ->update([
                    'score' => $score,
                    'scorechange' => (string) $change,
                ]);
        }

        return response('record updated successfully', 200);
    }
}
