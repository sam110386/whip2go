<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsLeaseUnavailability;
use App\Models\Legacy\CsLease;
use App\Models\Legacy\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait LeasesTrait {

    public function processCreateVehicleUnavailability($vehicleid, $view) {
        $vehicleid = base64_decode($vehicleid);
        if (empty($vehicleid)) {
            return redirect()->route('legacy.vehicles.index');
        }
        return view($view, compact('vehicleid'));
    }

    public function processLoad(Request $request, $userId = null) {
        $startDate = Carbon::parse($request->query('start'))->toDateString();
        $endDate = Carbon::parse($request->query('end'))->toDateString();
        $vehicleid = $request->query('vehicle_id');
        
        $query = CsLeaseUnavailability::where('vehicle_id', $vehicleid)
            ->whereBetween('date', [$startDate, $endDate]);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $tData = $query->get();
        $responseData = [];

        foreach ($tData as $item) {
            $responseData[] = [
                'id' => $item->id,
                'allDay' => false,
                'start' => Carbon::parse($item->date)->toIso8601String(),
                'end' => Carbon::parse($item->date)->toIso8601String()
            ];
        }

        return response()->json($responseData);
    }

    public function processRemove(Request $request, $id, $userId = null) {
        if (!empty($id)) {
            $query = CsLeaseUnavailability::where('id', $id);
            if ($userId) {
                $query->where('user_id', $userId);
            }
            $query->delete();
            return response()->json(['status' => 1, 'message' => "Vehicle Unavailability deleted for selected date"]);
        }
        return response()->json(['status' => 0, 'message' => "Invalid ID"]);
    }

    public function processAddUnavailability(Request $request, $userId = null, $isAdmin = false) {
        $data = $request->all();
        if (!empty($data) && !empty($data['vehicle_id'])) {
            $vehicle_id = trim($data['vehicle_id']);
            $query = Vehicle::where('id', $vehicle_id);
            if (!$isAdmin && $userId) {
                $query->where('user_id', $userId);
            }
            $vehicle = $query->first();

            if (!$vehicle) {
                return response()->json(['status' => 0, 'message' => "Sorry, you are not authorized owner of selected Vehicle"]);
            }

            $startDate = Carbon::parse($data['start']);
            $endDate = Carbon::parse($data['end']);

            try {
                DB::transaction(function() use ($startDate, $endDate, $vehicle_id, $userId) {
                    for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
                        CsLeaseUnavailability::updateOrCreate(
                            ['vehicle_id' => $vehicle_id, 'date' => $date->toDateString()],
                            ['user_id' => $userId ?: CsLeaseUnavailability::where('vehicle_id', $vehicle_id)->value('user_id')]
                        );
                    }
                });
                return response()->json(['status' => 1, 'message' => "Vehicle Unavailability data is saved successfully."]);
            } catch (\Exception $e) {
                return response()->json(['status' => 0, 'message' => $e->getMessage()]);
            }
        }
        return response()->json(['status' => 0, 'message' => "Invalid input"]);
    }

    public function processCreateVehicleLease(Request $request, $vehicleid, $userId = null, $view = null) {
        $vehicleid = base64_decode($vehicleid);
        if (empty($vehicleid)) {
            return redirect()->route('legacy.vehicles.index');
        }

        if ($request->isMethod('post')) {
            $data = $request->input('CsLease');
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                CsLease::updateOrCreate(
                    ['vehicle_id' => $vehicleid],
                    [
                        'user_id' => $userId ?: CsLease::where('vehicle_id', $vehicleid)->value('user_id'),
                        'start_date' => Carbon::parse($data['start_date'])->toDateString(),
                        'end_date' => Carbon::parse($data['end_date'])->toDateString()
                    ]
                );
                return redirect()->route('legacy.vehicles.index')->with('success', 'Records data saved successfully');
            }
        }

        $vehicle = Vehicle::with('lease')->find($vehicleid);
        // Map to CakePHP data structure if needed for Blade
        $data = $vehicle;

        return view($view, compact('data', 'vehicleid'));
    }
}
