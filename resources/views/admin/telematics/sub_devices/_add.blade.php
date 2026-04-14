<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="#" method="POST" id="AddDevice" class="form-horizontal">
        @csrf
        <fieldset>
            <legend class="text-semibold">Device Details</legend>
            <div class="form-group">
                <label class="col-lg-4 control-label">Name :</label>
                <div class="col-lg-8">
                    <input type="text" name="TelematicsDevice[device_name]" class="required form-control" value="{{ $device->device_name ?? '' }}">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-4 control-label">GPS Serial # :</label>
                <div class="col-lg-8">
                    <input type="text" name="TelematicsDevice[gps_serialno]" class="required form-control" value="{{ $device->gps_serialno ?? '' }}">
                </div>
            </div>
        </fieldset>
        <input type="hidden" name="TelematicsDevice[id]" value="{{ $device->id ?? '' }}">
        <input type="hidden" name="TelematicsDevice[sub_id]" value="{{ $subid }}">
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="saveDevice()">Save</button>
</div>
