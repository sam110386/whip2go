<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\OrderDepositRulesController as AdminOrderDepositRulesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderDepositRulesController extends AdminOrderDepositRulesController
{
    public function cloud_linkedupdate(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/bookings/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        $orderId = $this->decodeB64Id($id);
        if (!$orderId) {
            return redirect('/cloud/linked_bookings/index')->with('error', 'Invalid booking.');
        }

        $parentId = (int)($admin['parent_id'] ?? 0);
        $dealerIds = DB::table('admin_user_associations')
            ->where('admin_id', $parentId)
            ->pluck('user_id')
            ->map(fn ($v) => (int)$v)
            ->toArray();

        $ownerId = (int)DB::table('cs_orders')->where('id', $orderId)->value('user_id');
        if (!in_array($ownerId, $dealerIds, true)) {
            return redirect('/cloud/linked_bookings/index')
                ->with('error', 'Booking is not linked to this dealer account.');
        }

        return $this->runDepositRuleUpdate(
            $request,
            $id,
            '/cloud/order_deposit_rules/linkedupdate/',
            '/cloud/linked_bookings/index'
        );
    }

    /**
     * @internal Used by {@see cloud_linkedupdate()} via parent; exposed for dispatcher if URL uses this action name.
     */
    public function linkedupdate(Request $request, $id = null)
    {
        return $this->cloud_linkedupdate($request, $id);
    }
}
