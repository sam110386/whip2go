<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

@if (!empty($vehicle))
    <div class="modal-body">
        <form action="#" method="post" id="loadvehiclegps" class="form-horizontal">
            <fieldset>
                <legend class="text-semibold">Vehicle Details</legend>
                <div class="form-group">
                    <label class="col-lg-3 control-label">Starter Device Serial# :</label>
                    <div class="col-lg-6">
                        <input type="text" name="Text[passtime_serialno]" maxlength="30" class="form-control" value="{{ $vehicle->passtime_serialno ?? '' }}">
                    </div>
                    <div class="col-lg-3">
                        <button type="button" class="btn-warning pull-right btn" onclick="processVehicleGps(true)">Sync Vehicle <i class="icon-sync position-left"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-3 control-label">GPS Device Serial# :</label>
                    <div class="col-lg-6">
                        <input type="text" name="gps_serialno" maxlength="30" class="form-control" value="{{ $vehicle->gps_serialno ?? '' }}">
                    </div>
                    <div class="col-lg-3"></div>
                </div>
                <div class="form-group">
                    <label class="col-lg-3 control-label">Plate # :</label>
                    <div class="col-lg-6">
                        <input type="text" name="Text[plate_number]" maxlength="20" class="form-control" value="{{ $vehicle->plate_number ?? '' }}">
                    </div>
                    <div class="col-lg-3"></div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">Disable Vehicle Temporarily :</label>
                    <div class="col-lg-8 control-label">
                        <input type="checkbox" id="VehicleDisableTemp" class="checkbox" onclick="disableVehicleTemp()" {{ (int)($vehicle->passtime_status ?? 0) === 2 ? 'checked' : '' }}>
                    </div>
                </div>
            </fieldset>
            <input type="hidden" id="TextBooking" name="Text[booking]" value="{{ base64_encode((string)($booking ?? 0)) }}">
            <input type="hidden" id="TextVehicleId" name="vehicle_id" value="{{ (int)($vehicle->id ?? 0) }}">
            <input type="hidden" name="orderid" value="{{ base64_encode((string)($booking ?? 0)) }}">
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="processVehicleGps()">Update</button>
        @if (($vehicle->gps_provider ?? '') === 'smartcar' || ($vehicle->passtime ?? '') === 'smartcar')
            <button type="button" class="btn btn-primary mt-5" onclick="getEvBattery()">Battery (<span class="batteryspan">{{ (int)($vehicle->battery ?? 0) }}</span>%)</button>
        @endif
        @if (($vehicle->passtime ?? '') === 'geotabkeyless')
            <button type="button" class="btn btn-primary mt-5" onclick="geotabkeylessLock()">Keyless Lock</button>
            <button type="button" class="btn btn-primary mt-5" onclick="geotabkeylessUnLock()">Keyless Unlock</button>
        @endif
    </div>
@else
    <div class="modal-body">
        <strong>Sorry, you are not authorized user for this action.</strong>
    </div>
@endif

