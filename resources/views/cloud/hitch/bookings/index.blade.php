@extends('layouts.admin')
@section('content')
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Rental</span> Active Orders</h4>
        </div>
        <div class="heading-elements"></div>
    </div>
</div>
<div class="row">
    @include('partials.flash')
</div>
<div class="panel">
    <div class="panel-body">
        <div style="width:100%; overflow: visible;" id="update_log">
            @include('cloud.hitch.bookings._booking_table')
        </div>
    </div>
</div>
<script src="/Hitch/js/hitch.js"></script>
@endsection
