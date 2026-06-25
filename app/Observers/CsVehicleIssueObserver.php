<?php

namespace App\Observers;

use App\Models\Legacy\CsVehicleIssue;
use App\Models\Legacy\CsVehicleExpense;
use App\Services\Legacy\TicketService;

class CsVehicleIssueObserver
{
    public function created(CsVehicleIssue $csVehicleIssue): void
    {
        (new TicketService())->createIntercomTicket($csVehicleIssue->toArray());
    }

    public function saved(CsVehicleIssue $vehicleIssue): void
    {
        $extraData = json_decode($vehicleIssue->extra, true);

        if (isset($extraData['service_paid'])) {
            CsVehicleExpense::updateOrCreate(
                ['vehicle_issues_id' => $vehicleIssue->id],
                [
                    'vehicle_id' => $vehicleIssue->vehicle_id,
                    'amount' => $extraData['service_paid'],
                    'type' => $vehicleIssue->type,
                ]
            );
        }
    }
}
