@extends('admin.layouts.app')

@section('title', $title ?? 'Transaction Mismatch - Report')

@php
    $datefrom ??= '';
    $dateto ??= '';
@endphp

@section('content')

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">

            </div>
        </div>
    </div>

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Transaction Mismatch</span> - Report
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="POST"
                action="{{ url('admin/report/transaction_mismatches/index') }}" class="form-horizontal">
                @csrf
                <div class="row">
                    <div class="col-md-2">
                        <input type="text" name="Search[datefrom]" id="SearchDatefrom" class="date form-control"
                            value="{{ $datefrom }}" placeholder="Date from">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[dateto]" id="SearchDateto" class="date form-control"
                            value="{{ $dateto }}" placeholder="Date to">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" value="search" class="btn btn-primary" alt="Search">
                            Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="refresh" value="refresh" class="btn btn-warning" alt="Refresh Report">
                            Refresh Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
            @include('admin.report.transaction_mismatches.elements._transaction_mismatch')
        </div>
    </div>

@endsection

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
                if (url && url !== '#' && url !== 'javascript:void(0)') {
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
                $('#postsPaging').css('opacity', '0.5');

                $.ajax({
                    url: url,
                    type: "GET",
                    success: function (data) {
                        $('#postsPaging').html(data);
                        $('#postsPaging').css('opacity', '1');
                        window.history.pushState(null, null, historyUrl);
                    },
                    error: function (xhr) {
                        $('#postsPaging').css('opacity', '1');
                        console.error('AJAX Load Error:', xhr);
                    }
                });
            }

            window.onpopstate = function () {
                loadListing(window.location.href);
            };
        });
    </script>
@endpush