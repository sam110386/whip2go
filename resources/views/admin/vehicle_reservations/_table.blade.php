<div class="table-responsive">
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Vehicle</th>
            <th>VIN</th>
            <th>Dealer</th>
            <th>Renter</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($bookings as $b)
            <tr>
                <td>{{ $b->id }}</td>
                <td>{{ $b->vehicle_unique_id }} - {{ $b->vehicle_name }}</td>
                <td>{{ $b->vin_no }}</td>
                <td>{{ trim(($b->owner_first_name ?? '') . ' ' . ($b->owner_last_name ?? '')) }}</td>
                <td>{{ trim(($b->renter_first_name ?? '') . ' ' . ($b->renter_last_name ?? '')) }}</td>
                <td>{{ $b->start_datetime ?? '' }}</td>
                <td>{{ $b->end_datetime ?? '' }}</td>
                <td>{{ (int)$b->status }}</td>
                <td style="white-space:nowrap;">
                    <button type="button" class="btn btn-xs btn-default" onclick="reservationStatus('{{ base64_encode((string)$b->id) }}', 2)">Cancel</button>
                    <button type="button" class="btn btn-xs btn-default" onclick="reservationStatus('{{ base64_encode((string)$b->id) }}', 3)">Complete</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="9">No reservations found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

@if ($bookings->hasPages())
    <div style="margin-top:14px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <span>Page {{ $bookings->currentPage() }} of {{ $bookings->lastPage() }} ({{ $bookings->total() }} total)</span>
        @if (!$bookings->onFirstPage())
            <a href="{{ $bookings->previousPageUrl() }}">Previous</a>
        @endif
        @if ($bookings->hasMorePages())
            <a href="{{ $bookings->nextPageUrl() }}">Next</a>
        @endif
    </div>
@endif

<script>
    function reservationStatus(id, status) {
        fetch('/admin/vehicle_reservations/changeSaveStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: id, status: status })
        }).then(function (r) { return r.json(); })
          .then(function (res) {
              if (!res || !res.status) {
                  alert((res && res.message) || 'Failed to update reservation');
                  return;
              }
              window.location.reload();
          }).catch(function () {
              alert('Failed to update reservation');
          });
    }
</script>

