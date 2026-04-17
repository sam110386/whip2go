@extends('layouts.admin_booking')

@section('title', 'Rental Orders')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Rental</span> Orders</h4>
            </div>
            <div class="heading-elements"></div>
        </div>
    </div>

    <div class="row">
        @if(session('flash'))
            <div class="alert alert-info">{{ session('flash') }}</div>
        @endif
    </div>

    <div class="panel">
        <div class="panel-body">
            <div style="width:100%; overflow: visible;" id="update_log">
                @include('admin.bookings.booking_table', ['trips' => $trips])
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection
