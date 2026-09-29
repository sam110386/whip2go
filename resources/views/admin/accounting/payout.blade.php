@php
    $transactions ??= collect();
    $payments ??= collect();
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
                    <td class="text-center">
                        {{ data_get($transaction, 'csOrder.increment_id') }}
                    </td>
                    <td class="text-center">
                        {{ data_get($transaction, 'csOrder.vehicle.vehicle_name') }}
                    </td>
                    <td class="text-center">
                        {{ data_get($transaction, 'csOrder.renter.first_name') . ' ' . data_get($transaction, 'csOrder.renter.last_name') }}
                    </td>
                    <td class="text-center">
                        {{ \App\Services\Legacy\Reportlib::getPaymentType(false, data_get($transaction, 'type')) }}
                    </td>
                    <td class="text-center">
                        {{ (data_get($transaction, 'type', 0) == 6) ? '-' . data_get($transaction, 'refund') : data_get($transaction, 'amount') }}
                    </td>
                    <td class="text-center">
                        {{ data_get($transaction, 'csOrder.start_datetime', false) ? \Carbon\Carbon::parse(data_get($transaction, 'csOrder.start_datetime'))->format('m/d/Y h:i A') : '' }}
                    </td>
                    <td class="text-center">
                        <a href="{{ url('admin/transactions/updatetransaction', base64_encode(data_get($transaction, 'csOrder.id', ''))) }}"
                            title="Edit" class="border-0">
                            <img src="{{ legacy_asset('img/edit.png') }}">
                        </a>
                    </td>
                </tr>
            @empty
                <tr id="set_hide">
                    <td colspan="7">No Record Available!</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>