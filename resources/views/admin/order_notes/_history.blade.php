{{-- Pagination links --}}
@if($history->hasPages())
<div class="text-center">{{ $history->appends(['orderid' => $orderid, 'parentid' => $parentid])->links() }}</div>
@endif

<table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th style="width:5px;">Booking#</th>
            <th style="width:5px;">Note #</th>
            <th style="width:5px;">Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($history as $hist)
            <tr>
                <td>{{ $hist->increment_id }}</td>
                <td>{{ $hist->msg }}</td>
                <td>{{ $hist->created }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
