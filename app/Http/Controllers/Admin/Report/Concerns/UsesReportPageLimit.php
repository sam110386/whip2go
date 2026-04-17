<?php

namespace App\Http\Controllers\Admin\Report\Concerns;

use Illuminate\Http\Request;

trait UsesReportPageLimit
{
    protected function getPageLimit(Request $request, string $sessKey, int $default = 25): int
    {
        if ($request->filled('Record.limit')) {
            $limit = (int) $request->input('Record.limit');
            session([$sessKey => $limit]);

            return $limit;
        }

        return (int) session($sessKey, $default);
    }
}
