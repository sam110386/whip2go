@php $prefix = $prefix ?? 'reports'; @endphp
<table style="width:100%; border-collapse:collapse; font-size:13px;" class="table table-responsive">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            @include('partials.dispacher.sortable_header', [
                'columns' => [
                    ['title' => '', 'sortable' => false, 'style' => 'padding:6px;width:36px;'],
                    ['title' => 'Booking#', 'field' => 'increment_id', 'style' => 'padding:6px;'],
                    ['title' => 'Status', 'sortable' => false, 'style' => 'padding:6px;'],
                    ['title' => 'Vehicle', 'sortable' => false, 'style' => 'padding:6px;'],
                    ['title' => 'Start', 'field' => 'start_datetime', 'style' => 'padding:6px;'],
                    ['title' => 'End', 'field' => 'end_datetime', 'style' => 'padding:6px;'],
                    ['title' => 'Duration', 'sortable' => false, 'style' => 'padding:6px;'],
                    ['title' => 'Customer', 'sortable' => false, 'style' => 'padding:6px;'],
                    ['title' => 'Distance', 'sortable' => false, 'style' => 'padding:6px;'],
                    ['title' => 'Total', 'sortable' => false, 'style' => 'padding:6px;'],
                    ['title' => 'Insurance', 'sortable' => false, 'style' => 'padding:6px;'],
                    ['title' => 'Tolls', 'sortable' => false, 'style' => 'padding:6px;'],
                    ['title' => 'Documents', 'sortable' => false, 'style' => 'padding:6px;width:80px;']
                ]
            ])
        </tr>
    </thead>
    <tbody>
        @forelse($reportlists as $r)
            @php
                $statusLabel = ($r->status ?? '') == 3 ? 'Completed' : (($r->status ?? '') == 2 ? 'Canceled' : 'Incomplete');
                $dist = (($r->status ?? '') == 3 && $r->end_odometer !== null && $r->start_odometer !== null)
                    ? ((float) $r->end_odometer - (float) $r->start_odometer)
                    : 0;
                $tid = base64_encode((string) ($r->id ?? ''));
                $openTrip = $prefix === 'cloud'
                    ? "openTripDetails('{$tid}', this)"
                    : "openTripDetails('{$tid}')";
            @endphp
            <tr id="tr_{{ $r->id }}" rel-parent="no">
                <td style="padding:6px;"></td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $r->increment_id ?? $r->id }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $statusLabel }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $r->vehicle_name ?? '' }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $r->start_datetime }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $r->end_datetime }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">—</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $dist }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ $r->paid_amount ?? 0 }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ (float)($r->insurance_amt ?? 0) + (float)($r->dia_insu ?? 0) }}</td>
                <td style="padding:6px;" onclick="{{ $openTrip }}">{{ (float)($r->toll ?? 0) + (float)($r->pending_toll ?? 0) }}</td>
                <td style="padding:6px;"></td>
            </tr>
        @empty
            <tr><td colspan="13" style="padding:10px;">No rows found.</td></tr>
        @endforelse
    </tbody>
</table>

@if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50])
@endif
