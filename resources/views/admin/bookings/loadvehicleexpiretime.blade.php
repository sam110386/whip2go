<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

@if (!empty($vehicle))
    <div class="modal-body">
        <form action="" method="POST" id="loadvehicleexpiretime" class="form-horizontal">
            @csrf

            <fieldset>
                <legend class="text-semibold">Select Date Time</legend>

                <div class="form-group">
                    <label class="col-lg-4 control-label">Choose :</label>
                    <div class="col-lg-8">
                        @php
                            $passtimeVal = !empty($vehicle->passtime_threshold)
                                ? \Carbon\Carbon::parse($vehicle->passtime_threshold)->setTimezone($timezone ?? config('app.timezone'))->format('m/d/Y h:i A')
                                : \Carbon\Carbon::now($timezone ?? config('app.timezone'))->format('m/d/Y h:i A');
                        @endphp
                        <input type="text" name="passtime_threshold" class="form-control" value="{{ $passtimeVal }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label">Note :</label>
                    <div class="col-lg-8">
                        <textarea name="note"
                            class="form-control required">{{ !empty($orderExtlog) ? $orderExtlog->note : '' }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label">Amount To Charge :</label>
                    <div class="col-lg-8">
                        <input type="text" name="amt" class="form-control required"
                            value="{{ !empty($orderExtlog) ? $orderExtlog->amt : '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Dont Count Towards Total Extensions Count :</label>
                    <div class="col-lg-4 control-label">
                        <input type="checkbox" name="admin_count" value="1" class="fancytree-checkbox">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Unpaid Late Fee :</label>
                    <div class="col-lg-4 control-label">
                        {{ $unpaidlatefee }}
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">Charge Late Fee :</label>
                    <div class="col-lg-4 control-label">
                        <input type="checkbox" name="charge_late_fee" value="1" class="fancytree-checkbox">
                    </div>
                </div>
            </fieldset>

            <input type="hidden" name="booking" value="{{ $booking }}">
            <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
        </form>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="processVehicleLockTime()">Update</button>
    </div>
@else
    <div class="modal-body">
        <strong>Sorry, you are not authorized user for this action.</strong>
    </div>
@endif

<script src="{{ asset('js/assets/js/plugins/pickers/datetimepicker.js') }}"></script>