@extends('admin.layouts.app')

@php
    $title ??= 'Pending Booking';
    $chkrstatus = $commonService->getCheckrTypeValueForEditable();
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/timepicki.css') }}">

    <style type="text/css">
        .datepicker .prev,
        .datepicker .next {
            background: none;
        }

        .kv-fileinput-caption.icon-visible {
            display: flex;
        }

        .kv-fileinput-caption .file-caption-name {
            border: none;
            padding: 0;
        }

        .panel-body {
            padding: 20px 10px;
        }
    </style>

@endpush

@section('title', $title)

@section('content')

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Pending</span> Booking
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/vehicle_reservations/all') }}" class="btn left-margin btn-cancel">
                    All Pending Booking
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div style="width:100%; overflow: visible;" id="postsPaging">
                @include('admin.vehicle_reservations.elements.index')
            </div>
        </div>
    </div>



@endsection

@push('scripts')

    <script src="{{ legacy_asset('js/assets/js/plugins/forms/editable/editable.min.js') }}"></script>
    <script src="{{ legacy_asset('js/mvrbox.js') }}"></script>
    <script src="{{ legacy_asset('js/cluereport.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
    <script src="{{ legacy_asset('js/admin_booking.js') }}"></script>
    <script src="{{ legacy_asset('js/measureone/functions.js') }}"></script>
    <script src="{{ legacy_asset('js/admin_plaid.js') }}"></script>
    <script src="{{ legacy_asset('js/insurance/insurance.js') }}"></script>
    <script src="{{ legacy_asset('js/insurance/driverfinancedquote.js') }}"></script>
    <script src="{{ legacy_asset('js/insuranceprovider/insurance_provider.js') }}"></script>
    <script src="{{ legacy_asset('js/prepaidplan/prepaidplan.js') }}"></script>
    <script src="{{ legacy_asset('js/axle/axle.js') }}"></script>
    <script src="{{ legacy_asset('js/jquery.maskedinput.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/pickers/datetimepicker.js') }}"></script>
    <script src="{{ legacy_asset('js/timepicki.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/media/fancybox.min.js') }}"></script>
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/uploaders/fileinput.min.js') }}"></script>
    <script src="{{ legacy_asset('js/admin_setting.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function () {

            $(".fancybox").fancybox();

            $(document).on('click', '.page-link, .sort-link', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (url && url !== '#' && url !== 'javascript:void(0)') {
                    loadListing(url);
                }
            });

            $(document).on('submit', '#frmSearchadmin', function (e) {
                e.preventDefault();
                var form = $(this);
                var isClearFilter = false;

                if (e.originalEvent && e.originalEvent.submitter) {
                    var btn = $(e.originalEvent.submitter);
                    if (btn.attr('name') === 'ClearFilter') {
                        isClearFilter = true;
                    }
                }

                if (isClearFilter) {
                    form[0].reset();
                    var baseUrl = form.attr('action');
                    loadListing(baseUrl + '?ClearFilter=1', baseUrl);
                } else {
                    var formData = form.serialize();
                    var url = form.attr('action') + '?' + formData;
                    loadListing(url);
                }
            });

            $(document).on('change', '.ajax-limit', function (e) {
                e.preventDefault();
                var form = $(this).closest('form');
                var url = window.location.pathname + '?' + $('#frmSearchadmin').serialize() + '&' + form.serialize();
                loadListing(url);
            });

            function loadListing(url, historyUrl) {
                if (typeof historyUrl === 'undefined') {
                    historyUrl = url;
                }
                $('#listing').css('opacity', '0.5');

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function (data) {
                        $('#listing').html(data);
                        $('#listing').css('opacity', '1');
                        window.history.pushState(null, null, historyUrl);
                    },
                    error: function (xhr) {
                        $('#listing').css('opacity', '1');
                        console.error('AJAX Load Error:', xhr);
                    }
                });
            }

            window.onpopstate = function () {
                loadListing(window.location.href);
            };

        });

        $(function () {
            // Editable
            // Change defaults
            $.fn.editable.defaults.highlight = false;
            $.fn.editable.defaults.mode = 'popup';
            $.fn.editableform.template = '<form class="editableform form-horizontal">' +
                '<div class="control-group">' +
                '<div class="editable-input"></div> <div class="editable-buttons"></div>' +
                '<div class="editable-error-block"></div>' +
                '</div> ' +
                '</form>';
            $.fn.editableform.buttons =
                '<button type="submit" class="btn btn-info btn-icon editable-submit"><i class="icon-check"></i></button>' +
                '<button type="button" class="btn btn-default btn-icon editable-cancel"><i class="icon-x"></i></button>';

        });

        $(function () {
            // Editable
            // Change defaults
            $.fn.editable.defaults.highlight = false;
            $.fn.editable.defaults.mode = 'popup';
            $.fn.editableform.template = '<form class="editableform">' +
                '<div class="control-group">' +
                '<div class="editable-input"></div> <div class="editable-buttons"></div>' +
                '<div class="editable-error-block"></div>' +
                '</div> ' +
                '</form>';
            $.fn.editableform.buttons =
                '<button type="submit" class="btn btn-info btn-icon editable-submit"><i class="icon-check"></i></button>' +
                '<button type="button" class="btn btn-default btn-icon editable-cancel"><i class="icon-x"></i></button>';

        });

        $('.selectedit').editable({
            source: [{
                value: 0,
                text: 'No'
            }, {
                value: 2,
                text: 'NR'
            }],
            display: function (value, sourceData) {
                var colors = {
                    0: "gray",
                    1: "green",
                    2: "blue"
                }, elem = $.grep(sourceData, function (o) {
                    return o.value == value;
                });

                if (elem.length) {
                    $(this).text(elem[0].text).css("color", colors[value]);
                } else {
                    $(this).empty();
                }
            }
        });

        $('.gpsedit').editable({
            source: [{
                value: 0,
                text: 'No'
            }, {
                value: 1,
                text: 'Yes'
            }, {
                value: 2,
                text: 'NR'
            }],
            display: function (value, sourceData) {
                var colors = {
                    0: "gray",
                    1: "green",
                    2: "blue"
                }, elem = $.grep(sourceData, function (o) {
                    return o.value == value;
                });

                if (elem.length) {
                    $(this).text(elem[0].text).css("color", colors[value]);
                } else {
                    $(this).empty();
                }
            }
        });

        var allstatus = @json($chkrstatus);

        $('.mvredit').editable({
            placement: 'left',
            url: '{{ url("admin/vehicle_reservations/updatemvr") }}',
            value: {
                accidents_3: "0",
                accidents_5: "0",
                violations: "0"
            },
            validate: function (value) {
                if (value.accidents_3 == '') {
                    return 'How many accidents in the last 3 years!';
                }

                if (value.accidents_5 == '') {
                    return 'How many accidents in the last 5 years';
                }

                if (value.violations == '') {
                    return 'How many moving violations in the last 4 years?';
                }
            },
            display: function (value, sourceData) {
                if (sourceData) {
                    value = sourceData.status ? sourceData.result.status : value;
                }
                var html = allstatus.map((val) => {
                    return val.value == value ? val.text : '';
                });
                $(this).html(html);
            },
            success: function (response, newValue) {
                if (!response) {
                    return "Unknown error!";
                }
            }
        });
        $('.cluereport').editable({
            placement: 'left',
            sourceOptions: 'cluereport',
            url: '{{ url("/admin/vehicle_reservations/updatemvr") }}',
            value: {
                accidents_3: "0",
                accidents_5: "0",
                violations: "0",
                notes: ""
            },
            validate: function (value) {
                console.log(value);

                if (value.accidents_3 == '') {
                    return 'How many accidents in the last 3 years!';
                }

                if (value.accidents_5 == '') {
                    return 'How many accidents in the last 5 years';
                }

                if (value.violations == '') {
                    return 'How many moving violations in the last 4 years?';
                }
            },
            display: function (value, sourceData) {
                if (sourceData) {
                    value = sourceData.status ? sourceData.result.status : value;
                }
                var html = value ? "Clear" : "Fail";
                $(this).html(html);
            },
            success: function (response, newValue) {
                if (!response) {
                    return "Unknown error!";
                }
            }
        });
    </script>

@endpush