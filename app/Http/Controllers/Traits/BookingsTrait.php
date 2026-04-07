<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\User;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\CsOrderPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait BookingsTrait {
    use CommonTrait, MobileApi, AgreementTrait, VehicleDynamicFareMatrix, InsuranceToken, ActiveBookingTotalPending, PasstimeActivateVehicle;

    public function _editsave($data) {
        try {
            return DB::transaction(function() use ($data) {
                $orderId = $data['Text']['id'] ?? null;
                $vehicleId = $data['Text']['vehicle_id'] ?? null;
                
                if (!$orderId || !$vehicleId) {
                    return ['status' => false, 'message' => "Invalid inputs"];
                }

                $csOrder = CsOrder::findOrFail($orderId);
                $vehicle = Vehicle::findOrFail($vehicleId);

                $endTimeStr = str_replace('AM', ' AM', str_replace('PM', ' PM', $data['Text']['end_time']));
                $endDate = $data['daterangeto'];
                $endDateTime = Carbon::parse($endDate . ' ' . $endTimeStr);

                // Logic to match start time if > 24h
                $startDateTime = Carbon::parse($csOrder->start_datetime);
                if ($endDateTime->diffInHours($startDateTime) >= 24) {
                    $endDateTime = Carbon::parse($endDate . ' ' . $startDateTime->format('H:i:s'));
                }

                $csOrder->update([
                    'pickup_address' => trim($data['Text']['location'] ?? $csOrder->pickup_address),
                    'end_datetime' => $endDateTime->toDateTimeString(),
                    'vehicle_id' => $vehicleId,
                    'vehicle_name' => $vehicle->vehicle_name,
                    'user_id' => $vehicle->user_id
                ]);

                if ($vehicleId != $csOrder->getOriginal('vehicle_id')) {
                    Vehicle::where('id', $vehicleId)->update(['booked' => 1]);
                    Vehicle::where('id', $csOrder->getOriginal('vehicle_id'))->update(['booked' => 0]);
                    
                    // Reset Passtime
                    $this->ActivatePasstimeVehicle($vehicleId);
                    
                    // Update rental fee if requested
                    if (isset($data['Text']['updatebooking'])) {
                        $parentId = $csOrder->parent_id ?: $csOrder->id;
                        OrderDepositRule::where('cs_order_id', $parentId)->update(['rental' => $vehicle->day_rent]);
                    }
                }

                return ['status' => true, 'message' => "Your changes saved successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error in _editsave: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function _startBooking($csOrder) {
        // Logic for activating booking, payments, etc.
        try {
            return DB::transaction(function() use ($csOrder) {
                $orderId = $csOrder->id;
                $csOrder->update([
                    'status' => 1,
                    'start_timing' => now()
                ]);

                $this->ActivatePasstimeVehicle($csOrder->vehicle_id);
                
                // Placeholder for PaymentProcessor::ChargeAmount
                Log::info("Charging for booking $orderId");
                
                return ['status' => true, 'message' => "Booking started successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error in _startBooking: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function _cancelBooking($csOrder, $cancelNote, $cancellationFee) {
        try {
            return DB::transaction(function() use ($csOrder, $cancelNote, $cancellationFee) {
                $csOrder->update([
                    'status' => 2,
                    'cancel_note' => $cancelNote,
                    'cancellation_fee' => $cancellationFee,
                    'rent' => 0,
                    'tax' => 0
                ]);

                Vehicle::where('id', $csOrder->vehicle_id)->update(['booked' => 0]);
                
                // Placeholder for PaymentProcessor::ChargeCancelAmount
                Log::info("Charging cancellation fee for booking " . $csOrder->id);

                return ['status' => true, 'message' => "Booking cancelled successfully"];
            });
        } catch (\Exception $e) {
            Log::error("Error in _cancelBooking: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
