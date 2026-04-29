@php
    $typeLabel = function ($type) use ($paymentTypeValue) {
        return $paymentTypeValue[(int)$type] ?? ('Type ' . $type);
    };
    $fmtMoney = fn ($v) => number_format((float)$v, 2);
@endphp
<div class="panel">
    <h4 style="margin-top:0;">Associated transactions</h4>
    <div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Booking#</th>
                <th>Vehicle</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Misc</th>
                <th>Net</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $row)
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
                    <td>{{ $row->start_datetime }}</td>
                    <td>
                        @if (!empty($row->order_table_id))
                            <a href="/admin/transactions/updatetransaction/{{ $oid }}" target="_blank">Details</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">No records.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
