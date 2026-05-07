@include('partials.dispacher.paging_box', ['paginator' => $tripLog, 'limit' => $limit ?? 100, 'position' => 'top'])

<em><strong>*EMF</strong>->Extra Mileage Fee, <strong>*EMINS</strong>->EMF Insurance <strong>*Toll(P)</strong>->Pending
    Toll, <strong>*Cal. Rent</strong>->Calculated Rent,<strong>*Ini. Fee</strong>->Scheduled Fee</em>

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
    <thead>
        <tr>
            @include('partials.dispacher.sortable_header', [
                'columns' => [
                    ['title' => 'Booking#', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Insu. By', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Vehicle', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Dealer', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Begin Date', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Start Date', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'End Date', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Customer', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Cal. Rent', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'EMF', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'EMINS', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Late Fee', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Deposit', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Insu. Fee', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Ini. Fee', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Toll', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Toll(P)', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Action', 'sortable' => false, 'style' => 'text-align:center;']
                ]
            ])
        </tr>
    </thead>
    <tbody>
        @forelse ($tripLog as $trip)
            @php
                $class = $commonService->checkAutoRenew($trip->renter_id, $trip->end_datetime);
            @endphp
            <tr id="tripRow{{ $trip->id }}">

            </tr>
        @empty
            <tr>
                <td colspan="18" style="text-align:center;">No orders found.</td>
            </tr>
        @endforelse
    </tbody>
</table>


@include('partials.dispacher.paging_box', ['paginator' => $tripLog, 'limit' => $limit ?? 100])