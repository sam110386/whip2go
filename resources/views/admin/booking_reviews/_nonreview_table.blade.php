<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Booking#</th>
                <th>Vehicle#</th>
                <th>Start</th>
                <th>End</th>
                <th>Customer</th>
                <th class="text-right">Rent+Tax</th>
                <th class="text-right">Deposit</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($nonreviews as $o)
                <tr>
                    <td>{{ $o->increment_id }}</td>
                    <td>{{ $o->vehicle_unique_id }}</td>
                    <td>{{ $o->start_datetime }}</td>
                    <td>{{ $o->end_datetime }}</td>
                    <td>{{ $o->renter_name }}</td>
                    <td class="text-right">{{ number_format((float)$o->rent + (float)$o->tax, 2) }}</td>
                    <td class="text-right">{{ number_format((float)$o->deposit, 2) }}</td>
                    <td class="text-center">
                        <a href="{{ $basePath }}/initial/{{ base64_encode((string)$o->id) }}" class="btn btn-xs btn-default">Initial</a>
                        <a href="{{ $basePath }}/finalreview/{{ base64_encode((string)$o->id) }}" class="btn btn-xs btn-default">Final</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No bookings pending review.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (isset($nonreviews) && method_exists($nonreviews, 'links'))
    <div class="pull-right">{{ $nonreviews->links() }}</div>
    <div class="clearfix"></div>
@endif
