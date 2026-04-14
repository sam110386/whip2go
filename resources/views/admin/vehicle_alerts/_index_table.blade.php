{{-- Pagination --}}
@if($vehiclealrets->hasPages())
<div class="text-center">{{ $vehiclealrets->appends(['vehicle_id' => $vehicleid])->links() }}</div>
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Vehicle</th>
                <th style="text-align:center;">Type</th>
                <th style="text-align:center;">Geo</th>
                <th style="text-align:center;">MPH</th>
                <th style="text-align:center;">Recorded At</th>
                <th style="text-align:center;">Note</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vehiclealrets as $alert)
                <tr id="row_{{ $alert->id }}">
                    <td style="text-align:center;">{{ $alert->id }}</td>
                    <td style="text-align:center;">{{ $alert->vehicle_name }}</td>
                    <td style="text-align:center;">{{ $alert->type }}</td>
                    <td style="text-align:center;">
                        <a target="_blank" href="http://www.google.com/maps/place/{{ $alert->geo }}/@{{ $alert->geo }},17z">{{ $alert->geo }}</a>
                    </td>
                    <td style="text-align:center;">{{ $alert->speed }} MPH</td>
                    <td style="text-align:center;">
                        @if($alert->created && $alert->created !== '0000-00-00 00:00:00')
                            {{ \Carbon\Carbon::parse($alert->created)->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') }}
                        @else
                            --
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $alert->note }}</td>
                    <td style="text-align:center;">
                        <a href="javascript:;" title="Delete Record" onclick="DeleteVehicleAlert('{{ $alert->id }}')"><i class="icon-trash"></i></a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($vehiclealrets->hasPages())
<div class="text-center">{{ $vehiclealrets->appends(['vehicle_id' => $vehicleid])->links() }}</div>
@endif
