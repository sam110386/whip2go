<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <center><h3>Payments</h3></center>
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th align="center" style="text-align:center;">#</th>
                <th align="center" style="text-align:center;">Time</th>
                <th align="center" style="text-align:center;">Event</th>
                <th align="center" style="text-align:center;">Amount</th>
                <th align="center" style="text-align:center;">Status</th>
                <th align="center" style="text-align:center;">Transaction#</th>
                <th align="center" style="text-align:center;">Transferred</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                @php static $i = 1; @endphp
                <tr>
                    <td>{{ $i++ }}</td>
                    <td>{{ \Carbon\Carbon::parse($payment->created)->format('Y-m-d h:i A') }}</td>
                    <td>{{ $reportlib->getPaymentType(false, $payment->type) }}</td>
                    <td>{{ $payment->amount }}</td>
                    <td>{{ $payment->status == 2 ? 'Refunded' : '' }}</td>
                    <td>{{ $payment->transaction_id }}</td>
                    <td>{{ $payment->cs_transfer ? 'Yes' : 'No' }}</td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="9">No Payment Log Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>
    <center><h3>Wallet Payments</h3></center>
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th align="center">#</th>
                <th align="center">Time</th>
                <th align="center">Type</th>
                <th align="center">Amount</th>
                <th align="center">Status</th>
                <th align="center">Transaction#</th>
                <th align="center" style="width:160px;">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($wallets as $wallet)
                @php static $j = 1; @endphp
                <tr>
                    <td>{{ $j++ }}</td>
                    <td>{{ \Carbon\Carbon::parse($wallet->created)->format('Y-m-d h:i A') }}</td>
                    <td>{{ $wallet->type ? 'Debit' : 'Credit' }}</td>
                    <td>{{ $wallet->amt }}</td>
                    <td>{{ $wallet->status == 2 ? 'Refunded' : '' }}</td>
                    <td>{{ $wallet->transaction_id }}</td>
                    <td>{{ str_replace('_', ' ', trim($wallet->note)) }}</td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="9">No Log Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
