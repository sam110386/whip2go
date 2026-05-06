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

    <div class="row">
        <div class="col-lg-7">
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
        </div>

        <div class="col-lg-5">
            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">Pickup Driver's License Details</h5>
                </div>
                <div class="panel-body">
                    @if(empty($pickup_data) || (empty($pickup_data['LicenseDetail']) && empty($pickup_data['LICENSEDOC'])))
                        <div class="alert alert-warning alert-styled-left">
                            <span class="text-semibold">Licence Data: No data available</span>
                        </div>
                    @endif

                    @if(!empty($pickup_data) && !empty($pickup_data['LicenseDetail']))
                        <div class="col-lg-12">
                            <span class="text-semibold">Licence Data:</span>
                            <pre class="content-group language-markup" style="padding: 10px; background: #f8f8f8; border: 1px solid #ddd;"><code class="language-markup">@php print_r($pickup_data['LicenseDetail']); @endphp</code></pre>
                        </div>
                    @endif

                    @if(!empty($pickup_data) && !empty($pickup_data['LICENSEDOC']))
                        <div class="col-lg-12">
                            <span class="text-semibold">License Scan:</span><br/>
                            @foreach($pickup_data['LICENSEDOC'] as $doc)
                                <a href="{{ legacy_asset('files/reservation/' . $doc) }}" target="_blank">
                                    <img height="150px" width="150px" src="{{ legacy_asset('files/reservation/' . $doc) }}" class="img-thumbnail" style="margin-right:5px; margin-bottom:5px;" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
