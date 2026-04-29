@extends('admin.layouts.app')

@section('title', 'Reservation pickup review')

@section('content')
@php $cr = $CsOrderReview['CsOrderReview']; @endphp

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Beginning</span> Condition Report
                <div class="heading-elements">
                    <div class="heading-btn-group">
                        <button type="submit" form="frmadmin" class="btn btn-primary">Save <i class="icon-database-insert position-right"></i></button>
                        <a href="/admin/vehicle_reservations/index" class="btn btn-default">Return</a>
                    </div>
                </div>
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="/admin/vehicle_reservations/index">Vehicle Reservations</a></li>
            <li class="active">Reservation Pickup Review</li>
        </ul>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Review Details</h5>
        </div>

        <div class="panel-body">
            <form method="post" action="{{ $basePath }}/reservationreview/{{ base64_encode((string)$orderid) }}" id="frmadmin" class="form-horizontal">
                @csrf
                <input type="hidden" name="CsOrderReview[id]" value="{{ $cr['id'] ?? '' }}">

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Condition report:</label>
                    <div class="col-lg-9">
                        <textarea name="CsOrderReview[details]" rows="5" class="form-control">{{ $cr['details'] ?? '' }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Odometer:</label>
                    <div class="col-lg-9">
                        <input type="text" name="CsOrderReview[mileage]" value="{{ $cr['mileage'] ?? 0 }}" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                        <button type="submit" class="btn btn-primary">Save <i class="icon-database-insert position-right"></i></button>
                        <a href="/admin/vehicle_reservations/index" class="btn btn-default">Return</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Pickup data</h5>
        </div>
        <div class="panel-body">
            @if(!empty($pickup_data))
                <pre style="background:#f5f5f5; padding:10px;">{{ json_encode($pickup_data, JSON_PRETTY_PRINT) }}</pre>
            @else
                <p>No pickup snapshot.</p>
            @endif
        </div>
    </div>
</div>
@endsection
