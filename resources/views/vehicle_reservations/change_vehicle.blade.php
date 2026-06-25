@php
    $booking ??= [];
    $admin ??= false;
    $vehicleUrl = $admin ? url('admin/bookings/getVehicle') : url('vehicles/getVehicle');
    $fareUrl = $admin ? url('admin/vehicle_reservations/getfarecalculations') : url('vehicle_reservations/getfarecalculations');
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="col-sm-12">
        <div class="panel panel-flat">
            <div class="panel-body">
                <form action="#" method="POST" class="form-horizontal" id="updateVehicleDetails">
                    @csrf

                    <fieldset>
                        <legend class="text-semibold">Vehicle Details</legend>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Vehicle :</label>
                            <div class="col-lg-8">
                                <input type="text" name="vehicle_id" id="VehicleReservationVehicleId"
                                    style="width:100%;" value="{{ data_get($booking, 'vehicle_id', '') }}">
                            </div>
                        </div>
                        <div class="form-group" id="faredetails"></div>
                        <div class="form-group">
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-primary pull-right"
                                    onclick="updateReservationVehicle()">
                                    Save
                                </button>
                            </div>
                        </div>
                    </fieldset>

                    <input type="hidden" name="booking_id" id="VehicleReservationId"
                        value="{{ data_get($booking, 'id', '')}}">
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
        function format(item) {
            return item.tag;
        }

        jQuery("#VehicleReservationVehicleId").select2({
            data: {
                results: {},
                text: 'tag'
            },
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Vehicle ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ $vehicleUrl }}",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {
                        term: params
                    }
                },
                processResults: function (data) {
                    return {
                        results: jQuery.map(data, function (item) {
                            return {
                                tag: item.tag,
                                id: item.id
                            }
                        })
                    };
                }
            },
            initSelection: function (element, callback) {
                let vehicle_id = "{{ data_get($booking, 'vehicle_id', '') }}";
                if (vehicle_id.length > 0) {
                    jQuery.ajax({
                        url: "{{ $vehicleUrl }}",
                        dataType: "json",
                        type: "GET",
                        data: {
                            "id": vehicle_id
                        }
                    }).done(function (data) {
                        callback(data[0]);
                    });
                }
            }
        });

        jQuery("#VehicleReservationVehicleId").on('select2-selecting', function (e) {
            jQuery.blockUI({
                message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> loading...</h1>',
                css: { 'z-index': '9999' }
            });

            var prms = {
                _token: "{{ csrf_token() }}",
                bookingid: "{{ data_get($booking, 'id', '') }}",
                vehicleid: e.choice.id
            };

            jQuery.post("{{ $fareUrl }}", prms, function (resp) {
                if (resp.status) {
                    jQuery("#faredetails").html(resp.view);
                } else {
                    alert(resp.message);
                }
            }, 'json').done(function () {
                jQuery.unblockUI();
            });
        });
    });

</script>