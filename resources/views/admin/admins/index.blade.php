@extends('admin.layouts.app')

@section('title', 'Manage Admin Users')

@php
    $keyword ??= '';
    $searchin ??= '';
    $fieldname ??= '';
    $showtype ??= '';
    $options ??= [];
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span>
                    Admin Users
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/admins/add') }}" class="btn btn-success">
                    Add New User
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/admins/index') }}">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            Keyword :
                            <input type="text" name="keyword" class="form-control" value="{{ $keyword }}" maxlength="50" size="30">
                        </div>

                        <div class="col-md-3">
                            Search In :
                            <select name="searchin" class="form-control">
                                <option value="">Select..</option>
                                @foreach($options as $k => $label)
                                    <option value="{{ $k }}" @selected($searchin === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            Status :
                            <select name="showtype" class="form-control">
                                <option value="">Select..</option>
                                <option value="Active" @selected($showtype === 'Active')>Active</option>
                                <option value="Deactive" @selected($showtype === 'Deactive')>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary" alt="Next">
                                APPLY
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning" alt="Clear Filter">
                                Clear Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="panel">
        <div class="panel-body">
            <div id="listing">
                @include('admin.admins._index_table', [
                    'users' => $users ?? [],
                    'limit' => $limit,
                ])
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection



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
