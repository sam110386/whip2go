<?php

namespace App\Http\Controllers\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Traits/ValidateExtensionRequestTrait.php
 *
 * Validates booking extension requests based on complex business rules about
 * cycle limits, dates, and payment statuses.
 */
trait ValidateExtensionRequestTrait
{
    public function validateExtensionRequest($return, $orderData, $validateattempts = true)
    {
        $extcount = DB::table('order_extlogs')
            ->where('cs_order_id', $orderData['CsOrder']['id'])
            ->where('admin_count', 0)
            ->count();

        $pastOrderData = DB::table('cs_orders')
            ->where('id', '<', $orderData['CsOrder']['id'])
            ->where('renter_id', $orderData['CsOrder']['renter_id'])
            ->where('parent_id', !empty($orderData['CsOrder']['parent_id']) ? $orderData['CsOrder']['parent_id'] : $orderData['CsOrder']['id'])
            ->where('status', 3)
            ->orderBy('id', 'DESC')
            ->limit(2)
            ->pluck('id')
            ->toArray();

        $MAX_ALLOWED_BOOKING_EXT = config('app.max_allowed_booking_ext', 2);

        if (!empty($pastOrderData) && $validateattempts) {
            $pastOrderData = array_values($pastOrderData);
            if (count($pastOrderData) > 1) {
                $extCountPrev = DB::table('order_extlogs')
                    ->where('cs_order_id', $pastOrderData[1])
                    ->where('admin_count', 0)
                    ->count();
                if ($extCountPrev > 1) {
                    $extCountPrev1 = DB::table('order_extlogs')
                        ->where('cs_order_id', $pastOrderData[0])
                        ->where('admin_count', 0)
                        ->count();
                    $MAX_ALLOWED_BOOKING_EXT = $extCountPrev1 == 0 ? 2 : 0;
                }
                if ($extCountPrev <= 1) {
                    $extCountPrev1 = DB::table('order_extlogs')
                        ->where('cs_order_id', $pastOrderData[0])
                        ->where('admin_count', 0)
                        ->count();
                    $MAX_ALLOWED_BOOKING_EXT = ($extCountPrev1 == 1 || $extCountPrev1 == 0) ? 2 : 1;
                }
            }
            if (count($pastOrderData) == 1) {
                $extCountPrev = DB::table('order_extlogs')
                    ->where('cs_order_id', $pastOrderData[0])
                    ->where('admin_count', 0)
                    ->count();
                $MAX_ALLOWED_BOOKING_EXT = ($extCountPrev == 0 || $extCountPrev == 1) ? $MAX_ALLOWED_BOOKING_EXT : 1;
            }
        }

        if ($extcount >= $MAX_ALLOWED_BOOKING_EXT && $validateattempts) {
            return ['status' => false, 'message' => __("There are no more extension requests available. You can make a partial payment to enable the car up to 1 day. Please contact support in live chat with any questions."), 'result' => []];
        }

        if ($validateattempts) {
            $OrderExtlog = DB::table('order_extlogs')
                ->where('cs_order_id', $orderData['CsOrder']['id'])
                ->orderBy('id', 'DESC')
                ->first();

            if ($extcount && !empty($OrderExtlog) && (((strtotime($OrderExtlog->ext_date) - time()) / 3600) >= 24)) {
                $timezone = $orderData['CsOrder']['timezone'] ?? 'UTC';
                $formattedDate = Carbon::parse($OrderExtlog->ext_date)->setTimezone($timezone)->format('m/d/Y h:i A');
                return ['status' => false, 'message' => __("There is currently an active extension request till %s, with your account. Another extenion request can be made if request credits are still available and no other extensions are still active in use. Please engage in live chat for any further assistance.", $formattedDate), 'result' => []];
            }
        }

        $return['cycle_extension'] = $extcount;
        $return['allowed_extension'] = $MAX_ALLOWED_BOOKING_EXT;
        $return['remaining_extension'] = ($return['allowed_extension'] - $extcount);

        if ($extcount == 0 && (date('Y-m-d', (strtotime($orderData['CsOrder']['start_datetime']) + (7 * 86400))) >= date('Y-m-d')) && ($orderData['CsOrder']['payment_status'] == 2 || $orderData['CsOrder']['insu_status'] == 2)) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $return['allowed_max_date'] = date('Y-m-d', (strtotime($orderData['CsOrder']['start_datetime']) + (7 * 86400)));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        if ($extcount == 0 && $orderData['CsOrder']['payment_status'] != 2 && $orderData['CsOrder']['insu_status'] != 2 && ((strtotime($orderData['CsOrder']['start_datetime']) + (14 * 86400)) > time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $return['allowed_max_date'] = date('Y-m-d', (strtotime($orderData['CsOrder']['start_datetime']) + (14 * 86400)));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        if ($extcount == 0 && $orderData['CsOrder']['payment_status'] != 2 && $orderData['CsOrder']['insu_status'] != 2 && (date('Y-m-d', (strtotime($orderData['CsOrder']['start_datetime']) + (14 * 86400))) == date('Y-m-d'))) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day"));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        if ($extcount == 0 && (date('Y-m-d', (strtotime($orderData['CsOrder']['start_datetime']) + (7 * 86400))) == date('Y-m-d'))) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day"));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        if ($extcount == 0 && ((strtotime($orderData['CsOrder']['start_datetime']) + (14 * 86400)) < time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day"));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        if ($extcount && ((strtotime($orderData['CsOrder']['start_datetime']) + (7 * 86400)) > time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $timeDiff = (strtotime($orderData['CsOrder']['start_datetime']) + (7 * 86400) - time());
            $totalHours = abs($timeDiff / 3600);
            $days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $hours = $hours > 6 ? $hours : 0;
            $days = $days + ($hours > 0 ? 1 : 0);
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+$days day"));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        if ($extcount && $orderData['CsOrder']['payment_status'] != 2 && $orderData['CsOrder']['insu_status'] != 2 && ((strtotime($orderData['CsOrder']['start_datetime']) + (7 * 86400)) < time()) && ((strtotime($orderData['CsOrder']['start_datetime']) + (14 * 86400)) > time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $timeDiff = (strtotime($orderData['CsOrder']['start_datetime']) + (14 * 86400) - time());
            $totalHours = abs($timeDiff / 3600);
            $days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $hours = $hours > 6 ? $hours : 0;
            $days = $days + ($hours > 0 ? 1 : 0);
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+$days day"));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        if ($extcount == 1 && ((strtotime($orderData['CsOrder']['start_datetime']) + (7 * 86400)) < time()) && ((strtotime($orderData['CsOrder']['start_datetime']) + (14 * 86400)) > time())) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $timeDiff = (strtotime($orderData['CsOrder']['start_datetime']) + (14 * 86400) - time());
            $totalHours = abs($timeDiff / 3600);
            $days = $totalHours < 24 ? 1 : floor($totalHours / 24);
            $hours = $totalHours < 24 ? 0 : $totalHours % 24;
            $hours = $hours > 6 ? $hours : 0;
            $days = $days + ($hours > 0 ? 1 : 0);
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+$days day"));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        if (!$validateattempts && $extcount) {
            $OrderExtlog = DB::table('order_extlogs')
                ->where('cs_order_id', $orderData['CsOrder']['id'])
                ->orderBy('id', 'DESC')
                ->first();

            if (!empty($OrderExtlog) && ((strtotime($OrderExtlog->ext_date) - time()) / 3600) >= 24) {
                $return['allowed_min_date'] = $OrderExtlog->ext_date;
                $return['allowed_max_date'] = $OrderExtlog->ext_date;
                return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
            } else {
                $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
                $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day"));
                return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
            }
        }

        if (!$validateattempts) {
            $return['allowed_min_date'] = date('Y-m-d', strtotime("+1 day"));
            $return['allowed_max_date'] = date('Y-m-d', strtotime("+1 day"));
            return ['status' => true, 'message' => __("Your request is processed successfully"), 'result' => $return];
        }

        return ['status' => false, 'message' => __("There are no more extension requests available. You can make a partial payment to enable the car up to 1 day. Please contact support in live chat with any questions."), 'result' => []];
    }
}
