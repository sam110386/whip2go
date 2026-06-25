@php
    use App\Support\VehicleListing;
@endphp
@if ($vehicleDetails)
    @include('partials.dispacher.paging_box', ['paginator' => $vehicleDetails, 'limit' => $limit ?? 25, 'position' => 'top'])
@endif


<form method="GET" action="{{ url('admin/vehicles/multiplAction') }}" name="frm1" id="frm1" onsubmit="return ischeckboxSelected(frm1, frm1.elements['select1'], 'Vehicle')">
    <div class="table-responsive">
        <table width="100%" cellpadding="2" cellspacing="1"  border="0"  class="table  table-responsive vehiclelist">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['title' => '<input type="checkbox" name="selectall" id="selectAllChildCheckboxs" value="1" onclick="GetAction(this.checked, \'this.form.data[pageListing][select]\', document.querySelectorAll(\'input[id=select1]\'))"/>', 'sortable' => false, 'html' => true],
                        ['title' => '#', 'field' => 'vehicle_name'],
                        ['title' => 'Name', 'sortable' => false],
                        ['title' => 'Owner', 'sortable' => false],
                        ['title' => 'Type', 'sortable' => false],
                        ['title' => 'Visibility', 'sortable' => false],
                        ['title' => 'VIN', 'sortable' => false],
                        ['title' => 'Make', 'sortable' => false],
                        ['title' => 'Model', 'sortable' => false],
                        ['title' => 'Color', 'sortable' => false, 'style'=>"width: 70px;"],
                        ['title' => 'Trim', 'sortable' => false, 'style'=>"width: 70px;"],
                        ['title' => 'Config', 'sortable' => false],
                        ['title' => 'Status', 'field' => 'status'],
                        ['title' => '', 'sortable' => false],
                        ['title' => 'Passtime', 'sortable' => false],
                        ['title' => 'Actions', 'sortable' => false]
                    ]])
                </tr>
            </thead>
            <tbody>
                @forelse ($vehicleDetails as $v)
                    @php
                        $class = ($v->is_featured == 1) ? 'warning' : '';
                        $basePath = ($v->is_featured == 1) ? 'admin/featured_vehicles/add/' : 'admin/vehicles/add/';
                        $editUrl = url($basePath . base64_encode($v->id));
                    @endphp
                    <tr id="{{ $v->id }}"  class="{{ $class }}">
                        <td>
                            <input type="checkbox" name="select[{{ $v->id }}]" value="{{ $v->id }}" id= "select1" style="border:0;">
                        </td>

                        <td valign="top" >
                            {{ $v->vehicle_unique_id }}
                        </td>

                        <td valign="top" >
                            {{ $v->vehicle_name }}
                        </td>

                        <td valign="top" >
                            {{ trim((data_get($v, 'owner.first_name', '') . ' ' . data_get($v, 'owner.last_name', ''))) }}
                        </td>

                        <td valign="top" >
                            {{ ucfirst($v->type) }}
                        </td>

                        <td valign="top" >
                            {{ $v->visibility == 0 ? 'Not Visible Individually' : 'Visible' }}
                        </td>

                        <td valign="top" >
                            {{ $v->vin_no }}
                        </td>

                        <td valign="top" >
                            {{ $v->make }}
                        </td>

                        <td valign="top" >
                            {{ $v->model }}
                        </td>

                        <td valign="top" >
                            <span class="display-eclipse" style="width: 70px;">{{ $v->color }}</span>
                        </td>

                        <td valign="top" >
                            <span class="display-eclipse" style="width: 70px;">{{ $v->trim }}</span>
                        </td>

                        <td valign="top" >
                            @if($v->is_featured == 0 && !empty($v->config)) 
                                @php 
                                    $options = json_decode($v->config, 1);
                                    $formatted_options = array_map(function($key, $val) {
                                        return "<strong>$key:</strong> $val";
                                    }, array_keys($options), $options);
                                    echo implode(', ', $formatted_options);
                                @endphp
                            @endif
                        </td>

                        <td align="center" valign="bottom">
                            @if($v->status == 1)
                                <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status" onclick="loadVehicleStatus('{{ base64_encode($v->id) }}')"/>
                            @else
                                <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status" onclick="loadVehicleStatus('{{ base64_encode($v->id) }}')"/>
                            @endif
                        </td>

                        <td align="center">
                            @if($v->trash)
                                <span class='text-danger'>Deleted</span>
                            @else
                                {{ $vehicleSatatus[$v->status] ?? "Active" }}
                            @endif
                        </td>

                        <td align="center" valign="bottom">
                            @if($v->passtime_status == 1)
                                <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status" onclick="changePasstimeVehicleStatus('{{ base64_encode($v->id) }}','inactive')"/>
                            @else
                                <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status" onclick="changePasstimeVehicleStatus('{{ base64_encode($v->id) }}','active')"/>
                            @endif
                        </td>

                        <td class="action">
                            <span class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-position="left" aria-expanded="true">
                                    <i class="icon-cog7"></i>
                                    <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-solid pull-right">
                                    @if(!empty($v->gps_serialno))
                                        <li>
                                            <a href="{{ url('admin/vehicles/lastlocation/' . base64_encode($v->id)) }}" target="_blank" title='Vehicle Live Location'>
                                                <i class="glyphicon glyphicon-map-marker"></i> Vehicle Live Location
                                            </a>
                                        </li>
                                    @endif

                                    <li>
                                        <a href="javascript:void(0)" title='Define Vehicle GPS Setting' onclick="VehicleGpsSetting('{{ base64_encode($v->id) }}')">
                                            <i class="icon-satellite-dish2"></i> Define Vehicle GPS Setting
                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ url('admin/vehicles/rental_setting/' . base64_encode($v->id)) }}" title='Usage Fee Setting'>
                                            <i class="glyphicon glyphicon-usd"></i> Usage Fee Setting
                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ $editUrl }}" title='Edit Vehicle'>
                                            <i class="glyphicon glyphicon-edit"></i> Edit Vehicle
                                        </a>
                                    </li>

                                    @if($v->is_featured == 0)
                                        <li>
                                            <a href="{{ url('admin/vehicles/duplicate/' . base64_encode($v->id)) }}" title='Duplicate Vehicle'>
                                                <i class="icon-copy3"></i> Duplicate Vehicle
                                            </a>
                                        </li>
                                    @endif

                                </ul>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" align="center">No records found.</td>
                    </tr>
                @endforelse
                    <tr>
                        <td colspan="14" style="padding-left:7px;text-align:left;" >
                            <strong>Activate/Deactivate/delete multiple Cars:</strong>
                            <select name="Vehicle[status]" id="VehicleStatus" class="select">
                                <option value="" >--Select..</option>
                                <option value="active" >Activate</option>
                                <option value="inactive" >Inactive</option>
                            </select>
                           <button type="submit" class="btn btn-primary" alt="Multiple Status" title="Multiple Status">Submit</button>
                        </td>
                    </tr>
            </tbody>
        </table>
    </div>
</form>

@if ($vehicleDetails)
    @include('partials.dispacher.paging_box', ['paginator' => $vehicleDetails, 'limit' => $limit ?? 25])
@endif
