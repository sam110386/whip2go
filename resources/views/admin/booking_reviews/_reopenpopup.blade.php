<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <form id="BookingReviewReopenForm" class="form-horizontal" method="POST">
        <legend class="text-semibold">Please choose</legend>

        <div class="form-group">
            <div class="col-lg-1 control-label">
                <input type="checkbox" name="BookingReview[reset_bad_debt]" value="1" class="form-control checkbox">
            </div>
            <label class="col-lg-8 control-label">
                Reset driver wallet bad debt for this booking
            </label>
        </div>

        <div class="form-group">
            <div class="col-lg-1 control-label">
                <input type="checkbox" name="BookingReview[refund_py]" value="1" class="form-control checkbox">
            </div>
            <label class="col-lg-8 control-label">
                Refund all dealer paid insurance to stripe, for this booking
            </label>
        </div>

        <input type="hidden" name="BookingReview[orderid]" value="{{ base64_encode($orderid) }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">
        Cancel
    </button>
    <button type="button" class="btn btn-primary" onclick="ReopenBooking()">
        Confirm and Reopen Booking
    </button>
</div>