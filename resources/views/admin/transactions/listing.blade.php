@php
    $statusLabel = function ($s) {
        $s = (int)$s;
        if ($s === 3) {
            return 'Completed';
        }
        if ($s === 2) {
            return 'Canceled';
        }

        return 'Incomplete';
    };
@endphp
<table style="width:100%; border-collapse:collapse; font-size:12px;">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;">Booking#</th>
            <th style="padding:6px;">Status</th>
            <th style="padding:6px;">Customer</th>
            <th style="padding:6px;">Total</th>
            <th style="padding:6px;">Rent</th>
            <th style="padding:6px;">Tax</th>
            <th style="padding:6px;">EMF</th>
            <th style="padding:6px;">EMF tax</th>
            <th style="padding:6px;">EMF ins.</th>
            <th style="padding:6px;">Insurance</th>
            <th style="padding:6px;">Initial</th>
            <th style="padding:6px;">Init. tax</th>
            <th style="padding:6px;">Cancel fee</th>
            <th style="padding:6px;">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($reportlists as $trip)
            @php $oid = base64_encode((string)$trip->id); @endphp
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $trip->increment_id }}</td>
                <td style="padding:6px;">{{ $statusLabel($trip->status) }}</td>
                <td style="padding:6px;">{{ trim(($trip->renter_first_name ?? '') . ' ' . ($trip->renter_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ $trip->paid_amount }}</td>
                <td style="padding:6px;">{{ $trip->rent }}</td>
                <td style="padding:6px;">{{ $trip->tax }}</td>
                <td style="padding:6px;">{{ $trip->extra_mileage_fee }}</td>
                <td style="padding:6px;">{{ $trip->emf_tax }}</td>
                <td style="padding:6px;">{{ $trip->dia_insu }}</td>
                <td style="padding:6px;">{{ $trip->insurance_amt }}</td>
                <td style="padding:6px;">{{ $trip->initial_fee }}</td>
                <td style="padding:6px;">{{ $trip->initial_fee_tax }}</td>
                <td style="padding:6px;">{{ $trip->cancellation_fee }}</td>
                <td style="padding:6px;"><a href="/admin/transactions/updatetransaction/{{ $oid }}">Details</a></td>
            </tr>
        @empty
            <tr><td colspan="14" style="padding:12px;">No records.</td></tr>
        @endforelse
    </tbody>
</table>

@if ($reportlists->hasPages())
    <div style="margin-top:12px;">
        Page {{ $reportlists->currentPage() }} of {{ $reportlists->lastPage() }} ({{ $reportlists->total() }} total)
        @if (!$reportlists->onFirstPage())
            <a href="{{ $reportlists->previousPageUrl() }}">Previous</a>
        @endif
        @if ($reportlists->hasMorePages())
            <a href="{{ $reportlists->nextPageUrl() }}">Next</a>
        @endif
    </div>
@endif
