<div class="modal-header bg-primary">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h5 class="modal-title">Transaction Details</h5>
</div>
<div class="modal-body">
    @php $paymentTypeValue = $reportlib->getPaymentType(true); @endphp
    <h6 class="text-semibold text-center border-bottom-blue-400 pb-10">Booking & Payments</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-condensed table-hover">
            <thead>
                <tr class="bg-slate-700">
                    <th class="text-center">Booking#</th>
                    <th class="text-center">Transaction Type</th>
                    <th class="text-center">Amount</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td class="text-center">{{ $payment->increment_id }}</td>
                        <td class="text-center">{{ $paymentTypeValue[$payment->type] ?? '' }}</td>
                        <td class="text-center font-weight-semibold">{{ $payment->type == 6 ? '-' . $payment->refund : $payment->amount }}</td>
                        <td class="text-center"><small>{{ \Carbon\Carbon::parse($payment->created)->format('m/d/Y h:i A') }}</small></td>
                        <td class="text-center">
                            <span class="label {{ $payment->status == 2 ? 'label-danger' : 'label-success' }}">
                                {{ $payment->status == 2 ? 'Refunded' : 'Active' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No Record Available!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="text-semibold text-center border-bottom-blue-400 pb-10 mt-20">Payout</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-condensed table-hover">
            <thead>
                <tr class="bg-slate-700">
                    <th class="text-center">Payout#</th>
                    <th class="text-center">Transaction Type</th>
                    <th class="text-center">Amount</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td class="text-center">{{ $transaction->id }}</td>
                        <td class="text-center">{{ $paymentTypeValue[$transaction->type] ?? '' }}</td>
                        <td class="text-center font-weight-semibold">{{ $transaction->amount }}</td>
                        <td class="text-center"><small>{{ \Carbon\Carbon::parse($transaction->created)->format('m/d/Y h:i A') }}</small></td>
                        <td class="text-center">
                            <span class="label {{ $transaction->status == 2 ? 'label-danger' : 'label-success' }}">
                                {{ $transaction->status == 2 ? 'Refunded' : 'Active' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No Record Available!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>
