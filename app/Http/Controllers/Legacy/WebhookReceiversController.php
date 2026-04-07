<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\User;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\VehicleReservation;

class WebhookReceiversController extends LegacyAppController
{
    private $_reportStatsu;
    
    public function CheckrRecieve(Request $request)
    {
        $postDataRaw = $request->getContent();
        
        // Log the raw checkr data
        Log::channel('checkr')->info("\n" . date('Y-m-d H:i:s') . '=' . $postDataRaw);
        if(!is_dir(storage_path('logs'))) mkdir(storage_path('logs'), 0777, true);
        file_put_contents(storage_path('logs/checkr_' . date('Y-m-d') . '.log'), "\n" . date('Y-m-d H:i:s') . '=' . $postDataRaw, FILE_APPEND);

        if (!empty($postDataRaw)) {
            $postData = json_decode($postDataRaw, true);

            // if report request is added, Status: 'pending'
            if (isset($postData['type']) && $postData['type'] == 'report.created') {
                $candidateId = $postData['data']['object']['candidate_id'];
                $reportId = $postData['data']['object']['id'];
                
                $motor_vehicle_report_id = $postData['data']['object']['motor_vehicle_report_id'] ?? 
                    (!empty($postData['data']['object']['international_motor_vehicle_report_ids']) ? $postData['data']['object']['international_motor_vehicle_report_ids'][0] : '');
                
                DB::table('user_reports')
                    ->where('checkr_id', $candidateId)
                    ->update([
                        'checkr_reportid' => $reportId,
                        'motor_vehicle_report_id' => $motor_vehicle_report_id,
                        'status' => 1
                    ]);
            }

            // if report request is completed, Status: 'clear', 'consider'
            if (isset($postData['type']) && ($postData['type'] == 'report.completed' || ($postData['type'] == 'report.engaged' && $postData['type'] == 'report.clear'))) {
                $candidateId = $postData['data']['object']['candidate_id'];
                $reportId = $postData['data']['object']['id'];
                $motor_vehicle_report_id = !empty($postData['data']['object']['motor_vehicle_report_id']) ? $postData['data']['object']['motor_vehicle_report_id'] : '';
                $international_motor_vehicle_report_id = !empty($postData['data']['object']['international_motor_vehicle_report_ids']) ? $postData['data']['object']['international_motor_vehicle_report_ids'][0] : '';

                // Placeholder for CheckrApi calls
                $reportData = "{}"; // Simulate reportData from api
                
                DB::table('user_reports')
                    ->where('checkr_id', $candidateId)
                    ->update(['status' => 2, 'report' => $reportData]);

                $userReport = DB::table('user_reports')->where('checkr_id', $candidateId)->first();

                if (!empty($userReport)) {
                    $this->_reportStatsu = $reportStatsu = $postData['data']['object']['result'];
                    
                    if (strtolower($reportStatsu) == 'clear') {
                        DB::table('users')->where('id', $userReport->user_id)->update(['checkr_status' => 1]);
                        $this->activateBooking($userReport->user_id, 1);
                    } elseif (strtolower($reportStatsu) == 'consider') {
                        DB::table('users')->where('id', $userReport->user_id)->update(['checkr_status' => 3]);
                        $this->cancelBooking($userReport->user_id, 3);
                    } elseif (strtolower($reportStatsu) != 'pending') {
                        DB::table('users')->where('id', $userReport->user_id)->update(['checkr_status' => 4]);
                        $this->cancelBooking($userReport->user_id, 4);
                    }
                    
                    $this->notifyToAdmin($userReport->user_id, $reportStatsu);
                }
            }

            // if report request is Suspended
            if (isset($postData['type']) && ($postData['type'] == 'report.suspended' || ($postData['type'] == 'report.engaged' && $postData['type'] == 'report.suspended' && $postData['type'] == 'report.canceled'))) {
                $candidateId = $postData['data']['object']['candidate_id'];
                DB::table('user_reports')->where('checkr_id', $candidateId)->update(['status' => 3]);
                
                $userReport = DB::table('user_reports')->where('checkr_id', $candidateId)->first();
                if (!empty($userReport)) {
                    DB::table('users')->where('id', $userReport->user_id)->update(['checkr_status' => 4]);
                    $this->cancelBooking($userReport->user_id, 4);
                }
            }

            // if report request is Disputed
            if (isset($postData['type']) && $postData['type'] == 'report.disputed') {
                $candidateId = $postData['data']['object']['candidate_id'];
                DB::table('user_reports')->where('checkr_id', $candidateId)->update(['status' => 4]);
                
                $userReport = DB::table('user_reports')->where('checkr_id', $candidateId)->first();
                if (!empty($userReport)) {
                    DB::table('users')->where('id', $userReport->user_id)->update(['checkr_status' => 4]);
                    $this->cancelBooking($userReport->user_id, 4);
                }
            }
        }

        return response('finished');
    }

