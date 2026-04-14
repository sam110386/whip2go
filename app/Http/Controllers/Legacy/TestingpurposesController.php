<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestingpurposesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private static int $STATUSFAIL = 0;
    private static int $STATUSSUCCESS = 1;

    public function getRenterDetails($renterId)
    {
        $user = DB::table('users')->where('id', (int) $renterId)->first();
        return $user ? (array) $user : [];
    }

    private function pendingResponse(string $action)
    {
        return response()->json([
            'status' => false,
            'message' => "Testingpurposes::{$action} is pending migration.",
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function test1($id = '') { return $this->pendingResponse(__FUNCTION__); }
    public function test2() { return $this->pendingResponse(__FUNCTION__); }
    public function getUberBookings() { return $this->pendingResponse(__FUNCTION__); }
    public function test5($id = 20844) { return $this->pendingResponse(__FUNCTION__); }
    public function test6() { return $this->pendingResponse(__FUNCTION__); }
    public function test7() { return $this->pendingResponse(__FUNCTION__); }
    public function test9() { return $this->pendingResponse(__FUNCTION__); }
    public function NotifySaleToDealer($dealer, $payment, $msg) { return $this->pendingResponse(__FUNCTION__); }
    public function NotifyToDealer($userid, $total, $units) { return $this->pendingResponse(__FUNCTION__); }
    public function notifyPayment($id, $msgpart = 'Rental') { return $this->pendingResponse(__FUNCTION__); }
    public function notifyPaymentSuccessToRenter($transactionid, $amount, $order_id, $msgpart = 'rental') { return $this->pendingResponse(__FUNCTION__); }
    public function processpending() { return $this->pendingResponse(__FUNCTION__); }
    public function test8() { return $this->pendingResponse(__FUNCTION__); }
    public function DealerPartialReverse() { return $this->pendingResponse(__FUNCTION__); }
    public function rentRefundtotal() { return $this->pendingResponse(__FUNCTION__); }
    public function insuranceRefund() { return $this->pendingResponse(__FUNCTION__); }
    public function tollRefundtotal() { return $this->pendingResponse(__FUNCTION__); }
    public function emfRefundtotal() { return $this->pendingResponse(__FUNCTION__); }
    public function testt9() { return $this->pendingResponse(__FUNCTION__); }
    public function test10() { return $this->pendingResponse(__FUNCTION__); }
    public function test12() { return $this->pendingResponse(__FUNCTION__); }
    public function getVehicleQuote() { return $this->pendingResponse(__FUNCTION__); }
    private function _ptoQuote($dataValues, $vehicle) { return ['status' => self::$STATUSFAIL, 'message' => 'Pending migration', 'result' => []]; }

    private function saveVehicle($record, $user_id)
    {
        if (!is_array($record) || empty($record['vin'])) {
            return null;
        }

        $alreadyExists = DB::table('vehicles')
            ->where('vin_no', (string) $record['vin'])
            ->first();
        if (!empty($alreadyExists)) {
            return (int) $alreadyExists->id;
        }

        $insert = [
            'vehicle_unique_id' => '',
            'from_feed' => 0,
            'user_id' => (int) $user_id,
            'make' => (string) ($record['make'] ?? ''),
            'model' => (string) ($record['model'] ?? ''),
            'year' => (string) ($record['year'] ?? ''),
            'color' => (string) ($record['extcolor'] ?? ''),
            'vin_no' => (string) $record['vin'],
            'details' => (string) ($record['description'] ?? ''),
            'address' => trim(((string) ($record['dealeraddress'] ?? '')) . ' ' . ((string) ($record['dealercity'] ?? '')) . ' ' . ((string) ($record['dealerstate'] ?? '')) . ' ' . ((string) ($record['dealerzip'] ?? ''))),
            'lat' => (string) ($record['lat'] ?? ''),
            'lng' => (string) ($record['lng'] ?? ''),
            'program' => 2,
            'financing' => 4,
            'cab_type' => (string) ($record['standardbody'] ?? ''),
            'msrp' => (float) ($record['sellingprice'] ?? 0) > 1 ? (float) $record['sellingprice'] : 1500,
            'interior_color' => (string) ($record['intcolor'] ?? ''),
            'trim' => (string) ($record['trim'] ?? ''),
            'doors' => (string) ($record['doors'] ?? ''),
            'odometer' => (string) ($record['miles'] ?? ''),
            'allowed_miles' => 150,
            'subscription_allowed_miles' => 150,
            'stock_no' => (string) ($record['stock'] ?? ''),
            'engine' => (string) ($record['engliters'] ?? ''),
            'mpg_city' => (string) ($record['epacity'] ?? ''),
            'mpg_hwy' => (string) ($record['epahighway'] ?? ''),
            'vehicleCostInclRecon' => (float) ($record['cost'] ?? 0),
            'plate_number' => null,
            'equipment' => (string) ($record['equipment'] ?? ''),
            'fare_type' => 'D',
            'transmition_type' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $vehicleName =
            (!empty($insert['year']) ? substr((string) $insert['year'], -2) . '-' : '') .
            (!empty($insert['make']) ? str_replace(' ', '_', (string) $insert['make']) . '-' : '') .
            (!empty($insert['model']) ? str_replace(' ', '_', (string) $insert['model']) : '') .
            (!empty($insert['vin_no']) ? '-' . substr((string) $insert['vin_no'], -6) : '');
        $insert['vehicle_name'] = $vehicleName;

        $id = DB::table('vehicles')->insertGetId($insert);
        $uniqueNo = ($id < 999) ? ('1' . sprintf('%04d', $id)) : (string) $id;
        DB::table('vehicles')->where('id', (int) $id)->update(['vehicle_unique_id' => $uniqueNo]);

        if (!empty($record['images']) && is_array($record['images'])) {
            $order = 1;
            foreach ($record['images'] as $imgurl) {
                $this->addVehicleImage((int) $id, (string) $imgurl, $order++);
            }
        }

        return (int) $id;
    }

    private function addVehicleImage($vehicleid, $imgurl, $iorder)
    {
        $extension = pathinfo((string) $imgurl, PATHINFO_EXTENSION);
        if ($extension === '') {
            return;
        }

        DB::table('vehicle_images')->insert([
            'filename' => (string) $imgurl,
            'remote' => 1,
            'vehicle_id' => (int) $vehicleid,
            'iorder' => (int) $iorder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
