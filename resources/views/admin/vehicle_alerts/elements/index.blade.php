@if(isset($vehicleAlerts) && is_object($vehicleAlerts) && method_exists($vehicleAlerts, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $vehicleAlerts, 'limit' => $limit ?? 50, 'position' => 'top'])
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
            @foreach ($vehicleAlerts as $alert)
                <tr id="row_{{ $alert->id }}">
                    <td style="text-align:center;">
                        {{ $alert->id }}
                    </td>

                    <td style="text-align:center;">
                        {{ $alert?->vehicle?->vehicle_name }}
                    </td>

                    <td style="text-align:center;">
                        {{ $alert->type }}
                    </td>

                    <td style="text-align:center;">
                        <a target="_blank"
                            href="{{ sprintf('https://www.google.com/maps/place/%s/@%s,17z', urlencode($alert->geo), urlencode($alert->geo)) }}">
                            {{ $alert->geo }}
                        </a>
                    </td>

                    <td style="text-align:center;">
                        {{ $alert->speed }} MPH
                    </td>

                    <td style="text-align:center;">
                        @if($alert->created && $alert->created !== '0000-00-00 00:00:00')
                            {{ \Carbon\Carbon::parse($alert->created)->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') }}
                        @else
                            --
                        @endif
                    </td>

                    <td style="text-align:center;">
                        {{ $alert->note }}
                    </td>
                    <td style="text-align:center;">
                        <a href="javascript:void(0)" title="Delete Record" onclick="DeleteVehicleAlert('{{ $alert->id }}')">
                            <i class="icon-trash"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(isset($vehicleAlerts) && is_object($vehicleAlerts) && method_exists($vehicleAlerts, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $vehicleAlerts, 'limit' => $limit ?? 50])
@endif