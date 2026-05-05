@php
    use App\Support\VehicleListing;
@endphp

<div class="panel panel-flat">
    <div class="table-responsive">
        <table width="100%" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['title' => 'ID', 'field' => 'id'],
                        ['title' => 'Car #', 'field' => 'vehicle_name'],
                        ['title' => 'Owner', 'field' => 'owner_first_name'],
                        ['title' => 'Veh #', 'field' => 'vehicle_unique_id'],
                        ['title' => 'VIN', 'field' => 'vin_no'],
                        ['title' => 'Plate', 'field' => 'plate_number'],
                        ['title' => 'Status', 'field' => 'status'],
                        ['title' => 'Actions', 'sortable' => false]
                    ]])
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicleDetails as $v)
                    <tr>
                        <td>{{ $v->id }}</td>
                        <td>{{ $v->vehicle_name }}</td>
                        <td>{{ trim((data_get($v, 'owner.first_name', '') . ' ' . data_get($v, 'owner.last_name', ''))) }}</td>
                        <td>{{ $v->vehicle_unique_id }}</td>
                        <td>{{ $v->vin_no }}</td>
                        <td>{{ $v->plate_number }}</td>
                        <td>
                            @if ($listContext === 'admin')
                                {{ VehicleListing::humanizeAdminRow($v) }}
                            @else
                                {{ (int)$v->status === 1 ? 'Active' : 'Inactive' }}
                            @endif
                        </td>
                        <td>
                            @if ($listContext === 'admin')
                                <a href="/admin/vehicles/add/{{ base64_encode((string)$v->id) }}" class="btn btn-default btn-xs" title="Edit"><i class="icon-pencil"></i></a>
                                <a href="/admin/vehicles/rental_setting/{{ base64_encode((string)$v->id) }}" class="btn btn-default btn-xs" title="Rental"><i class="icon-gear"></i></a>
                                <a href="/admin/vehicles/duplicate/{{ base64_encode((string)$v->id) }}" class="btn btn-default btn-xs" title="Dup"><i class="icon-copy3"></i></a>
                            @else
                                <a href="{{ $linkedBasePath ?? '/admin/linked_vehicles' }}/add/{{ base64_encode((string)$v->id) }}" class="btn btn-default btn-xs">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" align="center">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($vehicleDetails instanceof \Illuminate\Contracts\Pagination\Paginator)
    @include('partials.dispacher.paging_box', ['paginator' => $vehicleDetails, 'limit' => $limit ?? 25])
@endif
