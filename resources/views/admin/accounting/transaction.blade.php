@php
    $transactions ??= collect();
    $payments ??= collect();
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <center>
        <h3>Booking & Payments</h3>
    </center>

    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
                <th class="text-center">Booking#</th>
                <th class="text-center">Transaction Type</th>
                <th class="text-center">Amount</th>
                <th class="text-center">Date</th>
                <th class="text-center"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td class="text-center">
                        {{ data_get($payment, 'csOrder.increment_id', '') }}
                    </td>
                    <td class="text-center">
                        {{ \App\Services\Legacy\Reportlib::getPaymentType(false, data_get($payment, 'type')) }}
                    </td>
                    <td class="text-center font-weight-semibold">
                        {{ (data_get($payment, 'type', 0) == 6) ? '-' . data_get($payment, 'refund', '') : data_get($payment, 'amount', '') }}
                    </td>
                    <td class="text-center">
                        {{ data_get($payment, 'created', false) ? \Carbon\Carbon::parse(data_get($payment, 'created'))->format('m/d/Y h:i A') : '' }}
                    </td>
                    <td class="text-center">
                        {{ data_get($payment, 'status', 0) == 2 ? 'Refunded' : 'Active' }}
                    </td>
                </tr>
            @empty
                <tr id="set_hide">
                    <td colspan="5">No Record Available!</td>
                </tr>
            @endforelse
        </tbody>
    </table>


    <center>
        <h3>Payout</h3>
    </center>
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
                <th class="text-center">Payout#</th>
                <th class="text-center">Transaction Type</th>
                <th class="text-center">Amount</th>
                <th class="text-center">Date</th>
                <th class="text-center"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td class="text-center">
                        {{ data_get($transaction, 'csPayout.id', '') }}
                    </td>
                    <td class="text-center">
                        {{ \App\Services\Legacy\Reportlib::getPaymentType(false, data_get($transaction, 'type')) }}
                    </td>
                    <td class="text-center">
                        {{ data_get($transaction, 'amount', '') }}
                    </td>
                    <td class="text-center">
                        {{ data_get($transaction, 'created', false) ? \Carbon\Carbon::parse(data_get($transaction, 'created'))->format('m/d/Y h:i A') : '' }}
                    </td>
                    <td class="text-center">
                        {{ (data_get($transaction, 'status', 0) == 2) ? 'Refunded' : 'Active' }}
                    </td>
                </tr>
            @empty
                <tr id="set_hide">
                    <td colspan="5">No Record Available!</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>