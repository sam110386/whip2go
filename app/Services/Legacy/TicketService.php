<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class TicketService
{
    public array $intercom_ticket_id = [
        '1' => ['label' => 'Accident', 'id' => 7],
        '2' => ['label' => 'Roadside Assistance', 'id' => 2826010],
        '3' => ['label' => 'Mechanical Issues', 'id' => 2808899],
        '4' => ['label' => 'TDK Violation', 'id' => 2826012],
        '5' => ['label' => 'Vehicle Cleaning', 'id' => 2826015],
        '6' => ['label' => 'Maintenance', 'id' => 2808854],
        '7' => ['label' => 'Inspection Scan', 'id' => 2831802],
        '8' => ['label' => 'Pending Booking Related', 'id' => 2831803],
        '9' => ['label' => 'Vehicle License Plate Received', 'id' => 11],
        '10' => ['label' => 'Vehicle Insurance Ticket', 'id' => 9],
    ];

    private static array $ticketFields = [
        6 => ['maintenance_issue_detail', 'vehicle_scheduled_for_service', 'vehicle_serviced', 'service_paid', 'current_odometer', 'next_service_odometer'],
        3 => ['service_paid', 'maintenance_issue_detail'],
        2 => ['roadside_request_detail'],
        5 => ['roadside_request_detail'],
        9 => [],
        10 => [],
    ];

    public function createIntercomTicket(array $data): array
    {
        $issue = DB::table('cs_vehicle_issues')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'cs_vehicle_issues.vehicle_id')
            ->leftJoin('users', 'users.id', '=', 'cs_vehicle_issues.renter_id')
            ->where('cs_vehicle_issues.id', $data['id'])
            ->select(
                'cs_vehicle_issues.*',
                'vehicles.id as vehicle_db_id',
                'vehicles.vehicle_unique_id',
                'vehicles.vehicle_name',
                'users.id as user_db_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.contact_number'
            )
            ->first();

        if (empty($issue)) {
            return ['status' => false, 'message' => 'Vehicle issue not found'];
        }

        $ticketTypeId = isset($this->intercom_ticket_id[$data['type']])
            ? $this->intercom_ticket_id[$data['type']]['id']
            : 1;

        $userinfo = [
            'id' => $issue->user_db_id,
            'first_name' => $issue->first_name,
            'last_name' => $issue->last_name,
            'email' => $issue->email,
            'contact_number' => $issue->contact_number,
        ];

        $vehicleName = $issue->vehicle_name ?? '';
        $title = "Vehicle issue reported - {$vehicleName}";
        if ($data['type'] == 1) {
            $title = "An auto accident has been reported - {$vehicleName}";
        }
        if ($data['type'] == 8) {
            $title = "Application - {$vehicleName}";
        }

        $description = "Vehicle Name: {$vehicleName}\n";
        $description .= "Customer Name: " . ($issue->first_name ?? '') . ' ' . ($issue->last_name ?? '') . "\n";
        $description .= "Customer Email: " . ($issue->email ?? '') . "\n";
        $description .= "Ticket Type: " . ($this->intercom_ticket_id[$data['type']]['label'] ?? 'N/A') . "\n";

        if ($data['type'] != 8) {
            $issueArr = (array) $issue;
            $skipFields = ['id', 'type', 'user_id', 'renter_id', 'vehicle_id', 'created', 'updated', 'intercom_id', 'status',
                'vehicle_db_id', 'vehicle_unique_id', 'vehicle_name', 'user_db_id', 'first_name', 'last_name', 'email', 'contact_number'];

            foreach ($issueArr as $key => $val) {
                if (empty($val)) {
                    continue;
                }
                if (in_array($key, $skipFields)) {
                    continue;
                }
                if (isset(self::$ticketFields[$data['type']]) && !in_array($key, self::$ticketFields[$data['type']])) {
                    continue;
                }
                $description .= ucwords(str_replace('_', ' ', $key)) . ': ' . (is_array($val) ? implode(', ', $val) : $val) . "\n";
            }
        }

        if ($data['type'] == 10) {
            $description .= 'Your insurance policy has either disconnected or is being marked as inactive. Please resolve the issue.';
        }

        try {
            $intercom = app(IntercomClient::class);
            $resp = $intercom->createTicket((object) $userinfo, $ticketTypeId, $title, $description);

            if (!empty($resp->id)) {
                DB::table('cs_vehicle_issues')->where('id', $data['id'])->update(['intercom_id' => $resp->id]);
                return ['status' => true, 'message' => 'Ticket created successfully', 'ticket_id' => $resp->id];
            }
        } catch (\Exception $e) {
            \Log::error('createIntercomTicket failed: ' . $e->getMessage());
        }

        return ['status' => false, 'message' => 'Ticket creation failed'];
    }

    public function updateTicketStatus(string $ticketid): array
    {
        try {
            $intercom = app(IntercomClient::class);
            $resp = $intercom->updateTicketSatatus($ticketid, 'resolved');

            if (!empty($resp->id)) {
                return ['status' => true, 'message' => 'Ticket updated successfully', 'ticket_id' => $ticketid];
            }
        } catch (\Exception $e) {
            \Log::error('updateTicketStatus failed: ' . $e->getMessage());
        }

        return ['status' => false, 'message' => 'Ticket update failed'];
    }
}
