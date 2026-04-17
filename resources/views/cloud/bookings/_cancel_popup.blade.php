<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Cancel booking</h3>
    <input type="hidden" id="cancel_orderid" value="{{ $orderid }}">
    <div style="margin-bottom:8px;">
        <label>Cancellation fee</label><br>
        <input type="number" step="0.01" id="cancel_fee" value="{{ (float)$cancellation_fee }}">
    </div>
    <div>
        <label>Cancel note</label><br>
        <textarea id="cancel_note" rows="3" style="width:100%;"></textarea>
    </div>
</div>
