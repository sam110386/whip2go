<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Change reservation vehicle</h3>
    <input type="hidden" id="reservation_id" value="{{ base64_encode((string)$reservation->id) }}">
    <label>Vehicle</label><br>
    <select id="new_vehicle_id" style="min-width:280px;">
        @foreach ($vehicles as $v)
            <option value="{{ $v->id }}" @selected((int)$v->id === (int)$reservation->vehicle_id)>{{ $v->vehicle_unique_id }} - {{ $v->vehicle_name }}</option>
        @endforeach
    </select>
</div>

