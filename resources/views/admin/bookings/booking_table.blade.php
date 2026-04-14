<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th style="text-align:center;">Booking #</th>
                <th style="text-align:center;">Insu. payer</th>
                <th>Vehicle</th>
                <th style="text-align:center;">Customer</th>
                <th style="text-align:center;">Start</th>
                <th style="text-align:center;">End</th>
                <th style="text-align:center;">Rent+tax+DIA</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trips as $trip)
                <tr id="booking_{{ (int)($trip->id ?? 0) }}">
                    @include('admin.bookings._single_row', ['trip' => $trip])
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">No orders found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if (method_exists($trips, 'links'))
    <div class="text-center">{{ $trips->links() }}</div>
@endif
