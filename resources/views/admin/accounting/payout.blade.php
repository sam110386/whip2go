<div class="modal-header bg-primary">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h5 class="modal-title">Payout Details</h5>
</div>
<div class="modal-body">
    <h6 class="text-semibold text-center border-bottom-blue-400 pb-10">Payments</h6>
    @php $paymentTypeValue = $reportlib->getPaymentType(true); @endphp
    <div class="table-responsive">
        <table class="table table-bordered table-condensed table-hover">
            <thead>
                <tr class="bg-slate-700">
                    <th class="text-center">Booking#</th>
                    <th class="text-center">Vehicle#</th>
                    <th class="text-center">Customer</th>
                    <th class="text-center">Transaction Type</th>
                    <th class="text-center">Amount</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td class="text-center">{{ $transaction->increment_id }}</td>
                        <td class="text-center">{{ $transaction->vehicle_name }}</td>
                        <td class="text-center">{{ $transaction->renter_first_name }} {{ $transaction->renter_last_name }}</td>
                        <td class="text-center">{{ $paymentTypeValue[$transaction->type] ?? '' }}</td>
                        <td class="text-center font-weight-semibold">
                            {{ $transaction->type == 6 ? '-' . $transaction->refund : $transaction->amount }}
                        </td>
                        <td class="text-center"><small>{{ \Carbon\Carbon::parse($transaction->start_datetime)->format('m/d/Y h:i A') }}</small></td>
                        <td class="text-center">
                            <a href="{{ url('admin/transactions/updatetransaction/' . base64_encode($transaction->order_id)) }}" class="text-primary">
                                <i class="icon-pencil7"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No Record Available!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>
