{{-- Minimal booking summary for modal (dealer + cloud reports). --}}
<div class="panel">
    <div class="panel-body">
        @if(empty($order))
            <p>No booking found.</p>
        @else
            <div class="row">
                <div class="col-lg-12">
                    <legend>Booking</legend>
                    <div class="form-group">
                        <label class="col-lg-4">Booking #</label>
                        <div class="col-lg-8">{{ $order->increment_id ?? $order->id }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4">Customer</label>
                        <div class="col-lg-8">{{ trim(($renter_first_name ?? '') . ' ' . ($renter_last_name ?? '')) ?: '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4">Vehicle</label>
                        <div class="col-lg-8">{{ $order->vehicle_name ?? '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4">Start</label>
                        <div class="col-lg-8">{{ $order->start_datetime }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4">End</label>
                        <div class="col-lg-8">{{ $order->end_datetime }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4">Status</label>
                        <div class="col-lg-8">{{ $order->status }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4">Rent / Tax / DIA</label>
                        <div class="col-lg-8">{{ $order->rent }} / {{ $order->tax }} / {{ $order->dia_fee }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
