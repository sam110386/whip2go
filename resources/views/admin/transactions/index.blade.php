@extends('admin.layouts.app')

@section('title', 'Manage Transactions')

@php
    $keyword ??= '';
    $transaction_id ??= '';
    $fieldname ??= '';
    $status_type ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> - Transactions
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <p class="help-block">Canceled and completed orders (status 2&ndash;3). Filters match Cake <code>admin_index</code>; adjust payment actions are not ported yet.</p>

            <form id="frmSearchadmin" name="frmSearchadmin" method="POST" action="{{ url('admin/transactions/index') }}">
                @csrf
                <div class="row">
                    <div class="col-md-2">
                        Keyword :
                        <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                    </div>
                    <div class="col-md-2">
                        Transaction ID :
                        <input type="text" name="Search[transaction_id]" class="form-control" maxlength="80" value="{{ $transaction_id }}" placeholder="Stripe / txn id">
                    </div>
                    <div class="col-md-2">
                        Search in :
                        <select name="Search[searchin]" class="form-control">
                            <option value="">Select</option>
                            <option value="2" @selected($fieldname === '2')>Vehicle#</option>
                            <option value="3" @selected($fieldname === '3')>Order#</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        Status :
                        <select name="Search[status_type]" class="form-control">
                            <option value="">Select type</option>
                            <option value="complete" @selected($status_type === 'complete')>Complete</option>
                            <option value="cancel" @selected($status_type === 'cancel')>Cancel</option>
                            <option value="incomplete" @selected($status_type === 'incomplete')>Incomplete</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        Date from :
                        <input type="text" name="Search[date_from]" class="form-control" value="{{ $date_from }}" placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-2">
                        Date to :
                        <input type="text" name="Search[date_to]" class="form-control" value="{{ $date_to }}" placeholder="YYYY-MM-DD">
                    </div>
                </div>
                <div class="row">&nbsp;</div>
                <div class="row">
                    <div class="col-md-1">
                        Rows :
                        <input type="number" name="Record[limit]" class="form-control" value="{{ $limit }}" min="1" max="500">
                    </div>
                    <div class="col-md-1">
                        <label style="margin-bottom:0;">&nbsp;</label>
                        <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.transactions.listing', ['reportlists' => $reportlists])
            </div>
        </div>
    </div>
@endsection
