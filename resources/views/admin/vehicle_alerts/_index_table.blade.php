@if(isset($vehiclealrets) && is_object($vehiclealrets) && method_exists($vehiclealrets, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $vehiclealrets, 'limit' => $limit ?? 50])
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'field' => 'id', 'style' => 'text-align:center;'],
                    ['title' => 'Vehicle', 'field' => 'vehicle_name', 'style' => 'text-align:center;'],
                    ['title' => 'Type', 'field' => 'type', 'style' => 'text-align:center;'],
                    ['title' => 'Geo', 'field' => 'geo', 'style' => 'text-align:center;'],
                    ['title' => 'MPH', 'field' => 'speed', 'style' => 'text-align:center;'],
                    ['title' => 'Recorded At', 'field' => 'created', 'style' => 'text-align:center;'],
                    ['title' => 'Note', 'field' => 'note', 'style' => 'text-align:center;'],
                    ['title' => 'Action', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
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

@if(isset($vehiclealrets) && is_object($vehiclealrets) && method_exists($vehiclealrets, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $vehiclealrets, 'limit' => $limit ?? 50])
@endif
