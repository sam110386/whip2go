@extends('admin.layouts.app')

@section('title', 'Manage Pick Ups')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Pick</span> Ups
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.reservation._admin_index')
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/forms/editable/editable.css') }}">
    <style type="text/css">
        .datepicker .prev, .datepicker .next { background: none; }
        .table > thead > tr > th,
        .table > tbody > tr > th,
        .table > tfoot > tr > th,
        .table > thead > tr > td,
        .table > tbody > tr > td,
        .table > tfoot > tr > td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/plugins/forms/editable/editable.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/media/fancybox.min.js') }}"></script>
    <script src="{{ legacy_asset('Reservation/js/admin_reservation.js') }}"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $(".fancybox").fancybox();
        });
        $(function () {
            $.fn.editable.defaults.highlight = false;
            $.fn.editable.defaults.mode = 'popup';
            $.fn.editableform.template = '<form class="editableform form-horizontal"><div class="control-group"><div class="editable-input"></div> <div class="editable-buttons"></div><div class="editable-error-block"></div></div></form>';
            $.fn.editableform.buttons = '<button type="submit" class="btn btn-info btn-icon editable-submit"><i class="icon-check"></i></button><button type="button" class="btn btn-default btn-icon editable-cancel"><i class="icon-x"></i></button>';
        });

        $(document).ready(function () {
            $(document).on('click', '.page-link, .sort-link', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (url && url !== '#' && url !== 'javascript:;') {
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
    </script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
