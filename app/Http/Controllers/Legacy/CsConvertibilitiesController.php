<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Legacy\CsUserConvertibility as LegacyCsUserConvertibility;

class CsConvertibilitiesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;
    private string $key = "CXYHSGGYSY23GT";

    /**
     * run by cron
     */
    public function runbyCron()
    {
        if (method_exists(LegacyCsUserConvertibility::class, 'processNewRecords')) {
            $model = new LegacyCsUserConvertibility();
            $model->processNewRecords();
            $model->deactiavteRecords();
        } else {
            Log::info("CsConvertibilities runByCron called, but model methods processNewRecords/deactiavteRecords are not yet fully ported.");
        }
        
        return response('Process complete');
    }

    /**
     * receive score data from Convertivility server
     * key=CXYHSGGYSY23GT&contactId=173139&score=23
     */
    public function receive_score(Request $request)
    {
        Log::build(['driver' => 'single', 'path' => storage_path('logs/Convertibility.log')])->info('Convertibility', [
            'request' => $request->all(),
            'post' => $request->post()
        ]);
        
        $received = $request->post();
        
        if (isset($received['key']) && $received['key'] === $this->key) {
            if (isset($received['contactId']) && !empty($received['contactId'])) {
                if (isset($received['score']) && $received['score'] > 0) {
                    $old = LegacyCsUserConvertibility::query()
                        ->where('reference_id', $received['contactId'])
                        ->first();
                        
                    if ($old) {
                        $change = $received['score'] - ($old->score ?? 0);
                        LegacyCsUserConvertibility::query()
                            ->where('reference_id', $received['contactId'])
                            ->update([
                                'score' => $received['score'],
                                'scorechange' => (string)$change
                            ]);
                    }
                }
                return response("record updated successfully");
            }
            return response("sorry, you missed to pass contactId");
        }
        return response("sorry, key dont match");
    }
}
