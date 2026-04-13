<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\OrderExtlog;
use App\Models\Legacy\CsOrder;
use Carbon\Carbon;

trait ValidateExtensionRequestTrait {

    public function validateExtensionRequest($return, $orderData, $validateattempts = true) {
        /*
        1. User gets 2 extension requests per rental cycle. 
            If both requests are used in a cycle, he gets one in the next cycle. 
            If that request is used, he gets 0 in the next cycle. 
            If one requests are used in a cycle, he gets two in the next cycle. 
            If a user has run out of extension requests, he can make a partial payment to extend till the next day (with a partial payment that is at least 2 days of rent +insurance)
        
        2. User cannot extend more than 7 days from begin date on the first extension OR 14 days from the begin date 
            if rental and insurance is paid (and EMF is failed or rental cycle simply hasn’t renewed and user wants to extend for the cycle about to renew); 
            more than 14 days from begin date on the second extension request in a cycle when rental or insurance are failed (and after the first request has expired)
        
        3. When they’ve run out of requests, have the response be “There are no more extension requests available. You can make a partial payment to enable the car up to 1 day. Please contact support in live chat with any questions.”
        */
        
        $extcount = OrderExtlog::where("cs_order_id", $orderData['id'])
            ->where('admin_count', 0)
            ->count();
            
        //Find previous booking extension request logic start here
        $parentId = !empty($orderData['parent_id']) ? $orderData['parent_id'] : $orderData['id'];
        
        $pastOrderDataIds = CsOrder::where('id', '<', $orderData['id'])
            ->where('renter_id', $orderData['renter_id'])
            ->where('parent_id', $parentId)
            ->where('status', 3)
            ->orderBy('id', 'desc')
            ->limit(2)
            ->pluck('id')
            ->toArray();
            
        $MAX_ALLOWED_BOOKING_EXT = env('MAX_ALLOWED_BOOKING_EXT', 2);
        
        if (!empty($pastOrderDataIds) && $validateattempts) {
            $pastOrderData = array_values($pastOrderDataIds); // filter only values
            
            if (count($pastOrderData) > 1) {
                $extCountPrev = OrderExtlog::where("cs_order_id", $pastOrderData[1])->where('admin_count', 0)->count();
                if ($extCountPrev > 1) {
                    $extCountPrev1 = OrderExtlog::where("cs_order_id", $pastOrderData[0])->where('admin_count', 0)->count();
                    $MAX_ALLOWED_BOOKING_EXT = $extCountPrev1 == 0 ? 2 : 0;
                }
                if ($extCountPrev <= 1) {
                    $extCountPrev1 = OrderExtlog::where("cs_order_id", $pastOrderData[0])->where('admin_count', 0)->count();
                    $MAX_ALLOWED_BOOKING_EXT = ($extCountPrev1 == 1 || $extCountPrev1 == 0) ? 2 : 1;
                }
            }
            if (count($pastOrderData) == 1) {
                $extCountPrev = OrderExtlog::where("cs_order_id", $pastOrderData[0])->where('admin_count', 0)->count();
                $MAX_ALLOWED_BOOKING_EXT = ($extCountPrev == 0 || $extCountPrev == 1) ? $MAX_ALLOWED_BOOKING_EXT : 1;
            }
        }
        
        if ($extcount >= $MAX_ALLOWED_BOOKING_EXT && $validateattempts) {
            return ['status' => false, 'message' => "There are no more extension requests available. You can make a partial payment to enable the car up to 1 day. Please contact support in live chat with any questions.", 'result' => []];
        }

        // if already extend time remaining
        if ($validateattempts) {
            $orderExtlog = OrderExtlog::where("cs_order_id", $orderData['id'])->orderBy('id', 'desc')->first();
            
            if ($extcount && !empty($orderExtlog) && ((Carbon::parse($orderExtlog->ext_date)->timestamp - time()) / 3600) >= 24) {
                $formattedDate = Carbon::parse($orderExtlog->ext_date)->timezone($orderData['timezone'])->format('m/d/Y h:i A');
                return ['status' => false, 'message' => sprintf("There is currently an active extension request till %s, with your account. Another extension request can be made if request credits are still available and no other extensions are still active in use. Please engage in live chat for any further assistance.", $formattedDate), 'result' => []];
            }
        }
        
        $return['cycle_extension'] = $extcount;
        $return['allowed_extension'] = $MAX_ALLOWED_BOOKING_EXT;
        $return['remaining_extension'] = ($return['allowed_extension'] - $extcount);
        
        $startDatetimeTS = strtotime($orderData['start_datetime']);
        $currentDate = date('Y-m-d');
        
        // If first attempt and start date is 7days earlier than current date
        if ($extcount == 0 && (date('Y-m-d', $startDatetimeTS + (7 * 86400)) >= $currentDate) && ($orderData['payment_status'] == 2 || $orderData['insu_status'] == 2)) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $return['allowed_max_date'] = date('Y-m-d', $startDatetimeTS + (7 * 86400)); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }
        // If first attempt and start date is 14days is not past, rent & insurance are paid
        if ($extcount == 0 && $orderData['payment_status'] != 2 && $orderData['insu_status'] != 2 && (($startDatetimeTS + (14 * 86400)) > time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $return['allowed_max_date'] = date('Y-m-d', $startDatetimeTS + (14 * 86400)); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }
        // If first attempt and start date is 14days equal current date, rent & insurance are paid
        if ($extcount == 0 && $orderData['payment_status'] != 2 && $orderData['insu_status'] != 2 && (date('Y-m-d', $startDatetimeTS + (14 * 86400)) == $currentDate)) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }

