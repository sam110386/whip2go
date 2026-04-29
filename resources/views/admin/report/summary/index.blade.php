@extends('admin.layouts.app')

@section('title', 'Revenue - Report')

@php
    $datefrom ??= '';
    $dateto ??= '';
    $process ??= 0;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Revenue</span> - Report
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="POST" action="{{ url('admin/report/summary/generatereport') }}" class="form-horizontal">
                @csrf
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            Date from :
                            <input type="text" name="Search[datefrom]" id="SearchDatefrom" class="date form-control" value="{{ $datefrom }}" placeholder="Date from">
                        </div>
                        <div class="col-md-3">
                            Date to :
                            <input type="text" name="Search[dateto]" id="SearchDateto" class="date form-control" value="{{ $dateto }}" placeholder="Date to">
                        </div>
                        <div class="col-md-2">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="pull" value="search" class="btn btn-primary" alt="Generate Report">Generate Report</button>
                        </div>
                        <div class="col-md-2">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="export" value="export" class="btn btn-warning" alt="Export Report">Export Report</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.report.elements.admin_summary')
            </div>

        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">

            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style type="text/css">
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            if (typeof $.fn.datetimepicker !== 'undefined') {
                $('#SearchDatefrom').datetimepicker({ format: 'MM/YYYY' });
                $('#SearchDateto').datetimepicker({
                    useCurrent: false,
                    format: 'MM/YYYY'
                });
            }

            $(document).on('click', '.page-link, .sort-link', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (url && url !== '#' && url !== 'javascript:;') {
                    loadListing(url);
                }
            });

            $(document).on('change', '.ajax-limit', function (e) {
                e.preventDefault();
                var form = $(this).closest('form');
                var url = window.location.pathname + '?' + form.serialize();
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
    @if (!empty($process) && (int) $process === 1)
        <script>
            $(function () {
                jQuery.blockUI({
                    message: '<h1><img src="{{ legacy_asset('img/select2-spinner.gif') }}" /> Hold On, we are generating report...</h1>',
                    css: { 'z-index': '9999' }
                });

                $.post("{{ url('admin/report/summary/processReport') }}", {
                    _token: "{{ csrf_token() }}"
                }, function (data) {

                }).done(function () {
                    jQuery.unblockUI();
                    window.location = "{{ url('admin/report/summary') }}";
                });
            });
        </script>
    @endif
@endpush
