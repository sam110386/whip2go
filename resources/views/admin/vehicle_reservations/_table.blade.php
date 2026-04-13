<table style="width:100%; border-collapse:collapse; font-size:13px;">
    <thead>
        <tr style="border-bottom:2px solid #ccc; text-align:left;">
            <th style="padding:6px;">#</th>
            <th style="padding:6px;">Vehicle</th>
            <th style="padding:6px;">VIN</th>
            <th style="padding:6px;">Dealer</th>
            <th style="padding:6px;">Renter</th>
            <th style="padding:6px;">Start</th>
            <th style="padding:6px;">End</th>
            <th style="padding:6px;">Status</th>
            <th style="padding:6px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($bookings as $b)
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:6px;">{{ $b->id }}</td>
                <td style="padding:6px;">{{ $b->vehicle_unique_id }} - {{ $b->vehicle_name }}</td>
                <td style="padding:6px;">{{ $b->vin_no }}</td>
                <td style="padding:6px;">{{ trim(($b->owner_first_name ?? '') . ' ' . ($b->owner_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ trim(($b->renter_first_name ?? '') . ' ' . ($b->renter_last_name ?? '')) }}</td>
                <td style="padding:6px;">{{ $b->start_datetime ?? '' }}</td>
                <td style="padding:6px;">{{ $b->end_datetime ?? '' }}</td>
                <td style="padding:6px;">{{ (int)$b->status }}</td>
                <td style="padding:6px; white-space:nowrap;">
                    <button type="button" onclick="reservationStatus('{{ base64_encode((string)$b->id) }}', 2)">Cancel</button>
                    <button type="button" onclick="reservationStatus('{{ base64_encode((string)$b->id) }}', 3)">Complete</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" style="padding:12px;">No reservations found.</td></tr>
        @endforelse
    </tbody>
</table>

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

