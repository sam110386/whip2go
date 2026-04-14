<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migrated from: app/Plugin/GTS/Controller/GtsApiController.php
 *
 * GTS toll-management API: fleet pull, rental lookup, charge requests, invoice upload.
 */
class GtsApiController extends Controller
{
    private string $webhookSecret = 'GJHGJHGHG788768UYT';

    private array $canada = [
        'NL','PE','NS','NB','QC','ON','MB','SK','AB','BC','YT','NT','NU',
    ];

    public function __construct()
    {
    }

    /**
     * Middleware-style auth check — call at the top of each action
     * or register as actual middleware on the route group.
     */
    private function verifySignature(Request $request): ?JsonResponse
    {
        $signature = $request->header('Dia-Signature', '');
        if ($signature !== $this->webhookSecret) {
            return response()->json([
                'status'  => false,
                'message' => 'Sorry, login signature dont match',
            ], 400);
        }
        return null;
    }

    private function getPostData(Request $request): string
    {
        $raw = $request->getContent();
        Log::channel('daily')->info('GTS', [
            'url'  => $request->fullUrl(),
            'body' => $raw,
        ]);
        return $raw ?: '';
    }

    public function pullFleets(Request $request): JsonResponse
    {
        if ($err = $this->verifySignature($request)) return $err;

        $postData = $this->getPostData($request);
        $data = json_decode($postData);

        if (empty($postData) || empty($data->clientid)) {
            return response()->json([
                'clientid'    => $data->clientid ?? null,
                'requestid'   => $data->requestid ?? null,
                'numvehicles' => 0,
                'message'     => 'Request body is empty, please pass correct clientid',
                'status'      => 0,
            ]);
        }

        $orders = DB::table('cs_orders as CsOrder')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->where('CsOrder.status', 1)
            ->select(
                'CsOrder.id', 'CsOrder.vehicle_id',
                'Vehicle.vin_no', 'Vehicle.model', 'Vehicle.year', 'Vehicle.id as vehicle_pk',
                'Vehicle.plate_number', 'Vehicle.color', 'Vehicle.trim', 'Vehicle.registered_state'
            )
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'clientid'    => $data->clientid,
                'requestid'   => $data->requestid,
                'numvehicles' => 0,
                'message'     => 'Sorry, no active order found.',
                'status'      => 0,
                'result'      => [],
            ]);
        }

        $vehicles = $orders->map(fn ($o) => [
            'licensenumber'  => $o->plate_number ?: '',
            'licensestate'   => $o->registered_state,
            'model'          => $o->model,
            'color'          => $o->color,
            'year'           => $o->year,
            'owninglocation' => 'BK1',
            'vehicleclass'   => $o->trim,
            'status'         => 'On Rent',
            'unitnumber'     => $o->vehicle_pk,
            'vin'            => $o->vin_no,
        ])->values()->all();

        return response()->json([
            'clientid'    => $data->clientid,
            'requestid'   => $data->requestid,
            'numvehicles' => count($vehicles),
            'message'     => '',
            'vehicles'    => $vehicles,
        ]);
    }

    public function rentalLookUp(Request $request): JsonResponse
    {
        if ($err = $this->verifySignature($request)) return $err;

        $postData = $this->getPostData($request);
        $data = json_decode($postData);

        if (empty($postData) || empty($data->clientid) || empty($data->requests)) {
            return response()->json([
                'clientid'  => $data->clientid ?? null,
                'requestid' => $data->requestid ?? null,
                'requests'  => $data->requests ?? null,
                'message'   => 'Request body is empty, please pass correct data',
                'responses' => [],
            ]);
        }

        $responses = [];
        foreach ($data->requests as $req) {
            $datetime = \DateTime::createFromFormat('Ymd-His', $req->txndatetime);
            if (!$datetime) continue;
            $dt = $datetime->format('Y-m-d H:i:s');

            $order = DB::table('cs_orders as CsOrder')
                ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
                ->leftJoin('users as Driver', 'Driver.id', '=', 'CsOrder.renter_id')
                ->whereRaw("CsOrder.start_datetime <= ? AND CsOrder.end_datetime >= ?", [$dt, $dt])
                ->where('Vehicle.plate_number', $req->licensenumber)
                ->select(
                    'CsOrder.id', 'CsOrder.pickup_address', 'CsOrder.increment_id',
                    'CsOrder.start_datetime', 'CsOrder.end_datetime',
                    'Vehicle.id as vehicle_id', 'Vehicle.plate_number', 'Vehicle.trim',
                    'Driver.first_name', 'Driver.last_name', 'Driver.address', 'Driver.city',
                    'Driver.state', 'Driver.zip', 'Driver.contact_number', 'Driver.email',
                    'Driver.licence_state', 'Driver.licence_number', 'Driver.dob'
                )
                ->first();

            if (empty($order)) continue;

            $responses[] = [
                'id'                    => $req->id,
                'txndatetime'           => $req->txndatetime,
                'licensenumber'         => $req->licensenumber,
                'licensestate'          => $req->licensestate,
                'unitnumber'            => $order->vehicle_id,
                'rastatus'              => 'ACTIVE',
                'ranumber'              => $order->id,
                'rastartdatetime'       => date('Ymd-His', strtotime($order->start_datetime)),
                'raenddatetime'         => date('Ymd-His', strtotime($order->end_datetime)),
                'firstname'             => $order->first_name,
                'lastname'              => $order->last_name,
                'address1'              => $order->address,
                'address2'              => '',
                'city'                  => $order->city,
                'state'                 => $order->state,
                'zip'                   => $order->zip,
                'country'               => in_array($order->state, $this->canada) ? 'CA' : 'US',
                'phone'                 => $order->contact_number,
                'email'                 => $order->email,
                'companyname'           => 'DIA',
                'companycode'           => 'DIA',
                'ponumber'              => $order->increment_id,
                'driverslicensestate'   => $order->licence_state,
                'driverslicensenumber'  => decrypt($order->licence_number),
                'dateofbirth'           => date('mdY', strtotime($order->dob)),
                'pickuplocation'        => $order->pickup_address,
                'vehicleclass'          => $order->trim,
                'optionalservicelist '  => 'TOLLPASS,CDW,GPS',
            ];
        }

        return response()->json([
            'clientid'    => $data->clientid,
            'requestid'   => $data->requestid,
            'numrequests' => $data->numrequests,
            'responses'   => $responses,
        ]);
    }

    public function chargeRequest(Request $request): JsonResponse
    {
        if ($err = $this->verifySignature($request)) return $err;

        $postData = $this->getPostData($request);
        $data = json_decode($postData);

        if (empty($postData) || empty($data->clientid) || empty($data->ranumber)) {
            return response()->json([
                'clientid'     => $data->clientid ?? null,
                'requestid'    => $data->requestid ?? null,
                'ranumber'     => $data->ranumber ?? null,
                'message'      => 'Request body is empty, please pass correct clientid',
                'chargeresult' => 'DECLINED',
            ]);
        }

        $orderObj = DB::table('cs_orders as CsOrder')
            ->where('CsOrder.id', $data->ranumber)
            ->select('CsOrder.id', 'CsOrder.renter_id', 'CsOrder.user_id', 'CsOrder.cc_token_id',
                     'CsOrder.toll', 'CsOrder.pending_toll', 'CsOrder.toll_status')
            ->first();

        $response = [
            'requestid'      => $data->requestid,
            'clientid'       => $data->clientid,
            'ranumber'       => $data->ranumber,
            'invoicenumber'  => $data->invoicenumber,
            'tollamount'     => $data->tollamount,
            'tollchargetype' => $data->tollchargetype,
            'feeamount'      => $data->feeamount,
            'feechargetype'  => $data->feechargetype,
            'salestax'       => 0,
            'chargeresult'   => 'DECLINED',
        ];

        if (empty($orderObj)) {
            $response['chargeresult'] = 'NOCARDATA';
            return response()->json($response);
        }

        if ($data->tollamount == 0) {
            $response['chargeresult'] = 'DECLINED';
            return response()->json($response);
        }

        $this->saveAmountAsToll($data, $orderObj);
        $response['chargeresult'] = 'APPROVED';

        if (!empty($data->content)) {
            $dir = public_path('files/GTSInvoice');
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $filename = $dir . '/' . $data->ranumber . '_' . $data->invoicenumber . '.pdf';
            @file_put_contents(
                $filename,
                base64_decode(preg_replace('#data:application/[^;]+;base64,#i', '', $data->content))
            );
        }

        return response()->json($response);
    }

    private function saveAmountAsToll(object $data, object $orderObj): void
    {
        $totalAmount = $data->tollamount + (float) $data->feeamount;

        DB::table('cs_user_balance_logs')->insert([
            'user_id'  => $orderObj->renter_id,
            'credit'   => $data->tollamount,
            'type'     => 8,
            'owner_id' => $orderObj->user_id,
            'note'     => 'Toll From GTS API',
        ]);

        DB::table('cs_user_balances')->insert([
            'owner_id'         => $orderObj->user_id,
            'user_id'          => $orderObj->renter_id,
            'note'             => 'Toll From GTS API',
            'credit'           => $totalAmount,
            'balance'          => $totalAmount,
            'debit'            => 0,
            'type'             => 8,
            'chargetype'       => 'lumpsum',
            'installment_type' => 'daily',
            'installment_day'  => null,
            'installment'      => 0,
            'created'          => now()->toDateTimeString(),
        ]);
    }

    public function uploadInvoice(Request $request): JsonResponse
    {
        if ($err = $this->verifySignature($request)) return $err;

        $postData = $this->getPostData($request);
        $data = json_decode($postData);

        if (empty($data->clientid) || empty($data->ranumber) || empty($data->invoicenumber) || empty($data->content)) {
            return response()->json([
                'status'  => false,
                'message' => 'Sorry, required data is missing',
            ], 400);
        }

        $order = DB::table('cs_orders')
            ->where('id', $data->ranumber)
            ->select('id', 'renter_id')
            ->first();

        if (empty($order)) {
            return response()->json([
                'status'  => false,
                'message' => "Sorry, {$data->ranumber} doesnt exists",
            ], 400);
        }

        if (!empty($data->content)) {
            $dir = public_path('files/GTSInvoice');
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $filename = $dir . '/' . $data->ranumber . '_' . $data->invoicenumber . '.pdf';
            @file_put_contents(
                $filename,
                base64_decode(preg_replace('#data:application/[^;]+;base64,#i', '', $data->content))
            );
        }

        return response()->json([
            'requestid'     => $data->requestid,
            'clientid'      => $data->clientid,
            'ranumber'      => $data->ranumber,
            'invoicenumber' => $data->invoicenumber,
            'uploadresult'  => 'SUCCESS',
        ]);
    }
}
