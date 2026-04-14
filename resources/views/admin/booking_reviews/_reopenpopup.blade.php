<div style="padding:12px;">
    <h4>Re-open booking #{{ $orderid }}</h4>
    <form id="frmReopen" onsubmit="return false;">
        <input type="hidden" name="BookingReview[orderid]" value="{{ base64_encode((string)$orderid) }}">
        <p><label><input type="checkbox" name="BookingReview[reset_bad_debt]" value="1"> Reset driver wallet bad debt for this booking</label></p>
        <p><label><input type="checkbox" name="BookingReview[refund_py]" value="1"> Refund dealer-paid insurance (not executed in Laravel)</label></p>
        <p><button type="button" onclick="
            var fd = new FormData(document.getElementById('frmReopen'));
            fetch('{{ $basePath ?? '/admin/booking_reviews' }}/reopenbooking', {method:'POST', body: fd}).then(r=>r.json()).then(function(d){ alert(d.message||''); if(d.status) location.reload(); });
        ">Submit</button></p>
    </form>
</div>
