@extends('layouts.admin')

@section('title', 'Manage Users')

@php
    $keyword ??= '';
    $show ??= null;
    $type ??= null;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">
                        {{ 'Manage' }}
                    </span>
                    {{ 'Users' }}
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/users/add') }}" class="btn btn-success">
                    {{ 'Add New' }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('common.flash-messages')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/users/index') }}">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            {{ ' Keyword :' }}
                            <input type="text" name="keyword" class="form-control" value="{{ $keyword }}" maxlength="50"
                                size="30">
                        </div>

                        <div class="col-md-3">
                            {{ 'Status :' }}
                            <select name="show" class="form-control">
                                <option value="">
                                    {{ 'Select..' }}
                                </option>
                                <option value="Active" @selected($show === 'Active')>
                                    {{ 'Active' }}
                                </option>
                                <option value="Deactive" @selected($show === 'Deactive')>
                                    {{ 'Inactive' }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            {{ 'Type :' }}
                            <select name="type" class="form-control">
                                <option value="">
                                    {{ 'Select..' }}
                                </option>
                                <option value="1" @selected($type == '1')>
                                    {{ 'Verified' }}
                                </option>
                                <option value="2" @selected($type == '2')>
                                    {{ 'UnVerified' }}
                                </option>
                                <option value="3" @selected($type == '3')>
                                    {{ 'Renter' }}
                                </option>
                                <option value="4" @selected($type == '4')>
                                    {{ 'Driver' }}
                                </option>
                                <option value="5" @selected($type == '5')>
                                    {{ 'Dealer' }}
                                </option>
                                <option value="6" @selected($type == '6')>
                                    {{ 'Dealer Waiting Approval' }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary" alt="Next">
                                {{ 'APPLY' }}
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning"
                                alt="Clear Filter">
                                {{ 'Clear Filter' }}
                            </button>
                        </div>
                    </div>
                </div>

            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @includeif('admin.elements.users.admin_index')
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