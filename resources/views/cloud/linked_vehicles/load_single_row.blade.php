<td>
    <input type="checkbox" name="select[{{ $vehcile->id }}]" value="{{ $vehcile->id }}" id="select1" style="border:0">
</td>
<td valign="top" width="10%">{{ $vehcile->vehicle_unique_id }}</td>
<td valign="top">{{ $vehcile->vehicle_name }}</td>
<td valign="top">{{ trim((string)($vehcile->owner->first_name ?? '') . ' ' . (string)($vehcile->owner->last_name ?? '')) }}</td>
<td valign="top">{{ $vehcile->plate_number }}</td>
<td valign="top">{{ $vehcile->color }}</td>
<td valign="top">{{ $vehcile->make }}</td>
<td valign="top">{{ $vehcile->model }}</td>
<td valign="top">{{ $vehcile->cab_type }}</td>
<td align="center" valign="bottom">
    <a href="javascript:;" onclick="loadVehicleStatus('{{ base64_encode((string)$vehcile->id) }}')">
        <img src="{{ (int)$vehcile->status === 1 ? '/img/green2.jpg' : '/img/red3.jpg' }}" alt="Status" title="Status">
    </a>
</td>
<td>{{ $vehicleStatuses[(string)((int)$vehcile->status)] ?? 'Active' }}</td>
<td align="center" valign="bottom">
    <a href="javascript:;" onclick="changePasstimeVehicleStatus('{{ base64_encode((string)$vehcile->id) }}','{{ (int)$vehcile->passtime_status === 1 ? 'inactive' : 'active' }}')">
        <img src="{{ (int)$vehcile->passtime_status === 1 ? '/img/green2.jpg' : '/img/red3.jpg' }}" alt="Status" title="Status">
    </a>
</td>
<td class="action">
    @if (!empty($vehcile->passtime_serialno))
        <a href="/cloud/linked_vehicles/lastlocation/{{ base64_encode((string)$vehcile->id) }}" target="_blank" title="Vehicle Live Location">
            <i class="glyphicon glyphicon-map-marker"></i>
        </a>
        &nbsp;
    @endif
    <a href="/cloud/linked_vehicles/add/{{ base64_encode((string)$vehcile->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
</td>
