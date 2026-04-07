<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use App\Models\Legacy\CsUserConvertibility;

trait LinkedUsersTrait
{
    /**
     * Function to update target score via AJAX
     */
    protected function processUpdateTargetScore(Request $request)
    {
        $pk = $request->input('pk');
        $value = $request->input('value');

        if (empty($pk) || empty($value)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, something went wrong'
            ]);
        }

        $exists = CsUserConvertibility::where('user_id', $pk)->exists();

        if ($exists) {
            CsUserConvertibility::where('user_id', $pk)->update(['target_score' => $value]);
            $return = ['status' => 'success', 'message' => 'Record updated successfully'];
        } else {
            CsUserConvertibility::insert([
                'user_id' => $pk,
                'target_score' => $value
            ]);
            $return = ['status' => 'success', 'message' => 'Record updated successfully'];
        }

        // update Credit healthy server (calling legacy method dynamically if exists)
        $convertibility = new CsUserConvertibility();
        if (method_exists($convertibility, 'pushToCreditHealthy')) {
            $convertibility->pushToCreditHealthy($pk);
        }

        return response()->json($return);
    }
}
