@if($records->hasPages())
<section class="pagging">
    <ul class="pagination pagination-rounded pull-right">
        {{ $records->links() }}
    </ul>
</section>
@endif

<div class="panel-flat">
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
                    <td style="text-align:center;">{{ $list->id }}</td>
                    <td style="text-align:center;">{{ $list->first_name }} {{ $list->last_name }}</td>
                    <td style="text-align:center;">{{ $list->address }}, {{ $list->state }}</td>
                    <td style="text-align:center;">{{ $list->vehicle_name }}</td>
                    <td style="text-align:center;">
                        @if($list->status == 0)
                            Canceled
                            <img src="/img/red3.jpg" alt="Status" title="Status">
                        @elseif($list->status == 1)
                            Active
                            <img src="/img/green2.jpg" alt="Status" title="Status">
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($list->created && $list->created !== '0000-00-00 00:00:00')
                            {{ \Carbon\Carbon::parse($list->created)->timezone($defaultTimezone ?? 'UTC')->format('Y-m-d h:i A') }}
                        @else
                            --
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <a href="{{ $basePath }}/delete/{{ base64_encode($list->id) }}" title="Delete" onclick="return confirm('are you sure you want to delete this record?')"><i class="icon-trash"></i></a>
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

@if($records->hasPages())
<section class="pagging">
    <ul class="pagination pagination-rounded pull-right">
        {{ $records->links() }}
    </ul>
</section>
@endif
