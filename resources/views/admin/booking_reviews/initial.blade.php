@extends('admin.layouts.app')

@section('title', 'Initial booking review')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Initial</span> Booking Review
                <div class="heading-elements">
                    <div class="heading-btn-group">
                        <button type="submit" form="frmadmin" class="btn btn-primary">Save <i class="icon-database-insert position-right"></i></button>
                        <a href="{{ $basePath }}/nonreview" class="btn btn-default">Return</a>
                    </div>
                </div>
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ $basePath }}/nonreview">Booking Reviews</a></li>
            <li class="active">Initial Review</li>
        </ul>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <p>Order #{{ $orderid }} &middot; Vehicle {{ $CsOrder['CsOrder']['vehicle_id'] ?? '' }}</p>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Review Details</h5>
        </div>

        <div class="panel-body">
            <form method="post" action="{{ $basePath }}/initial/{{ base64_encode((string)$orderid) }}" id="frmadmin" class="form-horizontal">
                @csrf
                <input type="hidden" name="CsOrderReview[id]" value="{{ $CsOrderReview['CsOrderReview']['id'] ?? '' }}">

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Condition report:</label>
                    <div class="col-lg-9">
                        <textarea name="CsOrderReview[details]" rows="5" class="form-control">{{ $CsOrderReview['CsOrderReview']['details'] ?? '' }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Odometer:</label>
                    <div class="col-lg-9">
                        <input type="text" name="CsOrderReview[mileage]" value="{{ $CsOrderReview['CsOrderReview']['mileage'] ?? 0 }}" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                        <button type="submit" class="btn btn-primary">Save <i class="icon-database-insert position-right"></i></button>
                        <a href="{{ $basePath }}/nonreview" class="btn btn-default">Return</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Pickup checklist (reference)</h5>
        </div>
        <div class="panel-body">
            @if(!empty($pickup_data))
                <pre style="background:#f5f5f5; padding:10px;">{{ json_encode($pickup_data, JSON_PRETTY_PRINT) }}</pre>
            @else
                <p>No pickup snapshot on deposit rule.</p>
            @endif
        </div>
    </div>
</div>
@endsection
