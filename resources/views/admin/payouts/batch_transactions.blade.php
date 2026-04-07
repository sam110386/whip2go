@php
    $typeLabel = function ($type) use ($paymentTypeValue) {
        return $paymentTypeValue[(int)$type] ?? ('Type ' . $type);
    };
    $fmtMoney = fn ($v) => number_format((float)$v, 2);
@endphp
<div class="panel">
    <h4 style="margin-top:0;">Associated transactions</h4>
    <table style="width:100%; border-collapse:collapse; font-size:12px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc;">
                <th style="padding:6px;">Booking#</th>
                <th style="padding:6px;">Vehicle</th>
                <th style="padding:6px;">Customer</th>
                <th style="padding:6px;">Type</th>
                <th style="padding:6px;">Amount</th>
                <th style="padding:6px;">Misc</th>
                <th style="padding:6px;">Net</th>
                <th style="padding:6px;">Date</th>
                <th style="padding:6px;">Action</th>
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
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $row->increment_id }}</td>
                    <td style="padding:6px;">{{ $row->vehicle_name }}</td>
                    <td style="padding:6px;">{{ trim(($row->renter_first_name ?? '') . ' ' . ($row->renter_last_name ?? '')) }}</td>
                    <td style="padding:6px;">{{ $typeLabel($row->type) }}</td>
                    <td style="padding:6px;">{{ $showAmt }}</td>
                    <td style="padding:6px;">{{ $misc }}</td>
                    <td style="padding:6px;">{{ $net }}</td>
                    <td style="padding:6px;">{{ $row->start_datetime }}</td>
                    <td style="padding:6px;">
                        @if (!empty($row->order_table_id))
                            <a href="/admin/transactions/updatetransaction/{{ $oid }}" target="_blank">Details</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="padding:12px;">No records.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
