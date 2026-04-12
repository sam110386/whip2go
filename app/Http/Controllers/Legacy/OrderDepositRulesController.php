<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cake `OrderDepositRulesController::update` — legacy app immediately redirected to bookings.
 */
class OrderDepositRulesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function update(Request $request, $id = null): RedirectResponse
    {
        return redirect('/bookings/index');
    }
}
