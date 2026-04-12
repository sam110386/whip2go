<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Message history</h3>
    <div style="margin-bottom:8px;">Owner: {{ $ownerId }} | Renter: {{ $renterId }}</div>
    <div style="max-height:340px; overflow:auto; border:1px solid #ddd; padding:8px;">
        @forelse($rows as $m)
            <div style="margin-bottom:10px;">
                <div><strong>{{ (int)$m->sender_id === (int)$ownerId ? 'Owner' : 'Renter' }}</strong> <span style="color:#666;">{{ $m->created ?? '' }}</span></div>
                <div>{{ $m->message }}</div>
            </div>
        @empty
            <div>No messages found.</div>
        @endforelse
    </div>
</div>

