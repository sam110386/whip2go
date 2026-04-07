<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\DepositTemplate;
use App\Models\Legacy\User;
use App\Services\Legacy\AgreementService;
use App\Services\Legacy\SignatureService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

trait AgreementTrait {

    public function _getagreementForCompletedBooking($CsLeaselists, $force = false) {
        $odometer = $CsLeaselists['CsOrder']['start_odometer'] ?? 0;
        $filename = ($CsLeaselists['CsOrder']['increment_id'] ?? $CsLeaselists['CsOrder']['id']) . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        if (!$force && file_exists($filefullname)) {
            return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/agreements/' . $filename)]];
        }

        // Parent order logic
        if (!empty($CsLeaselists['CsOrder']['parent_id'])) {
            $parentOrder = CsOrder::find($CsLeaselists['CsOrder']['parent_id']);
            if ($parentOrder) {
                $odometer = $parentOrder->start_odometer;
                $parentfilename = ($parentOrder->increment_id ?: $parentOrder->id) . '.pdf';
                if (!$force && file_exists(public_path('files/agreements/' . $parentfilename))) {
                    return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/agreements/' . $parentfilename)]];
                }
            }
        }

        // Data gathering... (simplified for migration logic)
        $vehicle = $this->prepareAgreementData($CsLeaselists, $odometer);

        $agreementService = new AgreementService();
        $agreementService->generateQuoteAgreementPdf($vehicle, $filefullname);

        return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/agreements/' . $filename)]];
    }

    public function _getPendingBookingAgreement($booking_id) {
        $booking = VehicleReservation::with(['Vehicle', 'User'])->find($booking_id);
        if (!$booking) return ['status' => false, 'message' => "Booking not found"];

        $filename = $booking_id . '.pdf';
        $filefullname = public_path('files/agreements/temp/' . $filename);

        $data = $this->preparePendingAgreementData($booking);
        
        $agreementService = new AgreementService();
        $agreementService->generateQuoteAgreementPdf($data, $filefullname);

        return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/agreements/temp/' . $filename), "filepath" => $filefullname]];
    }

    private function prepareAgreementData($CsLeaselists, $odometer) {
        // Implementation of data mapping for AgreementService
        // Includes Owner, Renter, Vehicle details and calculations
        return [
            'Owner' => $CsLeaselists['Owner'] ?? [],
            'Renter' => $CsLeaselists['Renter'] ?? [],
            'Vehicle' => $CsLeaselists['Vehicle'] ?? [],
            'odometer' => $odometer,
            'financing' => $CsLeaselists['financing'] ?? 1,
            // ... more fields as needed by agreement template
        ];
    }

    private function preparePendingAgreementData($booking) {
        // Similar to prepareAgreementData but for reservations
        return [
            'Owner' => $booking->User->toArray() ?? [],
            'Renter' => $booking->Renter->toArray() ?? [],
            'Vehicle' => $booking->Vehicle->toArray() ?? [],
            'id' => $booking->id,
            'financing' => $booking->financing ?? 1,
        ];
    }

    public function _generateCMMCard($renterid, $orderid = null) {
        $query = CsOrder::with(['Vehicle', 'Driver'])->where('renter_id', $renterid);
        if ($orderid) $query->where('id', $orderid);
        else $query->where('status', 1);

        $order = $query->first();
        if (!$order) return ['status' => false, 'message' => "Order not found"];

        $filename = 'cmm-service-card-' . ($order->parent_id ?: $order->id) . '.pdf';
        $filefullname = public_path('files/agreements/' . $filename);

        $data = [
            'year' => $order->Vehicle->year ?? '',
            'make' => $order->Vehicle->make ?? '',
            'model' => $order->Vehicle->model ?? '',
            'vin' => $order->Vehicle->vin_no ?? '',
            'ccm_auth_no' => $order->Vehicle->ccm_auth_no ?? 'XXXXXXXXXXXXXX',
            'driver_name' => ($order->Driver->first_name ?? '') . ' ' . ($order->Driver->last_name ?? ''),
            'logo' => '<img src="' . url('img/cmm-card-logo.png') . '" alt="logo"/>'
        ];

        $agreementService = new AgreementService();
        $agreementService->generateCMMCard($data, $filefullname);

        return ['status' => true, 'message' => "Success", 'result' => ['file' => url('files/agreements/' . $filename), "filepath" => $filefullname]];
    }
}
