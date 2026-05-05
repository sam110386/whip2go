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
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50, 'positon' => "top"])
@endif

<table class="table table-responsive" style="width:100%;">
    <thead>
        <tr>
            @include('partials.dispacher.sortable_header', ['columns' => [
                ['title' => 'Booking#', 'field' => 'increment_id'],
                ['title' => 'Status', 'field' => 'status'],
                ['title' => 'Total', 'field' => 'paid_amount'],
                ['title' => 'Rent', 'field' => 'rent'],
                ['title' => 'Tax', 'field' => 'tax'],
                ['title' => 'Rental EMF', 'sortable' => false],
                ['title' => 'REMF TAX', 'sortable' => false],
                ['title' => 'EMF Insu.', 'sortable' => false],
                ['title' => 'Insurance', 'field' => 'insurance_amt'],
                ['title' => 'Initial Fee', 'sortable' => false],
                ['title' => 'Initial Fee Tax', 'sortable' => false],
                ['title' => 'Cancellation Fee', 'sortable' => false],
                ['title' => 'Action', 'sortable' => false],
            ]])
        </tr>
    </thead>
    <tbody>
        @forelse ($reportlists as $trip)
            @php $oid = base64_encode((string)$trip->id); @endphp
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->increment_id }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $statusLabel($trip->status) }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->paid_amount }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->rent }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->tax }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->extra_mileage_fee }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->emf_tax }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->dia_insu }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->insurance_amt }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->initial_fee }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->initial_fee_tax }}</td>
                <td style="padding:6px;" onclick="openTripDetails('{{ $oid }}')">{{ $trip->cancellation_fee }}</td>
                <td style="padding:6px;">
                    <a href="/admin/transactions/updatetransaction/{{ $oid }}" title="Payment Details"><i class="glyphicon glyphicon-edit"></i></a>
                    &nbsp;
                    <a href="javascript:;" onclick="Updateenddatetime('{{ $oid }}')" title="Update Actual End Date Time"><i class="glyphicon glyphicon-time"></i></a>
                </td>
            </tr>
        @empty
            <tr><td colspan="14" style="padding:12px;">No records.</td></tr>
        @endforelse
    </tbody>
</table>

@if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50])
@endif
