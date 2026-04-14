@extends('layouts.admin')

@section('title', 'Manage Credits & Debits')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Customer Balances</span> - Manage Credits & Debits
            </h4>
        </div>

        <div class="heading-elements">
            <div class="heading-btn-group">
                <a href="{{ url('admin/customer_balances/add') }}" class="btn btn-link btn-float has-text text-size-small">
                    <i class="icon-plus22 text-primary"></i><span>Create New</span>
                </a>
            </div>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li class="active">Customer Balances</li>
        </ul>
    </div>
</div>

<div class="content">
    @include('layouts.flash-messages')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Filters</h5>
        </div>

        <div class="panel-body">
            <form method="GET" action="{{ url('admin/customer_balances/index') }}" class="form-vertical" id="search-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Keyword (first name):</label>
                            <input type="text" name="Search[keyword]" class="form-control" placeholder="Search..." value="{{ $keyword }}" maxlength="20">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Driver / Dealer:</label>
                            <select name="Search[type]" class="form-control">
                                <option value="">Global Search...</option>
                                <option value="1" @selected($type === '1')>Driver</option>
                                <option value="2" @selected($type === '2')>Dealer</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="Search[status]" class="form-control">
                                <option value="">Global Search...</option>
                                <option value="1" @selected($status === '1')>Active</option>
                                <option value="0" @selected($status === '0')>Inactive</option>
                                <option value="2" @selected($status === '2')>Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Rows / Page:</label>
                            <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                                @foreach ([25, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Search <i class="icon-search4 position-right"></i></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="listing">
            @include('admin.customer_balances._listing', [
                'records' => $records,
                'balanceTypes' => $balanceTypes,
                'formatDt' => $formatDt,
                'subscriptionMode' => false,
                'subscriptionUserId' => null,
            ])
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Basic search interactivity
    $('#search-form button[type="submit"]').on('click', function(e) {
        // You could add AJAX loading indicator here if needed
    });
});
</script>
@endsection
