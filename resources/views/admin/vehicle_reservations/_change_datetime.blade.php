<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Change reservation datetime</h3>
    <input type="hidden" id="reservation_id" value="{{ base64_encode((string)$reservation->id) }}">
    <label>Start</label><br>
    <input type="text" id="reservation_start_datetime" value="{{ $reservation->start_datetime ?? '' }}"><br><br>
    <label>End</label><br>
    <input type="text" id="reservation_end_datetime" value="{{ $reservation->end_datetime ?? '' }}">
</div>

