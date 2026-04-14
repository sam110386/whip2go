@if($records->hasPages())
    <div class="text-center">{{ $records->appends(request()->query())->links() }}</div>
@endif
<!-- Simple list -->
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Dealer</th>
                <th style="text-align:center;">Amount</th>
                <th style="text-align:center;">Driver</th>
                <th style="text-align:center;">Note</th>
                <th style="text-align:center;">Txn #</th>
                <th style="text-align:center;">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $list)
                <tr>
                    <td style="text-align:center;">{{ $list->id }}</td>
                    <td style="text-align:center;">{{ $list->owner_first_name }} {{ $list->owner_last_name }}</td>
                    <td style="text-align:center;">{{ $list->amt }}</td>
                    <td style="text-align:center;">{{ $list->driver_first_name }} {{ $list->driver_last_name }}</td>
                    <td style="text-align:center;">{{ $list->note }}</td>
                    <td style="text-align:center;">{{ $list->txn_id }}</td>
                    <td style="text-align:center;">
                        {{ $list->created != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($list->created)->timezone(session('default_timezone', config('app.timezone')))->format('Y-m-d h:i A') : '--' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<!-- /simple list -->
@if($records->hasPages())
    <div class="text-center">{{ $records->appends(request()->query())->links() }}</div>
@endif
