<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['field' => 'id', 'title' => '#', 'style' => 'text-align:center;'],
                    ['field' => 'first_name', 'title' => 'Driver', 'style' => 'text-align:center;'],
                    ['field' => 'address', 'title' => 'Address', 'style' => 'text-align:center;'],
                    ['field' => 'vehicle_id', 'title' => 'Vehicle', 'style' => 'text-align:center;'],
                    ['field' => 'status', 'title' => 'Status', 'style' => 'text-align:center;'],
                    ['field' => 'created', 'title' => 'Date', 'style' => 'text-align:center;'],
                    ['field' => 'action', 'title' => 'Action', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
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

@if(isset($records) && is_object($records) && method_exists($records, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit ?? 50])
@endif
