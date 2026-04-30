@extends('admin.layouts.app')

@section('title', 'Manage Savvy Dealers')

@php
    $limit ??= 25;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Savvy</span> - Dealers
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/savvy_dealers/add') }}" class="btn btn-success left-margin">New Dealer</a>
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
                <div class="table-responsive">
                    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                @include('partials.dispacher.sortable_header', ['columns' => [
                                    ['field' => 'id', 'title' => '#', 'sortable'=>false, 'style' => 'width: 5%;'],
                                    ['field' => 'first_name', 'title' => 'Name', 'sortable'=>false,],
                                    ['field' => 'status', 'title' => 'Status', 'sortable'=>false,],
                                    ['field' => 'actions', 'title' => 'Actions', 'sortable' => false, 'style' => 'width: 15%;']
                                ]])
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dealers as $dealer)
                                <tr>
                                    <td valign="top">{{ $dealer->id }}</td>
                                    <td valign="top">{{ $dealer->first_name }} {{ $dealer->last_name }}</td>
                                    <td valign="top">
                                        @if ($dealer->status == '0')
                                            <a href="{{ url('admin/savvy_dealers/status/' . base64_encode($dealer->id) . '/1') }}">Inactive</a>
                                        @else
                                            <a href="{{ url('admin/savvy_dealers/status/' . base64_encode($dealer->id) . '/0') }}">Active</a>
                                        @endif
                                    </td>
                                    <td class="action">
                                        <a href="{{ url('admin/savvy_dealers/add/' . base64_encode($dealer->id)) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                        <a href="{{ url('admin/savvy_dealers/delete/' . base64_encode($dealer->id)) }}" onclick="return confirm('Are you sure?')"><i class="glyphicon glyphicon-trash"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($dealers->isEmpty())
                                <tr><td colspan="4" align="center">No record found</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @include('partials.dispacher.paging_box', ['paginator' => $dealers, 'limit' => $limit])
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
    <style type="text/css">
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
    <script type="text/javascript">
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
