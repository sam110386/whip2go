@extends('layouts.main')

@section('title', 'Rental Active Orders')

@push('scripts')
<script src="{{ legacy_asset('Hitch/js/hitch.js') }}"></script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Rental </span>- Active Orders</h4>
        </div>
        <div class="heading-elements"></div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        <div style="width:100%; overflow: visible;" id="update_log">
            @include('cloud.hitch.bookings._booking_table')
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
@endsection
