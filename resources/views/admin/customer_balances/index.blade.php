@extends('admin.layouts.app')

@php
    $title ??= 'Customer Balance';
    $keyword ??= '';
    $type ??= null;
    $status ??= null;
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Credits </span> and Debits
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/customer_balances/add') }}" class="btn btn-success">
                    Create New
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="POST" action="{{ url('admin/customer_balances/index') }}" id="frmSearchadmin"
                name="frmSearchadmin">
                @csrf

                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            Keyword :
                            <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}"
                                maxlength="20">
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
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('admin.customer_balances.elements.index')
        </div>
    </div>
@endsection

@push('scripts')
@endpush