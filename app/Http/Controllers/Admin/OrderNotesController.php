<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Migrated from: app/Plugin/OrderNote/Controller/OrderNotesController.php
 *
 * Admin booking notes: history listing, new-note popup, save.
 * CTP views migrated to: resources/views/admin/order_notes/
 */
class OrderNotesController extends LegacyAppController
{
    /**
     * admin_loadhistory → loadhistory
     */
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
            $orderid = base64_decode($orderid);

            $csOrder = DB::table('cs_orders')
                ->where('id', $orderid)
                ->select('id', 'parent_id')
                ->first();

            $parentid = !empty($csOrder->parent_id) ? $csOrder->parent_id : ($csOrder->id ?? null);
        }

        $perPage = 10;

        $history = DB::table('cs_order_notes as OrderNote')
            ->leftJoin('cs_orders as CsOrder', 'CsOrder.id', '=', 'OrderNote.order_id')
            ->where('OrderNote.parent_order_id', $parentid)
            ->select('CsOrder.increment_id', 'OrderNote.*')
            ->orderByDesc('OrderNote.id')
            ->paginate($perPage);

        $data = compact('history', 'orderid', 'parentid');

        if ($request->ajax() && $order !== null && $parent !== null) {
            return view('admin.order_notes._history', $data);
        }

        return view('admin.order_notes._loadhistory', $data);
    }

    /**
     * admin_loadnewnotepopup → loadnewnotepopup
     */
    public function loadnewnotepopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $orderid = trim($request->input('orderid', ''));
        $parentid = trim($request->input('parentid', ''));

        return view('admin.order_notes._loadnewnotepopup', compact('orderid', 'parentid'));
    }

    /**
     * admin_savenote → savenote
     */
    public function savenote(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = ['status' => false, 'message' => 'Sorry, related order not found.'];

        if ($request->ajax()) {
            $noteData = $request->input('OrderNote', []);
            $noteData['user_id'] = 0;
            $noteData['craeted'] = now()->toDateTimeString();

            DB::table('cs_order_notes')->insert($noteData);

            $return = ['status' => true, 'message' => 'Your record is saved successfully'];
        }

        return response()->json($return);
    }
}
