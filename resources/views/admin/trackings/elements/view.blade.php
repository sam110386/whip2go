@php
    $trackings ??= [];
    $limit ??= 50;
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $trackings, 'limit' => $limit, 'position' => 'top'])

<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <tr>
            <th valign="top">Vehicle</th>
            <th valign="top">Total Views</th>
        </tr>

        @forelse($trackings as $row)
            <tr>
                <td valign="top">{{ $row->vehicle_name }}</td>
                <td valign="top">{{ $row->views }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" align="center">No record found</td>
            </tr>
        @endforelse
        <tr>
            <td style="height:6px;" colspan="2"></td>
        </tr>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $trackings, 'limit' => $limit])