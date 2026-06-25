@php
    $status ??= [];
    $booking ??= [];
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="col-sm-12">
        <div class="panel panel-flat">
            <div class="panel-body">
                <form action="#" method="POST" class="form-horizontal" id="updateVehicleStatus">
                    @csrf

                    <fieldset>
                        <legend class="text-semibold">Details</legend>

                        <div class="form-group">
                            <label class="col-lg-2 control-label">Status :</label>
                            <div class="col-lg-8">
                                <select name="status" id="status" class="form-control">
                                    @foreach($status as $value => $label)
                                        <option value="{{ $value }}" @selected((int) data_get($booking, 'status', 0) === (int) $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="help">
                            <label class="col-lg-2 control-label" id="noteLabel">Note:</label>
                            <div class="col-lg-8">
                                <input type="text" name="note" id="note" class="form-control" maxlength="255" value="">
                            </div>
                        </div>

                        <div class="form-group" id="faredetails"></div>

                        <div class="form-group">
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-primary pull-right"
                                    onclick="updateReservationStatus()">Save</button>
                            </div>
                        </div>
                    </fieldset>

                    <input type="hidden" name="id" id="booking_id" value="{{ data_get($booking, 'id') }}">
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>

<script type="text/javascript">

    $(function () {
        $("#updateVehicleStatus #status").change(function () {
            let vl = $(this).val();
            let $noteInput = $('#updateVehicleStatus #note');
            let $label = $("#updateVehicleStatus #noteLabel");

            if (vl == 6 || vl == 7) {
                $label.html("By Time:");
                $noteInput.datetimepicker({});
            } else {
                $label.html("Note:");

                if (typeof $noteInput.data("DateTimePicker") !== 'undefined') {
                    $noteInput.data("DateTimePicker").destroy();
                }

                let txt = 'The dealer is aware of your booking and is now planning to prep the vehicle. We will be in touch soon with a pick up time.';

                if (vl == 5) {
                    txt = 'Your vehicle is now being prepped. We will know shortly the pick up time.';
                }

                $noteInput.val(txt);
            }
        });
    });

</script>