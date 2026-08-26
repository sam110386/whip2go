<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <form action="" method="POST" id="loadupdateodometer" class="form-horizontal">
        @csrf

        <fieldset>
            <legend class="text-semibold">Odometer Reading</legend>

            <div class="form-group">
                <label class="col-lg-3 control-label">
                    Current Reading :
                </label>
                <div class="col-lg-4">
                    <input id="TextCurrentOdomter" type="text" name="current_odomter" maxlength="30"
                        class="form-control number">
                </div>
                <div class="col-lg-4">
                    <button type="button" class="btn btn-warning pull-right" onclick="pullVehicleOdometer(true)">
                        Pull Current Reading <i class="icon-sync position-left"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label">
                    Update To :
                </label>
                <div class="col-lg-3 control-label">
                    <input type="radio" name="current_odomter_to" value="start_odometer" checked />
                    Start Odometer
                </div>
                <div class="col-lg-3 control-label">
                    <input type="radio" name="current_odomter_to" value="end_odometer" />
                    End Odometer
                </div>
            </div>
        </fieldset>
        <input type="hidden" name="booking" value="{{ base64_encode($booking ?? '') }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">
        Cancel
    </button>
    <button type="button" class="btn btn-primary" onclick="saveBookingOdometer()">
        Update
    </button>
</div>