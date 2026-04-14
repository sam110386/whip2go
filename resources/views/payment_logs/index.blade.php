<div class="modal-header">
    <a href="/payment_logs/all/{{ base64_encode((string)$orderid) }}" class="btn label-success" target="_blank">All Payment Logs</a>
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th align="center" style="text-align:center;">#</th>
                <th align="center" style="text-align:center;">Event</th>
                <th align="center" style="text-align:center;">Amount</th>
                <th align="center" style="text-align:center;">Status</th>
                <th align="center" style="text-align:center;">Transaction#</th>
                <th align="center" style="text-align:center;">Old Transaction#</th>
                <th align="center" style="text-align:center;">Time</th>
                <th align="center" style="text-align:center;">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $allSiblings[(int)$log->cs_order_id] ?? '' }}</td>
                    <td>{{ $paymentTypeValue[(int)$log->type] ?? 'Unknown Payment Type' }}</td>
                    <td>{{ $log->amount }}</td>
                    <td>{{ (int)$log->status === 2 ? 'Declined' : 'Success' }}</td>
                    <td>{{ $log->transaction_id }}</td>
                    <td>{{ $log->old_transaction_id }}</td>
                    <td>{{ !empty($log->created) ? \Carbon\Carbon::parse($log->created)->format('Y-m-d h:i A') : '' }}</td>
                    <td>{{ $log->note }}</td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="9">No Payment Log Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
