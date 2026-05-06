<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h5 class="modal-title">Booking Review Report</h5>
</div>
<div class="modal-body">
    <div class="row text-center">
        <div class="col-xs-6">
            <a href="{{ url('admin/booking_reviews/initial/' . $orderid) }}" class="btn btn-primary btn-rounded btn-xlg" title="Initial Review Report">Initial Review</a>
        </div>
        <div class="col-xs-6">
            <a href="{{ url('admin/booking_reviews/finalreview/' . $orderid) }}" class="btn btn-danger btn-rounded btn-xlg" title="Final Review Report">Final Review</a>
        </div>
    </div>
</div>
