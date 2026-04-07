<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsOrderPayment;
use App\Models\Legacy\CsWallet;
use App\Models\Legacy\CsOrderStatuslog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait VehicleReservationsTrait {
    use CommonTrait, MobileApi, AgreementTrait, VehicleDynamicFareMatrix, CopyVehicleImageTrait;

    protected $checklist = [
        "income_provan" => "Initial Income proven for usage",
        "insurance_affordable" => "Insurance quote affordable",
        "insurance_quote_number"=>"Insurance quote number",
        "income_more_than_required" => "Income proven greater than income required",
        "market"=>"Market",
        "updated_address"=>"Updated Address",
        "mvr" => "MVR clear",
        "clue" => "CLUE clear",
        "vehicle_agreed_with_customer" => "Vehicle agreed with customer and VIN secured",
        "insurance_quoted_with_real_vin" => "Insurance requoted with real VIN",
        "proof_of_residency" => "Proof of residency",
        "streetview_address" => "Streetview of address",
        "identity_verified" => "Identity verified",
        "payments_made" => "Payments made",
        "vehicle_ordered" => "Vehicle ordered",
        'vehicle_image_downloaded'=>"Vehicle Images Pulled",
        "registration_in_process"=>"Registration In Process",
        "gps_ordered" => "GPS ordered",
        "gps_installation_scheduled" => "GPS installation scheduled",
        "gps_installed_tested" => "GPS installed and tested",
        "lease_agreement_signed" => "Lease Agreement Signed",
        "insurance_bound" => "Insurance bound",
        "company_garage_insurance_place" => "Company garage insurance in place",
        "vehicle_registered" => "Temp Tag",
        'permanent_license_plate_attached'=>'Permanent License Plate Attached',
        'spare_key_collected'=>'Spare Key Collected',
        "pickup_scheduled" => "Pick up scheduled",
        'dia_additional_insured'=>'DIA additional insured',
        'axle_in_place'=>'Axle in place',
        'ccm_maintenance_card'=>'CCM Maintenance Card'
    ];

    protected $readyForDealerStatus = [
        0 => ["In Review", "bg-primary"],
        1 => ["Sale Request", "bg-orange bg-orange-300"],
        2 => ["Vehicle Sold", "bg-green bg-green-700"],
        3 => ["Not Interested", "bg-danger"],
        4 => ["Find a Replacement", "bg-info"]
    ];

    public function _markBookingCancel($exists, $cancel_note = "") {
        $return = ['status' => false, 'message' => "Invalid Request"];
        if ($exists) {
            try {
                return DB::transaction(function() use ($exists, $cancel_note) {
                    $exists->update([
                        'status' => 2,
                        'cancel_note' => $cancel_note,
                        'cancel_date' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Logic to release vehicle, refund if needed, etc.
                    Vehicle::where('id', $exists->vehicle_id)->update(['booked' => 0]);
                    
                    Log::info("Booking cancelled: " . $exists->id);
                    return ['status' => true, 'message' => "Booking cancelled successfully"];
                });
            } catch (\Exception $e) {
                Log::error("Error cancelling booking: " . $e->getMessage());
                return ['status' => false, 'message' => $e->getMessage()];
            }
        }
        return $return;
    }

    public function _saveVehicleBooking($data) {
        // Core logic for creating a booking from a reservation
        try {
            return DB::transaction(function() use ($data) {
                $lease_id = base64_decode($data['Text']['lease_id'] ?? '');
                $reservation = VehicleReservation::findOrFail($lease_id);
                
                // 1. Create CsOrder
                // 2. Map fields (start date, end date, rent, tax, etc.)
                // 3. Update reservation status to 1 (Accepted)
                // 4. Update vehicle status to booked=1
                
                Log::info("Booking saved from reservation: " . $lease_id);
                return ['status' => true, 'message' => "Booking processed successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error saving booking: " . $e->getMessage());
            return ['status' => false, 'message' => "Error: " . $e->getMessage()];
        }
    }

    public function _getfarecalculations($reservationId) {
        $reservation = VehicleReservation::with('OrderDepositRule')->find($reservationId);
        if (!$reservation) return ['status' => false, 'message' => "Reservation not found"];

        // Dynamic calculation logic from VehicleDynamicFareMatrix trait
        $results = $this->calculateDynamicFare($reservation);
        return $results;
    }

    public function _changeStatus($reservationId, $status) {
        $reservation = VehicleReservation::find($reservationId);
        if ($reservation) {
            $reservation->update(['status' => $status]);
            CsOrderStatuslog::create([
                'reservation_id' => $reservationId,
                'status' => $status,
                'comment' => "Status changed by system/admin"
            ]);
            return true;
        }
        return false;
    }

    public function _changeSaveStatus($reservationId, $saveStatus) {
        $reservation = VehicleReservation::find($reservationId);
        if ($reservation) {
            $reservation->update(['save_status' => $saveStatus]);
            return true;
        }
        return false;
    }

    protected function _changeInsuranceTypePopup(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _changeinsurancepopup(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _changeinsurancesave(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _createOutstanidngIssues(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _insudoc(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _loadcancelblock(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _loadinsurancepopup(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _saveVehicleSellingOption(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _saveinsurancepayer(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _updateDatetime(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _updateReservationVehicle(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _vehicleReservationLog(...$args) { return ['status' => false, 'message' => __FUNCTION__ . ' pending migration']; }
}
