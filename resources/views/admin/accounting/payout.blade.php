<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <center><h3>Payments</h3></center>
    @php $paymentTypeValue = $reportlib->getPaymentType(true); @endphp
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th align="center" style="text-align:center;">Booking#</th>
                <th align="center" style="text-align:center;">Vehicle#</th>
                <th align="center" style="text-align:center;">Customer</th>
                <th align="center" style="text-align:center;">Transaction Type</th>
                <th align="center" style="text-align:center;">Amount</th>
                <th align="center" style="text-align:center;">Date</th>
                <th align="center" style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td align="center" style="text-align:center;">{{ $transaction->increment_id }}</td>
                    <td align="center" style="text-align:center;">{{ $transaction->vehicle_name }}</td>
                    <td align="center" style="text-align:center;">{{ $transaction->renter_first_name }} {{ $transaction->renter_last_name }}</td>
                    <td align="center" style="text-align:center;">{{ $paymentTypeValue[$transaction->type] ?? '' }}</td>
                    <td align="center" style="text-align:center;">{{ $transaction->type == 6 ? '-' . $transaction->refund : $transaction->amount }}</td>
                    <td align="center" style="text-align:center;">{{ \Carbon\Carbon::parse($transaction->start_datetime)->format('Y-m-d h:i A') }}</td>
                    <td>
                        &nbsp;
                        <a href="{{ url('admin/transactions/updatetransaction/' . base64_encode($transaction->order_id)) }}"><img src="{{ asset('img/edit.png') }}" alt="Edit" style="border:0px" title="Edit"></a>
                    </td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="4">No Record Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
