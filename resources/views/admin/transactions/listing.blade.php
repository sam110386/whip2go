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

@if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50])
@endif

<table class="table table-responsive" style="width:100%;">
    <thead>
        <tr>
            @include('partials.dispacher.sortable_header', ['columns' => [
                ['field' => 'increment_id', 'title' => 'Booking#'],
                ['field' => 'status', 'title' => 'Status'],
                ['field' => 'renter_first_name', 'title' => 'Customer', 'sortable' => false],
                ['field' => 'paid_amount', 'title' => 'Total'],
                ['field' => 'rent', 'title' => 'Rent'],
                ['field' => 'tax', 'title' => 'Tax'],
                ['field' => 'extra_mileage_fee', 'title' => 'EMF'],
                ['field' => 'emf_tax', 'title' => 'EMF tax'],
                ['field' => 'dia_insu', 'title' => 'EMF ins.'],
                ['field' => 'insurance_amt', 'title' => 'Insurance'],
                ['field' => 'initial_fee', 'title' => 'Initial'],
                ['field' => 'initial_fee_tax', 'title' => 'Init. tax'],
                ['field' => 'cancellation_fee', 'title' => 'Cancel fee'],
                ['field' => 'action', 'title' => 'Action', 'sortable' => false],
            ]])
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

@if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50])
@endif
