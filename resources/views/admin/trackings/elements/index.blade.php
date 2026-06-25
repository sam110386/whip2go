@php
    $trackings ??= [];
    $limit ??= 50;
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $trackings, 'limit' => $limit, 'position' => 'top'])


<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <tr>
            <th valign="top">Vehicle</th>
            <th valign="top">Driver</th>
            <th valign="top">Date</th>
        </tr>

        @forelse($trackings as $row)
            <tr>
                <td valign="top">
                    {{ data_get($row, 'vehicle.vehicle_name', '') }}
                </td>
                <td valign="top">
                    {{ trim(data_get($row, 'user.first_name', '') . ' ' . data_get($row, 'user.last_name', '')) }}
                </td>
                <td valign="top">
                    @if(!empty(data_get($row, 'created')))
                        {{ \Carbon\Carbon::parse(data_get($row, 'created'))->format('m/d/Y h:i A') }}
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" align="center">No record found</td>
            </tr>
        @endforelse
        <tr>
            <td style="height:6px;" colspan="4"></td>
        </tr>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $trackings, 'limit' => $limit])