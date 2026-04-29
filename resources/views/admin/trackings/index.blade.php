@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Tracking Data')

@php
    $trackings ??= [];
    $limit ??= 50;
    $basePath ??= url('admin/trackings');
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Tracking</span> Data
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ $basePath }}/view" class="btn btn-success">
                    {{ 'Vehicle Views' }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div id="listing">
                @include('admin.trackings.partials.index_listing', [
                    'trackings' => $trackings,
                    'limit' => $limit,
                    'basePath' => $basePath,
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

@push('styles')
    <style type="text/css">
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
