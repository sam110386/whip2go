<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;

/**
 * Cake `StripeAuthsController` — Stripe Connect OAuth callback (stubbed in Laravel).
 */
class StripeAuthsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request)
    {
        $status = 'error';

        if ($request->filled(['code', 'state'])) {
            // Legacy exchanged `code` at https://connect.stripe.com/oauth/token — not ported.
            session()->flash('error', 'Stripe OAuth not yet ported to Laravel');
        } else {
            session()->flash('error', 'Sorry, something went wrong.');
        }

        return view('stripe_auths.index', [
            'status' => $status,
        ]);
    }

    public function mbindex(Request $request)
    {
        $status = 'error';

        if ($request->filled(['code', 'state'])) {
            session()->flash('error', 'Stripe OAuth not yet ported to Laravel');
        } else {
            session()->flash('error', 'Sorry, something went wrong.');
        }

        return view('stripe_auths.index', [
            'status' => $status,
        ]);
    }
}
