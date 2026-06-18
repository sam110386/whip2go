<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

@if(empty($booking))
    <div class="modal-body">
        <p class="text-danger">Sorry, you are not an authorized user for this action.</p>
    </div>
@else
    @php
        $tz = data_get($booking, 'timezone', config('app.timezone'));
        $start = \Carbon\Carbon::parse(data_get($booking, 'start_datetime'))->setTimezone($tz);
        $end = \Carbon\Carbon::parse(data_get($booking, 'end_datetime'))->setTimezone($tz);
        $startDateFormatted = $start->format('m/d/Y');
        $endDateFormatted = $end->format('m/d/Y');

        if ($start->isPast()) {
            $startDateFormatted = now()->timezone($tz)->format('m/d/Y');
            $daysDifference = $start->diffInDays($end);
            $endDateFormatted = now()->timezone($tz)->addDays($daysDifference)->format('m/d/Y');
        }

    @endphp

    <div class="modal-body">
        <div class="col-sm-12">
            <div class="panel panel-flat">
                <div class="panel-body">
                    <form action="#" method="POST" class="form-horizontal" id="updateVehicleDetails">
                        @csrf

                        <fieldset>
                            <legend class="text-semibold">Pending Booking Details</legend>

                            <div class="row form-group">
                                <div class="col-md-2">From Date </div>
                                <div class="col-md-3">
                                    <input type="text" name="daterangefrom" id="daterangefrom"
                                        class="form-control required date" value="{{ $startDateFormatted }}"
                                        rel-date="{{ $startDateFormatted }}">
                                </div>
                                <div class="col-md-2"> Time</div>
                                <div class="col-md-3">
                                    <input type="text" name="start_time" class="form-control timeClass"
                                        value="{{ $start->format('h:i A') }}">
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-md-2">To Date</div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control required date" name="daterangeto"
                                        id="daterangeto" value="{{ $endDateFormatted }}">
                                </div>
                                <div class="col-md-2"> Time</div>
                                <div class="col-md-3">
                                    <input type="text" name="end_time" class="form-control timeClass"
                                        value="{{ $end->format('h:i A') }}">
                                </div>
                            </div>
                        </fieldset>

                        <input type="hidden" name="id" value="{{ data_get($booking, 'id')}}">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-primary pull-left" onclick="updateDatetime()">Save</button>
        <button type="button" class="btn btn-primary pull-right" data-dismiss="modal">Close</button>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function () {
            $('.timeClass').timepicki();
        });
    </script>
@endif