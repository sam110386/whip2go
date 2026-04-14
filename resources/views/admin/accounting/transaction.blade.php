<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    @php $paymentTypeValue = $reportlib->getPaymentType(true); @endphp
    <center><h3>Booking & Payments</h3></center>
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th align="center" style="text-align:center;">Booking#</th>
                <th align="center" style="text-align:center;">Transaction Type</th>
                <th align="center" style="text-align:center;">Amount</th>
                <th align="center" style="text-align:center;">Date</th>
                <th align="center" style="text-align:center;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td align="center">{{ $payment->increment_id }}</td>
                    <td align="center">{{ $paymentTypeValue[$payment->type] ?? '' }}</td>
                    <td align="center">{{ $payment->type == 6 ? '-' . $payment->refund : $payment->amount }}</td>
                    <td align="center">{{ \Carbon\Carbon::parse($payment->created)->format('Y-m-d h:i A') }}</td>
                    <td align="center">{{ $payment->status == 2 ? 'Refunded' : 'Active' }}</td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="5">No Record Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>

    <center><h3>Payout</h3></center>
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th align="center" style="text-align:center;">Payout#</th>
                <th align="center" style="text-align:center;">Transaction Type</th>
                <th align="center" style="text-align:center;">Amount</th>
                <th align="center" style="text-align:center;">Date</th>
                <th align="center" style="text-align:center;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td align="center">{{ $transaction->id }}</td>
                    <td align="center">{{ $paymentTypeValue[$transaction->type] ?? '' }}</td>
                    <td align="center">{{ $transaction->amount }}</td>
                    <td align="center">{{ \Carbon\Carbon::parse($transaction->created)->format('Y-m-d h:i A') }}</td>
                    <td align="center">{{ $transaction->status == 2 ? 'Refunded' : 'Active' }}</td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="5">No Record Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
