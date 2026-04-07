<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\OrderDepositRulesTrait;
use Illuminate\Http\Request;

class OrderDepositRulesController extends LegacyAppController
{
    use OrderDepositRulesTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * update: User and Dealer view
     */
    public function update(Request $request, $id = null)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $result = $this->_processUpdate($request, $id);
        if ($result['status'] === 'success') {
            if ($request->isMethod('POST') || $request->isMethod('PUT')) {
                return redirect()->back()->with('success', $result['message']);
            }
            return view('legacy.order_deposit_rules.update', $result);
        }

        return redirect()->route('legacy.bookings.index')->with('error', $result['message']);
    }
}