        // If first attempt and start date is 7days equal current date
        if ($extcount == 0 && (date('Y-m-d', $startDatetimeTS + (7 * 86400)) == $currentDate)) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }
        // only allowed if its first attempt and start date is 14 days past
        if ($extcount == 0 && (($startDatetimeTS + (14 * 86400)) < time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }
        // only allowed if its second attempt and start date is not past 7 days yet
        if ($extcount && (($startDatetimeTS + (7 * 86400)) > time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $timeDiff = ($startDatetimeTS + (7 * 86400) - time());
            $totalHours = abs($timeDiff / 3600);
            
            $days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $hours = $hours > 6 ? $hours : 0;
            $days = $days + ($hours > 0 ? 1 : 0);
            
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+$days day")); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }
        // only allowed if more than 1 attempt and start date is past 7 days & 14 days not passsed, along with rent & insurane are paid
        if ($extcount && $orderData['payment_status'] != 2 && $orderData['insu_status'] != 2 && (($startDatetimeTS + (7 * 86400)) < time()) && (($startDatetimeTS + (14 * 86400)) > time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $timeDiff = ($startDatetimeTS + (14 * 86400) - time());
            $totalHours = abs($timeDiff / 3600);
            
            $days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $hours = $hours > 6 ? $hours : 0;
            $days = $days + ($hours > 0 ? 1 : 0);
            
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+$days day")); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }
        // only allowed if its only second attempt and start date is past 7 days & 14 days not passsed
        if ($extcount == 1 && (($startDatetimeTS + (7 * 86400)) < time()) && (($startDatetimeTS + (14 * 86400)) > time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $timeDiff = ($startDatetimeTS + (14 * 86400) - time());
            $totalHours = abs($timeDiff / 3600);
            
            $days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $hours = $hours > 6 ? $hours : 0;
            $days = $days + ($hours > 0 ? 1 : 0);
            
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+$days day")); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }
        
        // in case of paying least amount then allow 1 day
        if (!$validateattempts && $extcount) {
            $orderExtLog = OrderExtlog::where("cs_order_id", $orderData['id'])->orderBy('id', 'desc')->first();
            
            if (!empty($orderExtLog) && ((strtotime($orderExtLog->ext_date) - time()) / 3600) >= 24) {
                $return['allowed_min_date'] = $orderExtLog->ext_date; // return in UTC
                $return['allowed_max_date'] = $orderExtLog->ext_date; // return in UTC
                return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
            } else {
                $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
                $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
                return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
            }
        }
        
        // special case when only 1 day allow
        if (!$validateattempts) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day")); // return in UTC
            return ['status' => true, 'message' => "Your request is processed successfully", 'result' => $return];
        }
        
        return ['status' => false, 'message' => "There are no more extension requests available. You can make a partial payment to enable the car up to 1 day. Please contact support in live chat with any questions.", 'result' => []];
    }
}
