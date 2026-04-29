@extends('admin.layouts.app')

@section('title', 'Final booking review')

@section('content')
@php $co = $CsOrder['CsOrder']; $cr = $CsOrderReview['CsOrderReview']; @endphp

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                Final Booking <span class="text-semibold">Review</span>
                <div class="heading-elements">
                    <div class="heading-btn-group">
                        <button type="submit" form="frmadmin" name="submit" value="save" class="btn btn-primary">Save only</button>
                        <button type="submit" form="frmadmin" name="submit" value="update" class="btn btn-primary">Complete review</button>
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
            <li class="active">Final Review</li>
        </ul>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Final Booking Review</h5>
        </div>

        <div class="panel-body">
            <form method="post" action="{{ $basePath }}/finalreview/{{ base64_encode((string)$orderid) }}" id="frmadmin" class="form-horizontal">
                @csrf
                <input type="hidden" name="CsOrderReview[id]" value="{{ $cr['id'] ?? '' }}">
                <input type="hidden" name="CsOrderReview[cs_order_id]" value="{{ $orderid }}">

                @if(($co['deposit_type'] ?? '') === 'C')
                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Total Deposits:</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ $co['deposit'] ?? '' }} {{ $co['currency'] ?? '' }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Refund amount:</label>
                        <div class="col-lg-9">
                            <input type="text" name="CsOrderReview[refund]" value="{{ $co['deposit'] ?? 0 }}" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-9 col-lg-offset-3">
                            <button type="button" class="btn btn-danger" onclick="alert('Deposit settlement is not wired in Laravel yet; use legacy or extend PaymentProcessor.');">Process (legacy)</button>
                        </div>
                    </div>
                @endif

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Vehicle condition report:</label>
                    <div class="col-lg-9">
                        <textarea name="CsOrderReview[details]" rows="5" class="form-control">{{ $cr['details'] ?? '' }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Ending odometer:</label>
                    <div class="col-lg-9">
                        <input type="text" name="CsOrderReview[mileage]" id="CsOrderReviewMileage" value="{{ $cr['mileage'] ?? 0 }}" class="form-control">
                        <button type="button" id="btnOdo" class="btn btn-warning" style="margin-top:6px;">Pull odometer (DB)</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Vehicle cleaned:</label>
                    <div class="col-lg-9">
                        <select name="CsOrderReview[is_cleaned]" class="form-control">
                            <option value="0" @selected((int)($cr['is_cleaned'] ?? 0) === 0)>No</option>
                            <option value="1" @selected((int)($cr['is_cleaned'] ?? 0) === 1)>Yes</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Vehicle service:</label>
                    <div class="col-lg-9">
                        <label class="radio-inline"><input type="radio" name="CsOrderReview[vehicle_service]" value="done" @checked((int)($cr['vehicle_service'] ?? 0) === 1)> Done</label>
                        <label class="radio-inline"><input type="radio" name="CsOrderReview[vehicle_service]" value="needed" @checked((int)($cr['vehicle_service'] ?? 0) !== 1)> Needed</label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Service date:</label>
                    <div class="col-lg-9">
                        <input type="text" name="CsOrderReview[service_date]" value="{{ $cr['service_date'] ?? '' }}" class="form-control">
                    </div>
                </div>

                @foreach($extras as $key => $label)
                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">{{ $label }}:</label>
                        <div class="col-lg-9">
                            <label class="checkbox-inline"><input type="checkbox" name="CsOrderReview[extra][{{ $key }}]" value="1" @checked(!empty($cr['extra'][$key]))> {{ $label }}</label>
                        </div>
                    </div>
                @endforeach

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">New vehicle body damage:</label>
                    <div class="col-lg-9">
                        <label class="checkbox-inline"><input type="checkbox" name="CsOrderReview[extra][new_vehicle_body_damage]" value="1" @checked(!empty($cr['extra']['new_vehicle_body_damage']))> New vehicle body damage</label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Damage details:</label>
                    <div class="col-lg-9">
                        <textarea name="CsOrderReview[extra][new_vehicle_body_damage_text]" rows="2" class="form-control" placeholder="Damage details">{{ $cr['extra']['new_vehicle_body_damage_text'] ?? '' }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                        <button type="submit" name="submit" value="save" class="btn btn-primary">Save only</button>
                        <button type="submit" name="submit" value="update" class="btn btn-primary">Complete review</button>
                        <a href="{{ $basePath }}/nonreview" class="btn btn-default">Return</a>
                    </div>
                </div>
            </form>

            <script>
                document.getElementById('btnOdo')?.addEventListener('click', function () {
                    var fd = new FormData();
                    fd.append('vehicle', '{{ base64_encode((string)($co['vehicle_id'] ?? 0)) }}');
                    fetch('{{ $basePath }}/pullVehicleOdometer', {method: 'POST', body: fd})
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            if (d.status) document.getElementById('CsOrderReviewMileage').value = d.miles;
                            else alert(d.message || 'Could not read odometer');
                        });
                });
            </script>
        </div>
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Review images</h5>
        </div>
        <div class="panel-body">
            <p>POST <code>{{ $basePath }}/saveImage</code> with multipart <code>reviewimage</code> and field <code>id={{ $cr['id'] ?? '' }}</code> (same pattern as legacy fileinput).</p>
            <ul>
                @foreach($CsOrderReviewImages ?? [] as $img)
                    <li><a href="/files/reviewimages/{{ $img->image }}" target="_blank">{{ $img->image }}</a> (id {{ $img->id }})</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
