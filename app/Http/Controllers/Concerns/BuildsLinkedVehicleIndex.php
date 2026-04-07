<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\Vehicle as LegacyVehicle;
use App\Support\VehicleListing;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait BuildsLinkedVehicleIndex
{
    protected function linkedVehiclesIndexResponse(Request $request, int $parentAdminId, string $listUrl)
    {
        if ($request->input('export') === 'Export') {
            return $this->streamLinkedVehiclesCsv($request, $parentAdminId);
        }

        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['linked_vehicles_limit' => $lim]);
            }
        }
        $limit = (int)session('linked_vehicles_limit', 50);
        if ($limit < 1) {
            $limit = 50;
        }

        $dealerIds = AdminUserAssociation::query()
            ->where('admin_id', $parentAdminId)
            ->pluck('user_id')
            ->map(fn ($id) => (int)$id)
            ->all();

        $q = LegacyVehicle::query()->with('owner')->orderByDesc('id');
        VehicleListing::applyLinkedFilters($q, $request, $dealerIds);
        $vehicleDetails = $q->paginate($limit)->withQueryString();

        $keyword = trim((string)$request->input('Search.keyword', $request->query('keyword', '')));
        $searchin = trim((string)$request->input('Search.searchin', $request->query('searchin', '')));
        $show = (string)$request->input('Search.show', $request->query('show', ''));
        $userId = trim((string)$request->input('Search.user_id', $request->query('user_id', '')));

        return view('admin.linked_vehicles.index', [
            'vehicleDetails' => $vehicleDetails,
            'keyword' => $keyword,
            'searchin' => $searchin,
            'show' => $show,
            'userId' => $userId,
            'limit' => $limit,
            'showArr' => VehicleListing::linkedStatusLabels(),
            'searchOptions' => [
                'vehicle_name' => 'Car #',
                'vehicle_number' => 'Vehicle Number',
                'cab_name' => 'Vehicle Type',
                'plate_number' => 'Plate Number',
            ],
            'listUrl' => $listUrl,
        ]);
    }

    protected function streamLinkedVehiclesCsv(Request $request, int $parentAdminId): StreamedResponse
    {
        $dealerIds = AdminUserAssociation::query()
            ->where('admin_id', $parentAdminId)
            ->pluck('user_id')
            ->map(fn ($id) => (int)$id)
            ->all();

        $q = LegacyVehicle::query()->orderByDesc('id');
        VehicleListing::applyLinkedFilters($q, $request, $dealerIds);
        $rows = $q->limit(5000)->get(['id', 'user_id', 'vehicle_name', 'vehicle_unique_id', 'vin_no', 'plate_number', 'status', 'booked']);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="linked_vehicles.csv"',
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'user_id', 'vehicle_name', 'vehicle_unique_id', 'vin_no', 'plate_number', 'status', 'booked']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->user_id,
                    $r->vehicle_name,
                    $r->vehicle_unique_id,
                    $r->vin_no,
                    $r->plate_number,
                    $r->status,
                    $r->booked,
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }
}
