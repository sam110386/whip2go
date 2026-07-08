@php
    $lists ??= [];
@endphp

@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50, 'position' => 'top'])
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;"> Vehicle </th>
                <th style="text-align:center;"> VIN </th>
                <th style="text-align:center;"> Last Recorded Mile </th>
                <th style="text-align:center;"> Last Checked </th>
                <th style="text-align:center;"> Booking </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                <tr id="{{ data_get($list, 'vehicle_id', '') }}">
                    <td style="text-align:center;">
                        {{ data_get($list, 'vehicle.vehicle_name', '') }}
                        {{ data_get($list, 'vehicle.vin_no', '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'vehicle.last_mile', '')}}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'vehicle.modified', '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'increment_id', '') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif