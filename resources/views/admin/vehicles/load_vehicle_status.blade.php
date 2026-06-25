@php
    $statuses = $commonService->getVehicleStatus();
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="#" method="POST" id="statusChangeForm" class="form-horizontal">
        @csrf

        <fieldset>
            <legend class="text-semibold">Select Vehicle Status</legend>
            <div class="form-group">
                <label class="col-lg-4 control-label">Status :</label>
                <div class="col-lg-8">
                    <select name="status" id="VehicleStatus" class="form-control">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ (isset($vehicle) && data_get($vehicle, 'status') == $value) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </fieldset>

        <input type="hidden" name="id" value="{{ data_get($vehicle, 'id') }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="changeVehicleStatus()">Process</button>
</div>