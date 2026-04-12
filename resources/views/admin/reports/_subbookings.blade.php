<div style="font-size:13px;">
    <h3>Sub-bookings for #{{ $id }}</h3>
    <ul>
        @forelse($subs as $s)
            <li>#{{ $s->id }} ({{ $s->increment_id ?? 'n/a' }}) — status {{ $s->status }}</li>
        @empty
            <li>No sub-bookings</li>
        @endforelse
    </ul>
</div>

