<div class="modal-header bg-primary">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h5 class="modal-title">Booking Details</h5>
</div>
<div class="modal-body">
    <h6 class="text-semibold text-center border-bottom-blue-400 pb-10">Payments</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-condensed table-hover">
            <thead>
                <tr class="bg-slate-700">
                    <th class="text-center">#</th>
                    <th class="text-center">Time</th>
                    <th class="text-center">Event</th>
                    <th class="text-center">Amount</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Transaction#</th>
                    <th class="text-center">Transferred</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php static $i = 1; @endphp
                    <tr>
                        <td class="text-center">{{ $i++ }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($payment->created)->format('m/d/Y h:i A') }}</td>
                        <td class="text-center">{{ $reportlib->getPaymentType(false, $payment->type) }}</td>
                        <td class="text-center font-weight-semibold text-success">{{ $payment->amount }}</td>
                        <td class="text-center">
                            @if($payment->status == 2)
                                <span class="label label-danger">Refunded</span>
                            @else
                                <span class="label label-success">Success</span>
                            @endif
                        </td>
                        <td class="text-center"><small>{{ $payment->transaction_id }}</small></td>
                        <td class="text-center">{{ $payment->cs_transfer ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No Payment Log Available!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="text-semibold text-center border-bottom-blue-400 pb-10 mt-20">Wallet Payments</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-condensed table-hover">
            <thead>
                <tr class="bg-slate-700">
                    <th class="text-center">#</th>
                    <th class="text-center">Time</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Amount</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Transaction#</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wallets as $wallet)
                    @php static $j = 1; @endphp
                    <tr>
                        <td class="text-center">{{ $j++ }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($wallet->created)->format('m/d/Y h:i A') }}</td>
                        <td class="text-center">
                            <span class="label {{ $wallet->type ? 'label-warning' : 'label-info' }}">
                                {{ $wallet->type ? 'Debit' : 'Credit' }}
                            </span>
                        </td>
                        <td class="text-center font-weight-semibold">{{ $wallet->amt }}</td>
                        <td class="text-center">
                            @if($wallet->status == 2)
                                <span class="label label-danger">Refunded</span>
                            @endif
                        </td>
                        <td class="text-center"><small>{{ $wallet->transaction_id }}</small></td>
                        <td><small>{{ str_replace('_', ' ', trim($wallet->note)) }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No Log Available!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>
