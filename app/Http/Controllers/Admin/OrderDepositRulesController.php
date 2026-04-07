<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\OrderDepositRulesTrait;
use Illuminate\Http\Request;

class OrderDepositRulesController extends LegacyAppController
{
    use OrderDepositRulesTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * admin_update: Admin financial rule update
     */
    public function admin_update(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $result = $this->_processUpdate($request, $id);
        if ($result['status'] === 'success') {
            if ($request->isMethod('POST') || $request->isMethod('PUT')) {
                return redirect()->back()->with('success', $result['message']);
            }
            return view('admin.order_deposit_rules.admin_update', $result);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
<!-- slide -->
<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\OrderDepositRulesTrait;
use Illuminate\Http\Request;

class OrderDepositRulesController extends LegacyAppController
{
    use OrderDepositRulesTrait;

    protected bool $shouldLoadLegacyModules = true;

    /**
     * cloud_linkedupdate: Cloud linked booking financial update
     */
    public function cloud_linkedupdate(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudSession()) {
            return $redirect;
        }

        $result = $this->_processUpdate($request, $id);
        if ($result['status'] === 'success') {
            if ($request->isMethod('POST') || $request->isMethod('PUT')) {
                return redirect()->back()->with('success', $result['message']);
            }
            return view('cloud.order_deposit_rules.cloud_linkedupdate', $result);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
