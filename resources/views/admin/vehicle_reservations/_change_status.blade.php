<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Change reservation status</h3>
    <input type="hidden" id="reservation_id" value="{{ base64_encode((string)$reservation->id) }}">
    <select id="reservation_new_status">
        @foreach ([0 => 'Pending', 1 => 'Accepted', 2 => 'Cancelled', 3 => 'Completed'] as $k => $label)
            <option value="{{ $k }}" @selected((int)$reservation->status === (int)$k)>{{ $label }}</option>
        @endforeach
    </select>
</div>

