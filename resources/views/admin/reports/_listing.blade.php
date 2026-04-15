@if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50])
@endif
<table class="table table-responsive" style="width:100%;">
    <thead>
        <tr>
            @include('partials.dispacher.sortable_header', ['columns' => [
                ['field' => 'increment_id', 'title' => 'Order'],
                ['field' => 'owner_first_name', 'title' => 'Dealer', 'sortable' => false],
                ['field' => 'renter_first_name', 'title' => 'Renter', 'sortable' => false],
                ['field' => 'start_datetime', 'title' => 'Start'],
                ['field' => 'end_datetime', 'title' => 'End'],
                ['field' => 'status', 'title' => 'Status'],
                ['field' => 'actions', 'title' => 'Actions', 'sortable' => false],
            ]])
        </tr>
    </thead>
    <tbody>
        @forelse($reportlists as $r)
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $r->increment_id ?? $r->id }}</td>
                <td style="padding:6px;">{{ trim(($r->owner_first_name ?? '') . ' ' . ($r->owner_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ trim(($r->renter_first_name ?? '') . ' ' . ($r->renter_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ $r->start_datetime }}</td>
                <td style="padding:6px;">{{ $r->end_datetime }}</td>
                <td style="padding:6px;">{{ $r->status }}</td>
                <td style="padding:6px;"><a href="/admin/reports/details/{{ base64_encode((string)$r->id) }}">Details</a></td>
            </tr>
        @empty
            <tr><td colspan="7" style="padding:10px;">No rows found.</td></tr>
        @endforelse
    </tbody>
</table>

@if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50])
@endif

