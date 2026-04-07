<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\OrderDepositRulesTrait;
use Illuminate\Http\Request;

class OrderDepositRulesController extends LegacyAppController
{
    use OrderDepositRulesTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function cloud_linkedupdate(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $adminUser = session('AdminUser');
        if (!empty($adminUser['administrator'])) {
            return redirect('/admin/bookings/index')->with('error', 'Sorry, you are not authorized user for this action');
        }

        $orderId = (int)base64_decode($id);

        if (empty($orderId)) {
            return redirect()->back();
        }

        if ($request->isMethod('post') || $request->isMethod('put')) {
            return $this->processDepositRuleUpdate($request, $orderId, url()->previous());
        }

        $ruleArr = $this->loadDepositRule($orderId);

        return view('cloud.order_deposit_rules.cloud_linkedupdate', [
            'data' => ['OrderDepositRule' => $ruleArr],
            'id'   => $orderId,
        ]);
    }
}
