@extends('admin.layouts.app')

@php
    $title ??= 'Promotion Rules';
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> - Promo Rules
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/promo_rules/add') }}" class="btn btn-danger btn-lg">
                    {{ 'Add New' }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
            @include('admin.promo_rules.elements.index')
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/admin_booking.js') }}"></script>
@endpush