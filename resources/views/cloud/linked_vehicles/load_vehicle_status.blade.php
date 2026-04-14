<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form method="post" id="statusChangeForm" class="form-horizontal">
        @csrf
        <fieldset>
            <legend class="text-semibold">Select Vehicle Status</legend>
            <div class="form-group">
                <label class="col-lg-4 control-label">Status :</label>
                <div class="col-lg-8">
                    <select name="Vehicle[status]" class="form-control">
                        @foreach ($statusOptions as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected((int)$vehcile->status === (int)$statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </fieldset>
        <input type="hidden" name="Vehicle[id]" value="{{ $vehcile->id }}">
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="changeVehicleStatus()">Process</button>
</div>
