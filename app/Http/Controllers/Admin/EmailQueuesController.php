<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\User;
use App\Services\Legacy\EmailQueueService;

/**
 * Migrated from: app/Plugin/EmailQueue/Controller/EmailQueuesController.php
 */
class EmailQueuesController extends LegacyAppController
{
    public function paymentReceipt(string $paymentid)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = ['status' => false, 'message' => 'Sorry, something went wrong'];
        $paymentid = $this->decodeId($paymentid);

        if (empty($paymentid)) {
            return response()->json($return);
        }

        $orderData = CsOrderPayment::with('csOrder:id,increment_id,renter_id,start_datetime,end_datetime,timezone,vehicle_name')
            ->where('id', $paymentid)
            ->first();

        if (!$orderData || !$orderData->csOrder) {
            return response()->json($return);
        }

        $renter = User::where('id', $orderData->csOrder->renter_id)
            ->select('first_name', 'last_name', 'email', 'address', 'city', 'state', 'zip')
            ->first();

        $paymentTypes = $this->commonService->getPayoutTypeValue(true);
        $msg = 'Payment was successful for the ' . ($paymentTypes[$orderData->type] ?? 'Fee') . ' charges of your DriveItAway order ';

        $service = new EmailQueueService();
        $resp = $service->generateReceipt($orderData, $renter, 'card', $msg);

        if (!$resp['status']) {
            return response()->json($resp);
        }

        return response()->download($resp['filefullname']);
    }
}
