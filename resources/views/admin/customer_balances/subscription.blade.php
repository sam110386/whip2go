@extends('admin.layouts.app')
@php
    $title ??= 'Credits and Debits';
    $userid ??= '';
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
                <a href="{{ url('admin/customer_balances/addsubscription/' . base64_encode($userid)) }}"
                    class="btn btn-success">
                    Add New
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div id="listing">
                @include('admin.customer_balances.elements.subscription')
            </div>
        </div>
    </div>

@endsection