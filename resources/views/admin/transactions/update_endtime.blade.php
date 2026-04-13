<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Update booking end time</h3>
    <input type="hidden" id="endtime_order_id" value="{{ $order->id }}">
    <label>End timing</label><br>
    <input type="text" id="endtime_value" value="{{ $order->end_timing ?? '' }}">
</div>

