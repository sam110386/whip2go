@extends('admin.layouts.app')

@php
    $title ??= 'Staff Users';
    $keyword ??= '';
    $fieldname ??= '';
    $show ??= null;
    $options ??= [];
@endphp

@section('title', $title)

@section('content')

    <div class="panel">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Search</span> - Admin Staff
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/admin_staffs/add') }}" class="btn btn-success">
                    Add New
                </a>
            </div>
        </div>

        <div class="row">
            @includeif('partials.flash')
        </div>

        <section class="right_content">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/admin_staffs/index') }}">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            Keyword :
                            <input type="text" name="keyword" class="form-control" value="{{ $keyword }}" maxlength="50"
                                size="30">
                        </div>

                        <div class="col-md-3">
                            Search In :
                            <select name="searchin" class="form-control">
                                <option value="">Select..</option>
                                @foreach($options as $k => $label)
                                    <option value="{{ $k }}" @selected($fieldname === $k)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            Status :
                            <select name="show" class="form-control">
                                <option value="">Select..</option>
                                <option value="Active" @selected($show === 'Active')>Active</option>
                                <option value="Deactive" @selected($show === 'Deactive')>Inactive</option>
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
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning"
                                alt="Clear Filter">
                                Clear Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.admin_staffs.elements.index')
            </div>

        </section>
    </div>
@endsection

@push('scripts')

    <script src="{{ legacy_asset('js/selectAllCheckbox.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function () {

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
    </script>
@endpush