@php
    use App\Support\VehicleListing;
@endphp

<table style="width:100%; border-collapse:collapse; font-size:13px; margin-top:12px;">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;">ID</th>
            <th style="padding:6px;">Car #</th>
            <th style="padding:6px;">Owner</th>
            <th style="padding:6px;">Veh #</th>
            <th style="padding:6px;">VIN</th>
            <th style="padding:6px;">Plate</th>
            <th style="padding:6px;">Status</th>
            <th style="padding:6px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($vehicleDetails as $v)
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $v->id }}</td>
                <td style="padding:6px;">{{ $v->vehicle_name }}</td>
                <td style="padding:6px;">{{ trim((data_get($v, 'owner.first_name', '') . ' ' . data_get($v, 'owner.last_name', ''))) }}</td>
                <td style="padding:6px;">{{ $v->vehicle_unique_id }}</td>
                <td style="padding:6px;">{{ $v->vin_no }}</td>
                <td style="padding:6px;">{{ $v->plate_number }}</td>
                <td style="padding:6px;">
                    @if ($listContext === 'admin')
                        {{ VehicleListing::humanizeAdminRow($v) }}
                    @else
                        {{ (int)$v->status === 1 ? 'Active' : 'Inactive' }}
                    @endif
                </td>
                <td style="padding:6px; white-space:nowrap;">
                    @if ($listContext === 'admin')
                        <a href="/admin/vehicles/add/{{ base64_encode((string)$v->id) }}">Edit</a>
                        ·
                        <a href="/admin/vehicles/rental_setting/{{ base64_encode((string)$v->id) }}">Rental</a>
                        ·
                        <a href="/admin/vehicles/duplicate/{{ base64_encode((string)$v->id) }}">Dup</a>
                    @else
                        <a href="{{ $linkedBasePath ?? '/admin/linked_vehicles' }}/add/{{ base64_encode((string)$v->id) }}">Edit</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="padding:12px;">No records.</td></tr>
        @endforelse
    </tbody>
</table>

@if ($vehicleDetails instanceof \Illuminate\Contracts\Pagination\Paginator && $vehicleDetails->hasPages())
    <div style="margin-top:12px;">
        Page {{ $vehicleDetails->currentPage() }} of {{ $vehicleDetails->lastPage() }} ({{ $vehicleDetails->total() }} total)
        @if (!$vehicleDetails->onFirstPage())
            <a href="{{ $vehicleDetails->previousPageUrl() }}">Previous</a>
        @endif
        @if ($vehicleDetails->hasMorePages())
            <a href="{{ $vehicleDetails->nextPageUrl() }}">Next</a>
        @endif
    </div>
@endif
