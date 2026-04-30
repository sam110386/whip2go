@extends('admin.layouts.app')

@section('title', 'Vehicle Unavailability')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Vehicle</span> - Unavailability
                </h4>
                <div class="heading-elements">
                    <a href="{{ url('admin/leases/index') }}" class="btn btn-default heading-btn">
                        <i class="icon-arrow-left8 position-left"></i> Return
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Unavailable Days</h5>
                <p class="text-muted no-margin-top">Vehicle ID: {{ (int) ($vehicleid ?? 0) }}</p>
            </div>

            <div class="panel-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ legacy_asset('css/fullcalendar.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ legacy_asset('js/assets/js/plugins/ui/moment/moment.min.js') }}"></script>
        <script src="{{ legacy_asset('js/assets/js/plugins/ui/fullcalendar/fullcalendar.min.js') }}"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                var vehicleId = {{ (int) ($vehicleid ?? 0) }};

                $('#calendar').fullCalendar({
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,agendaWeek,agendaDay'
                    },
                    displayEventTime: false,
                    defaultDate: new Date(),
                    selectable: true,
                    selectHelper: true,
                    select: function (start, end) {
                        var con = confirm("Are you sure you want to save this selection ?");
                        if (con) {
                            var eventData = {
                                vehicle_id: vehicleId,
                                start: start.format(),
                                end: end.format()
                            };
                            $.ajax({
                                type: "post",
                                url: "{{ url('admin/leases/addunavailability') }}",
                                data: eventData,
                                dataType: "json",
                                success: function (data) {
                                    if (data.status) {
                                        $('#calendar').fullCalendar('renderEvent', eventData, true);
                                    } else {
                                        alert(data.message);
                                    }
                                }
                            });
                        }
                        $('#calendar').fullCalendar('unselect');
                    },
                    loading: function (bool) {
                        if (bool) {
                            $("#calendar").block({
                                message: '<i class="icon-spinner2 spinner"></i>',
                                overlayCSS: { backgroundColor: '#fff', opacity: 0.8, cursor: 'wait' },
                                css: { border: 0, padding: 0, backgroundColor: 'none' }
                            });
                        } else {
                            $("#calendar").unblock();
                        }
                    },
                    eventRender: function (event, element) {
                        element.append("<span class='closeon'><i class='glyphicon glyphicon-trash'></i></span>");
                        element.css('background-color', '#E62E0E');
                        element.find(".closeon").click(function () {
                            if (confirm("Are you sure you want to remove this day?")) {
                                var id = event._id;
                                $.ajax({
                                    type: "post",
                                    dataType: "json",
                                    url: "{{ url('admin/leases/remove') }}/" + id,
                                    success: function (data) {
                                        if (data.status) {
                                            $('#calendar').fullCalendar('removeEvents', event._id);
                                        } else {
                                            alert(data.message);
                                        }
                                    }
                                });
                            }
                        });
                    },
                    eventLimit: true,
                    events: function (start, end, timezone, callback) {
                        $.ajax({
                            url: "{{ url('admin/leases/load') }}",
                            dataType: 'json',
                            data: {
                                vehicle_id: vehicleId,
                                start: start.unix(),
                                end: end.unix()
                            },
                            success: function (events) {
                                callback(events);
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
@endsection