    protected function cancelBooking($renterid, $checkr_status)
    {
        $this->cancelReservation($renterid, $checkr_status);
        return;
    }

    protected function cancelReservation($renterid, $checkr_status)
    {
        $reservation = VehicleReservation::where('renter_id', $renterid)
            ->where('status', 0)
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($reservation)) return;

        $reservation->checkr_status = $checkr_status;
        $reservation->save();

        DB::table('vehicle_reservation_logs')->insert([
            'user_id' => 0,
            'reservation_id' => $reservation->id,
            'status' => 10,
            'note' => "Status changed by MVR webhook, MVR status=" . $this->_reportStatsu,
            'created' => now()
        ]);
    }

    protected function activateBooking($renterid, $checkr_status)
    {
        $this->activateVehicleReservation($renterid, $checkr_status);
        return;
    }

    protected function activateVehicleReservation($renterid, $checkr_status)
    {
        $reservation = VehicleReservation::where('renter_id', $renterid)
            ->where('status', 0)
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($reservation)) return;

        $reservation->checkr_status = $checkr_status;
        $reservation->save();

        DB::table('vehicle_reservation_logs')->insert([
            'user_id' => 0,
            'reservation_id' => $reservation->id,
            'status' => 10,
            'note' => "Status changed by MVR webhook, MVR status=" . $this->_reportStatsu,
            'created' => now()
        ]);
    }

    protected function notifyToAdmin($renterid, $status)
    {
        $order = CsOrder::where('renter_id', $renterid)
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($order)) return;

        $msg = "One of your booking (" . $order->increment_id . ") is updated in your account due to checkr API status (" . $status . ") update.";
        $email = 'adam01@gmail.com';
        $subject = "Booking #" . $order->increment_id . " ChekrApi Update";
        
        // Placeholder for Emailnotify Component
        // Emailnotify::sendCustomEmail($msg, $email, $subject);
    }

    public function geotab(Request $request)
    {
        $postData = $request->getContent();
        if(!is_dir(storage_path('logs'))) mkdir(storage_path('logs'), 0777, true);
        file_put_contents(storage_path('logs/geotab_webhook' . date('Y-m-d') . '.log'), "\n" . date('Y-m-d H:i:s') . '=' . $postData, FILE_APPEND);
        return response('OK');
    }

    public function intercomReciever(Request $request)
    {
        $authorization = $request->header('Authorization', '');
        $auth_token = trim(str_replace('Basic', '', $authorization));
        
        $postDataRaw = $request->getContent();
        $postData = json_decode($postDataRaw, true);

        if ($auth_token != 'GJHGJHGHG788768UYT' || empty($postData) || (!isset($postData['form_id']) || $postData['form_id'] != 1)) {
            return response('sorry, wrong attempt');
        }

        $userData = [];
        $userData['name'] = isset($postData['8.3']) ? $postData['8.3'] . ' ' . ($postData['8.6'] ?? '') : '';
        $userData['email'] = $postData['3'] ?? '';
        $userData['phone'] = $postData['4'] ?? '';
        $userData['address'] = $postData['10'] ?? '';
        $userData['city'] = $postData['11'] ?? '';
        $userData['state'] = $postData['14'] ?? '';
        $userData['postal'] = $postData['13'] ?? '';
        $userData['usertype'] = (isset($postData['9']) && $postData['9'] == '0121U000000EnwR') ? "Dealer" : 'Driver';
        $msg = $postData['7'] ?? '';

        if (empty($userData['email']) || empty($userData['phone'])) {
            return response('sorry, wrong attempt');
        }

        // Placeholder for Intercom Lib
        // $intercomObj = new \App\Lib\Intercom();
        // $intercomObj->syncForWebhook($userData, $msg);

        return response('finished');
    }

    public function intercomLeadReciever(Request $request)
    {
        $authorization = $request->header('Authorization', '');
        $auth_token = trim(str_replace('Basic', '', $authorization));
        
        $postDataRaw = $request->getContent();
        $postData = json_decode($postDataRaw, true);

        if ($auth_token != 'GJHGJHGHG788768UYT' || empty($postData)) {
            return response()->json(['status' => false, "message" => "sorry, wrong attempt"]);
        }

        if (!isset($postData['email']) || empty($postData['email'])) {
            return response()->json(['status' => false, "message" => "sorry, wrong attempt"]);
        }

        // Placeholder for Intercom Lib Search & Create
        // $intercomObj = new \App\Lib\Intercom();
        /*
        $resp = $intercomObj->searchLead([...]);
        if(isset($resp->total_count) && $resp->total_count > 0){
            return response()->json(['status'=>true,"message"=>"already exists"]);
        }
        $intercomObj->createLead(["email"=>$postData['email'],"name"=>$postData['name'],"type"=>'Lead',"role"=>'lead']);
        */

        return response()->json(['status' => true, "message" => "success"]);
    }
}
