<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderNote;

/**
 * Migrated from: app/Plugin/OrderNote/Controller/OrderNotesController.php
 */
class OrderNotesController extends LegacyAppController
{
    public function loadhistory(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $order = $request->route('orderid') ?? null;
        $parent = $request->route('parentid') ?? null;

        if ($order !== null && $parent !== null) {
            $orderid = $order;
            $parentid = $parent;
        } else {
            $orderid = trim($request->input('orderid', ''));
            $orderid = $this->decodeId($orderid);
            $csOrder = CsOrder::select('id', 'parent_id')
                ->where('id', $orderid)
                ->first();
            $parentid = !empty($csOrder->parent_id) ? $csOrder->parent_id : ($csOrder->id ?? null);
        }

        $limit = 10;
        $history = CsOrderNote::with('csOrder:id,increment_id')
            ->where('parent_order_id', $parentid)
            ->latest('id')
            ->paginate($limit);

        $data = compact('history', 'orderid', 'parentid', 'limit');

        if ($request->ajax() && $order !== null && $parent !== null) {
            return view('admin.order_notes._history', $data);
        }

        return view('admin.order_notes._loadhistory', $data);
    }
    public function loadnewnotepopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $orderid = trim($request->input('orderid', ''));
        $parentid = trim($request->input('parentid', ''));

        return view('admin.order_notes._loadnewnotepopup', compact('orderid', 'parentid'));
    }
    public function savenote(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $return = [
            'status' => false,
            'message' => 'Sorry, related order not found.'
        ];

        if ($request->ajax()) {
            $noteData = $request->input('OrderNote', []);
            $noteData['user_id'] = 0;
            $noteData['craeted'] = now()->toDateTimeString();
            CsOrderNote::create($noteData);

            $return = [
                'status' => true,
                'message' => 'Your record is saved successfully'
            ];
        }

        return response()->json($return);
    }
}
