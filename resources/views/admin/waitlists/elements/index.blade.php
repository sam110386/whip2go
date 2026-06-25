@php
    $records ??= [];
    $limit ??= 50;
@endphp

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th style="text-align:center;">#</th>
            <th style="text-align:center;">Driver</th>
            <th style="text-align:center;">Address</th>
            <th style="text-align:center;">Vehicle</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center;">Date</th>
            <th style="text-align:center;">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $list)
            <tr>
                <td style="text-align:center;">
                    {{ data_get($list, 'id', '')}}
                </td>
                <td style="text-align:center;">
                    {{ data_get($list, 'user.first_name', '') }} {{ data_get($list, 'user.last_name', '') }}
                </td>
                <td style="text-align:center;">
                    {{ data_get($list, 'user.address', '') }}, {{ data_get($list, 'user.state', '') }}
                </td>
                <td style="text-align:center;">
                    {{ data_get($list, 'vehicle.vehicle_name', '') }}
                </td>
                <td style="text-align:center;">
                    @if(data_get($list, 'status', 0) == 0)
                        Canceled
                        <img src="/img/red3.jpg" alt="Status" title="Status">
                    @elseif(data_get($list, 'status', 0) == 1)
                        Active
                        <img src="/img/green2.jpg" alt="Status" title="Status">
                    @endif
                </td>
                <td style="text-align:center;">
                    @if(data_get($list, 'created') && data_get($list, 'created') !== '0000-00-00 00:00:00')
                        {{ \Carbon\Carbon::parse(data_get($list, 'created'))->timezone(config('app.timezone', 'UTC'))->format('Y-m-d h:i A') }}
                    @else
                        --
                    @endif
                </td>
                <td style="text-align:center;">
                    <a href="{{ url('/admin/waitlists/delete/' . base64_encode(data_get($list, 'id', ''))) }}"
                        title="Delete" onclick="return confirm('are you sure you want to delete this record?')">
                        <i class="icon-trash"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center;">No waitlist records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

@if(isset($records) && is_object($records) && method_exists($records, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit ?? 50])
@endif