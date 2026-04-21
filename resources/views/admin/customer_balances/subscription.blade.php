@extends('admin.layouts.app')

@section('title', 'Credits and Debits')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Credits </span> and Debits</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('admin/customer_balances/addsubscription', $useridB64) }}" class="btn btn-success">Add New</a>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('admin/customer_balances/index') }}">Customer Balances</a></li>
            <li class="active">Subscriptions</li>
        </ul>
    </div>
</div>

<div class="content">
    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div id="listing">
                @include('admin.customer_balances._listing', [
                    'records'           => $records,
                    'balanceTypes'      => $balanceTypes,
                    'formatDt'          => $formatDt,
                    'subscriptionMode'  => true,
                    'subscriptionUserId'=> $userid,
                ])
            </div>
        </div>
    </div>
</div>
@endsection
