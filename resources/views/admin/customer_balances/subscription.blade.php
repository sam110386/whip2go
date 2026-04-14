@extends('layouts.admin')

@section('title', 'User Subscriptions')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">User</span> - Subscriptions & Charges
            </h4>
        </div>

        <div class="heading-elements">
            <div class="heading-btn-group">
                <a href="{{ url('admin/customer_balances/addsubscription', $useridB64) }}" class="btn btn-link btn-float has-text text-size-small">
                    <i class="icon-plus22 text-primary"></i><span>Add New Charge</span>
                </a>
                <a href="{{ url('admin/customer_balances/index') }}" class="btn btn-link btn-float has-text text-size-small">
                    <i class="icon-list text-primary"></i><span>All Balances</span>
                </a>
            </div>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('admin/users/index') }}">Users</a></li>
            <li><a href="{{ url('admin/customer_balances/index') }}">Customer Balances</a></li>
            <li class="active">Subscriptions</li>
        </ul>
    </div>
</div>

<div class="content">
    @include('layouts.flash-messages')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Active Charges / Subscriptions</h5>
            <div class="heading-elements">
                <form method="GET" action="{{ url('admin/customer_balances/subscription', $useridB64) }}" class="form-inline">
                    <div class="form-group mr-10">
                        <label class="mr-10">Rows:</label>
                        <select name="Record[limit]" class="form-control input-xs" onchange="this.form.submit()">
                            @foreach ([25, 50, 100, 200] as $opt)
                                <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div id="listing">
            @include('admin.customer_balances._listing', [
                'records' => $records,
                'balanceTypes' => $balanceTypes,
                'formatDt' => $formatDt,
                'subscriptionMode' => true,
                'subscriptionUserId' => $userid,
            ])
        </div>
    </div>
</div>
@endsection
