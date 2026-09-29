@php
    $wallets ??= collect();
    $payments ??= collect();
    $i = 1;
    $j = 1;
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <center>
        <h3>Payments</h3>
    </center>
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
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
                <tr>
                    <td class="text-center">
                        {{ $i++ }}
                    </td>
                    <td class="text-center">
                        {{ data_get($payment, 'created', false) ? \Carbon\Carbon::parse(data_get($payment, 'created'))->format('m/d/Y h:i A') : '' }}
                    </td>
                    <td class="text-center">
                        {{ \App\Services\Legacy\Reportlib::getPaymentType(false, data_get($payment, 'type')) }}
                    </td>
                    <td class="text-center">
                        {{ data_get($payment, 'amount', '') }}
                    </td>
                    <td class="text-center">
                        {{ (data_get($payment, 'status', 0) == 2 ? 'Refunded' : '') }}
                    </td>
                    <td class="text-center">
                        {{ data_get($payment, 'transaction_id', '') }}
                    </td>
                    <td class="text-center">
                        {{ data_get($payment, 'cs_transfer', false) ? 'Yes' : 'No' }}
                    </td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="9">No Payment Log Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>

    <center>
        <h3>Wallet Payments</h3>
    </center>
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th class="text-center">Time</th>
                <th class="text-center">Type</th>
                <th class="text-center">Amount</th>
                <th class="text-center">Status</th>
                <th class="text-center">Transaction#</th>
                <th class="text-center" style="width:160px;">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($wallets as $wallet)
                <tr>
                    <td class="text-center">
                        {{ $j++ }}
                    </td>
                    <td class="text-center">
                        {{ data_get($wallet, 'created', false) ? \Carbon\Carbon::parse(data_get($wallet, 'created'))->format('m/d/Y h:i A') : '' }}
                    </td>
                    <td class="text-center">
                        {{ data_get($wallet, 'type', false) ? 'Debit' : 'Credit' }}
                    </td>
                    <td class="text-center font-weight-semibold">
                        {{ data_get($wallet, 'amt', '') }}
                    </td>
                    <td class="text-center">
                        {{ (data_get($wallet, 'status', 0) == 2) ? 'Refunded' : '' }}
                    </td>
                    <td class="text-center">
                        {{ data_get($wallet, 'transaction_id', '') }}
                    </td>
                    <td>
                        {{ str_replace('_', ' ', trim(data_get($wallet, 'note', ''))) }}
                    </td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="9">No Log Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>