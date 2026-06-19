<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">

    <form action="#" method="POST" name="frmadmin" id="cancelReservationForm" class="form-horizontal">
        @csrf

        <div class="panel-body">
            <legend class="text-size-large text-bold">Cancel Booking:</legend>

            <div class="form-group">
                <label class="col-lg-2 control-label">Any Note :</label>
                <div class="col-lg-9">
                    <input type="text" name="cancel_note" id="VehicleReservationCancelNote"
                        class="form-control required" value="{{ old('cancel_note') }}">
                </div>
            </div>

            <div class="col-lg-12">
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="button" class="btn btn-primary pl-3 pr-3" onclick="processCancelReservation()">
                            Process <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="lease_id" value="{{ $lease_id }}">

    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Cancel</button>
</div>