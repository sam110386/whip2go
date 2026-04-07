@php
    $typeLabel = function ($t) use ($balanceTypes) {
        $k = (string)$t;

        return $balanceTypes[$k] ?? '';
    };
@endphp

<table style="width:100%; border-collapse:collapse; font-size:12px;">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;">#</th>
            @if (!$subscriptionMode)
                <th style="padding:6px;">Name</th>
            @endif
            <th style="padding:6px;">Type</th>
            <th style="padding:6px;">Charge on Driver</th>
            <th style="padding:6px;">Debit</th>
            <th style="padding:6px;">Balance</th>
            <th style="padding:6px;">Charge Type</th>
            <th style="padding:6px;">Last Processed</th>
            <th style="padding:6px;">Note</th>
            <th style="padding:6px;">Created</th>
            <th style="padding:6px;">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($records as $row)
            @php
                $ct = strtolower((string)($row->chargetype ?? ''));
            @endphp
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $row->id }}</td>
                @if (!$subscriptionMode)
                    <td style="padding:6px;">{{ trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) }}</td>
                @endif
                <td style="padding:6px;">{{ $typeLabel($row->type) }}</td>
                <td style="padding:6px;">{{ $row->credit }}</td>
                <td style="padding:6px;">{{ $row->debit }}</td>
                <td style="padding:6px;">{{ $row->balance }}</td>
                <td style="padding:6px;">
                    <strong>Type:</strong> {{ ucfirst((string)($row->chargetype ?? '')) }}<br>
                    <strong>Installment Type:</strong> {{ ucfirst((string)($row->installment_type ?? '')) }}<br>
                    <strong>Installment:</strong> {{ $row->installment }}
                </td>
                <td style="padding:6px;">{{ $formatDt($row->last_processed ?? null) }}</td>
                <td style="padding:6px;">{{ $row->note }}</td>
                <td style="padding:6px;">{{ $row->created }}</td>
                <td style="padding:6px; white-space:nowrap;">
                    @if ((int)$row->status === 1)
                        <a href="/admin/customer_balances/status/{{ base64_encode((string)$row->id) }}/0"
                           onclick="return confirm('Are you sure to update this record?');">
                            <img src="/img/green2.jpg" alt="Active" title="Set inactive" style="height:18px;vertical-align:middle;">
                        </a>
                    @else
                        <a href="/admin/customer_balances/status/{{ base64_encode((string)$row->id) }}/1"
                           onclick="return confirm('Are you sure to update this record?');">
                            <img src="/img/red3.jpg" alt="Inactive" title="Set active" style="height:18px;vertical-align:middle;">
                        </a>
                    @endif
                    @if (!$subscriptionMode && (int)$row->status === 1 && $ct === 'subscription' && !empty($row->linked_user_id))
                        <a href="/admin/customer_balances/addsubscription/{{ base64_encode((string)$row->linked_user_id) }}/{{ base64_encode((string)$row->id) }}"
                           title="Edit subscription" style="margin-left:6px;">✎</a>
                    @elseif (!$subscriptionMode && (int)$row->status === 1 && $ct !== 'subscription')
                        <a href="/admin/customer_balances/add/{{ base64_encode((string)$row->id) }}" title="Edit" style="margin-left:6px;">✎</a>
                    @elseif ($subscriptionMode && (int)$row->status === 1 && $subscriptionUserId)
                        <a href="/admin/customer_balances/addsubscription/{{ base64_encode((string)$subscriptionUserId) }}/{{ base64_encode((string)$row->id) }}"
                           title="Edit" style="margin-left:6px;">✎ Edit</a>
                    @endif
                    @if (!$subscriptionMode)
                        <a href="/admin/customer_balances/relatedpayments/{{ base64_encode((string)$row->id) }}"
                           title="Details" style="margin-left:6px;">🔍</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $subscriptionMode ? 10 : 11 }}" style="padding:12px;">No records.</td></tr>
        @endforelse
    </tbody>
</table>

@if ($records->hasPages())
    <div style="margin-top:12px;">
        Page {{ $records->currentPage() }} of {{ $records->lastPage() }} ({{ $records->total() }} total)
        @if (!$records->onFirstPage())
            <a href="{{ $records->previousPageUrl() }}">Previous</a>
        @endif
        @if ($records->hasMorePages())
            <a href="{{ $records->nextPageUrl() }}">Next</a>
        @endif
    </div>
@endif
