<td>
    <input type="checkbox" name="select[{{ $vehicle->id }}]" value="{{ $vehicle->id }}" id= "select1" style="border:0;">
</td>

<td valign="top" >
    {{ $vehicle->vehicle_unique_id }}
</td>

<td valign="top" >
    {{ $vehicle->vehicle_name }}
</td>

<td valign="top" >
    {{ trim((data_get($vehicle, 'owner.first_name', '') . ' ' . data_get($vehicle, 'owner.last_name', ''))) }}
</td>

<td valign="top" >
    {{ ucfirst($vehicle->type) }}
</td>

<td valign="top" >
    {{ $vehicle->visibility == 0 ? 'Not Visible Individually' : 'Visible' }}
</td>

<td valign="top" >
    {{ $vehicle->vin_no }}
</td>

<td valign="top" >
    {{ $vehicle->make }}
</td>

<td valign="top" >
    {{ $vehicle->model }}
</td>

<td valign="top" >
    <span class="display-eclipse" style="width: 70px;">{{ $vehicle->color }}</span>
</td>

<td valign="top" >
    <span class="display-eclipse" style="width: 70px;">{{ $vehicle->trim }}</span>
</td>

<td valign="top" >
    @if($vehicle->is_featured == 0 && !empty($vehicle->config)) 
        @php 
            $options = json_decode($vehicle->config, 1);
            $formatted_options = array_map(function($key, $val) {
                return "<strong>$key:</strong> $val";
            }, array_keys($options), $options);
            echo implode(', ', $formatted_options);
        @endphp
    @endif
</td>

<td align="center" valign="bottom">
    @if($vehicle->status == 1)
        <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status" onclick="loadVehicleStatus('{{ base64_encode($vehicle->id) }}')"/>
    @else
        <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status" onclick="loadVehicleStatus('{{ base64_encode($vehicle->id) }}')"/>
    @endif
</td>

<td align="center">
    @if($vehicle->trash)
        <span class='text-danger'>Deleted</span>
    @else
        {{ $vehicleSatatus[$vehicle->status] ?? "Active" }}
    @endif
</td>

<td align="center" valign="bottom">
    @if($vehicle->passtime_status == 1)
        <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status" onclick="changePasstimeVehicleStatus('{{ base64_encode($vehicle->id) }}','inactive')"/>
    @else
        <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status" onclick="changePasstimeVehicleStatus('{{ base64_encode($vehicle->id) }}','active')"/>
    @endif
</td>

<td class="action">
    <span class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-position="left" aria-expanded="true">
            <i class="icon-cog7"></i>
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-solid pull-right">
            @if(!empty($vehicle->gps_serialno))
                <li>
                    <a href="{{ url('admin/vehicles/lastlocation/' . base64_encode($vehicle->id)) }}" target="_blank" title='Vehicle Live Location'>
                        <i class="glyphicon glyphicon-map-marker"></i> Vehicle Live Location
                    </a>
                </li>
            @endif

            <li>
                <a href="javascript:void(0)" title='Define Vehicle GPS Setting' onclick="VehicleGpsSetting('{{ base64_encode($vehicle->id) }}')">
                    <i class="icon-satellite-dish2"></i> Define Vehicle GPS Setting
                </a>
            </li>

            <li>
                <a href="{{ url('admin/vehicles/rental_setting/' . base64_encode($vehicle->id)) }}" title='Usage Fee Setting'>
                    <i class="glyphicon glyphicon-usd"></i> Usage Fee Setting
                </a>
            </li>

            <li>
                <a href="{{ url('admin/vehicles/add/' . base64_encode($vehicle->id))}}" title='Edit Vehicle'>
                    <i class="glyphicon glyphicon-edit"></i> Edit Vehicle
                </a>
            </li>

            @if($vehicle->is_featured == 0)
                <li>
                    <a href="{{ url('admin/vehicles/duplicate/' . base64_encode($vehicle->id)) }}" title='Duplicate Vehicle'>
                        <i class="icon-copy3"></i> Duplicate Vehicle
                    </a>
                </li>
            @endif

        </ul>
    </span>
</td>
