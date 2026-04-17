@php
    $typeLabel = function ($type) use ($paymentTypeValue) {
        return $paymentTypeValue[(int)$type] ?? ('Type ' . $type);
    };
    $fmtMoney = fn ($v) => number_format((float)$v, 2);
@endphp

@if ($batchMode)
    <div class="table-responsive">
        <table width="100%" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['field' => 'id', 'title' => 'Payout #'],
                        ['field' => 'amount', 'title' => 'Amount'],
                        ['field' => 'processed_on', 'title' => 'Processed'],
                        ['field' => 'transaction_id', 'title' => 'Txn id'],
                        ['field' => 'actions', 'title' => 'Action', 'sortable' => false]
                    ]])
                </tr>
            </thead>
            <tbody>
                @forelse ($payoutlists as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->amount }}</td>
                        <td>{{ $row->processed_on }}</td>
                        <td>{{ $row->transaction_id }}</td>
                        <td>
                            <button type="button" class="btn btn-default btn-xs" onclick="getTransactions({{ (int)$row->id }})" title="Associated transactions">
                                <i class="icon-cash3"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" align="center">No batches found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="table-responsive">
        <table width="100%" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['field' => 'increment_id', 'title' => 'Booking#'],
                        ['field' => 'vehicle_name', 'title' => 'Vehicle'],
                        ['field' => 'renter_first_name', 'title' => 'Customer'],
                        ['field' => 'type', 'title' => 'Type'],
                        ['field' => 'amount', 'title' => 'Amount'],
                        ['field' => 'stripe_amt', 'title' => 'Misc'],
                        ['field' => 'net', 'title' => 'Net', 'sortable' => false],
                        ['field' => 'created', 'title' => 'Date'],
                        ['field' => 'actions', 'title' => 'Action', 'sortable' => false]
                    ]])
                </tr>
            </thead>
            <tbody>
                @forelse ($payoutlists as $row)
                    @php
                        $refund = (float)($row->refund ?? 0);
                        $amt = (float)($row->amount ?? 0);
                        $stripe = (float)($row->stripe_amt ?? 0);
                        $showAmt = $refund > 0 ? '-' . $fmtMoney($refund) : $fmtMoney($amt);
                        $misc = $refund > 0 ? '-' : ($stripe > 0 ? $fmtMoney($amt - $stripe) : '0.00');
                        $net = $refund > 0 ? '-' . $fmtMoney($refund) : ($stripe > 0 ? $fmtMoney($stripe) : $fmtMoney($amt));
                        $oid = base64_encode((string)($row->order_table_id ?? ''));
                    @endphp
                    <tr>
                        <td>{{ $row->increment_id }}</td>
                        <td>{{ $row->vehicle_name }}</td>
                        <td>{{ trim(($row->renter_first_name ?? '') . ' ' . ($row->renter_last_name ?? '')) }}</td>
                        <td>{{ $typeLabel($row->type) }}</td>
                        <td>{{ $showAmt }}</td>
                        <td>{{ $misc }}</td>
                        <td>{{ $net }}</td>
                        <td>{{ $row->created }}</td>
                        <td>
                            @if (!empty($row->order_table_id))
                                <a href="/admin/transactions/updatetransaction/{{ $oid }}" class="btn btn-default btn-xs">Details</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" align="center">No rows found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@include('partials.dispacher.paging_box', ['paginator' => $payoutlists, 'limit' => $limit ?? 25])
