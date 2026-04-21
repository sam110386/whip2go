@extends('admin.layouts.app')

@section('title', 'Credits and Debits')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Credits </span> and Debits</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('admin/customer_balances/add') }}" class="btn btn-success">Create New</a>
        </div>
    </div>
</div>

<div class="row">
    @include('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ url('admin/customer_balances/index') }}" id="frmSearchadmin" name="frmSearchadmin">
            @csrf
            <div class="row">
                <div class="col-md-10">
                    <div class="col-md-3">
                        Keyword :
                        <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        Driver/Dealer :
                        <select name="Search[type]" class="form-control">
                            <option value="">Select..</option>
                            <option value="1" @selected($type === '1')>Driver</option>
                            <option value="2" @selected($type === '2')>Dealer</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        Status :
                        <select name="Search[status]" class="form-control">
                            <option value="">Select..</option>
                            <option value="1" @selected($status === '1')>Active</option>
                            <option value="0" @selected($status === '0')>Inactive</option>
                            <option value="2" @selected($status === '2')>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label style="margin-bottom: 0px;">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">APPLY</button>
                    </div>
                    <div class="col-md-1">
                        <label style="margin-bottom: 0px;">&nbsp;</label>
                        <button type="submit" name="ClearFilter" value="1" class="btn btn-warning">Clear Filter</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        @include('admin.customer_balances._listing', [
            'records'           => $records,
            'balanceTypes'      => $balanceTypes,
            'formatDt'          => $formatDt,
            'subscriptionMode'  => false,
            'subscriptionUserId'=> null,
        ])
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        // AJAX Pagination and Sorting
        $(document).on('click', '.page-link, .sort-link', function (e) {
            e.preventDefault();
            var url = $(this).attr('href');
            if (url && url !== '#' && url !== 'javascript:;') {
                loadListing(url);
            }
        });

        // AJAX Search and Clear Filter
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
                // POST search then load index via AJAX
                $.ajax({
                    url: form.attr('action'),
                    type: "POST",
                    data: formData,
                    success: function () {
                        loadListing(form.attr('action'));
                    }
                });
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
