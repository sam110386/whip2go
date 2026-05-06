@include('partials.dispacher.paging_box', ['paginator' => $tripLog, 'limit' => $limit ?? 100, 'position' => 'top'])

<em><strong>*EMF</strong>->Extra Mileage Fee, <strong>*EMINS</strong>->EMF Insurance <strong>*Toll(P)</strong>->Pending
    Toll, <strong>*Cal. Rent</strong>->Calculated Rent,<strong>*Ini. Fee</strong>->Scheduled Fee</em>

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
    <thead>
        <tr>
            @include('partials.dispacher.sortable_header', ['label' => 'Booking #', 'sort' => 'id', 'align' => 'center'])
            @include('partials.dispacher.sortable_header', ['label' => 'Insu. payer', 'sort' => 'insurance_payer', 'align' => 'center'])
            @include('partials.dispacher.sortable_header', ['label' => 'Vehicle', 'sort' => 'vehicle_name'])
            <th style="text-align:center;">Customer</th>
            @include('partials.dispacher.sortable_header', ['label' => 'Start', 'sort' => 'start_datetime', 'align' => 'center'])
            @include('partials.dispacher.sortable_header', ['label' => 'End', 'sort' => 'end_datetime', 'align' => 'center'])
            <th style="text-align:center;">Rent+tax+DIA</th>
            <th style="text-align:center;">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($tripLog as $trip)
            <tr id="booking_{{ (int) ($trip->id ?? 0) }}">
                @include('admin.bookings._single_row', ['trip' => $trip])
            </tr>
        @empty
            <tr>
                <td colspan="8" style="text-align:center;">No orders found.</td>
            </tr>
        @endforelse
    </tbody>
</table>


@include('partials.dispacher.paging_box', ['paginator' => $tripLog, 'limit' => $limit ?? 100])