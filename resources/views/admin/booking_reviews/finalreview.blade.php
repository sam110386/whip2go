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
        <div class="panel-body">
            <form method="post" action="{{ $basePath }}/finalreview/{{ base64_encode((string)$orderid) }}" id="frmadmin" class="form-horizontal">
                @csrf
                <input type="hidden" name="CsOrderReview[id]" value="{{ $cr['id'] ?? '' }}">
                <input type="hidden" name="CsOrderReview[cs_order_id]" value="{{ $orderid }}">

                @if(($co['deposit_type'] ?? '') === 'C')
                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Total Deposits:</label>
                        <div class="col-lg-9">
                            <p class="form-control-static">{{ number_format((float)($co['deposit'] ?? 0), 2) }} {{ $co['currency'] ?? '' }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">Total Refund:</label>
                        <div class="col-lg-4">
                            <input type="text" name="CsOrderReview[refund]" value="{{ $co['deposit'] ?? 0 }}" class="form-control" max="{{ $co['deposit'] ?? 0 }}">
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
                    <label class="col-lg-3 control-label text-semibold">Ending Odometer:</label>
                    <div class="col-lg-6">
                        <div class="input-group">
                            <input type="text" name="CsOrderReview[mileage]" id="CsOrderReviewMileage" value="{{ $cr['mileage'] ?? 0 }}" class="form-control">
                            <span class="input-group-btn">
                                <button type="button" id="btnOdo" class="btn btn-warning">Pull GPS Provider Reading <i class="icon-sync position-right"></i></button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Vehicle Cleaned:</label>
                    <div class="col-lg-4">
                        <select name="CsOrderReview[is_cleaned]" class="form-control">
                            <option value="0" @selected((int)($cr['is_cleaned'] ?? 0) === 0)>No</option>
                            <option value="1" @selected((int)($cr['is_cleaned'] ?? 0) === 1)>Yes</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Vehicle Service:</label>
                    <div class="col-lg-2">
                        <label class="radio-inline"><input type="radio" name="CsOrderReview[vehicle_service]" value="done" @checked((int)($cr['vehicle_service'] ?? 0) === 1)> Done</label>
                        <label class="radio-inline"><input type="radio" name="CsOrderReview[vehicle_service]" value="needed" @checked((int)($cr['vehicle_service'] ?? 0) !== 1)> Needed</label>
                    </div>
                    <div class="col-lg-3">
                        <div class="input-group">
                            <input type="text" name="CsOrderReview[service_date]" id="CsOrderReviewServiceDate" value="{{ $cr['service_date'] ?? '' }}" class="form-control datepicker">
                            <span class="input-group-addon"><i class="icon-calendar22"></i></span>
                        </div>
                    </div>
                </div>

                @foreach($extras as $key => $label)
                    <div class="form-group">
                        <label class="col-lg-3 control-label text-semibold">{{ $label }}:</label>
                        <div class="col-lg-9">
                            <div class="checkbox">
                                <label><input type="checkbox" name="CsOrderReview[extra][{{ $key }}]" value="1" @checked(!empty($cr['extra'][$key]))> {{ $label }}</label>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">New Vehicle Body Damage:</label>
                    <div class="col-lg-2">
                        <div class="checkbox">
                            <label><input type="checkbox" id="CsOrderReviewExtraNewVehicleBodyDamage" name="CsOrderReview[extra][new_vehicle_body_damage]" value="1" @checked(!empty($cr['extra']['new_vehicle_body_damage']))> New Vehicle Body Damage</label>
                        </div>
                    </div>
                    <div class="col-lg-4 {{ !empty($cr['extra']['new_vehicle_body_damage']) ? 'show' : 'hide' }}" id="damageDetailsWrapper">
                        <textarea name="CsOrderReview[extra][new_vehicle_body_damage_text]" id="CsOrderReviewExtraNewVehicleBodyDamageText" rows="2" class="form-control" placeholder="Please enter damage details">{{ $cr['extra']['new_vehicle_body_damage_text'] ?? '' }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                        <button type="submit" name="submit" value="save" class="btn btn-primary">Save Only</button>
                        @if(($co['deposit_type'] ?? '') === 'C')
                            <button type="button" onclick="processfinalreview()" class="btn btn-danger">Process</button>
                        @else
                            <button type="submit" name="submit" value="update" class="btn btn-primary">Update</button>
                        @endif
                        <a href="{{ $basePath }}/nonreview" class="btn btn-default">Cancel</a>
                    </div>
                </div>
            </form>

            <div class="form-group">
                <label class="text-semibold">Additional Review Images</label>
                <input type="file" class="fileinputajax" multiple="multiple" name="reviewimage" data-show-preview="true" data-show-upload="true">
                <span class="help-block">You can select multiple images.</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/plugins/uploaders/fileinput.min.js') }}"></script>
<script>
    $(function() {
        $(".datepicker").datepicker();

        $("#CsOrderReviewExtraNewVehicleBodyDamage").change(function(){
            if($(this).is(":checked")){
                $("#damageDetailsWrapper").removeClass('hide').addClass('show');
            }else{
                $("#damageDetailsWrapper").removeClass('show').addClass('hide');
                $("#CsOrderReviewExtraNewVehicleBodyDamageText").val('');
            }
        });

        document.getElementById('btnOdo')?.addEventListener('click', function () {
            jQuery.blockUI({ message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> loading...</h1>', css: { 'z-index': '9999' } });
            var fd = new FormData();
            fd.append('vehicle', '{{ base64_encode((string)($co['vehicle_id'] ?? 0)) }}');
            fetch('{{ $basePath }}/pullVehicleOdometer', {method: 'POST', body: fd})
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status) document.getElementById('CsOrderReviewMileage').value = d.miles;
                    else alert(d.message || 'Could not read odometer');
                })
                .finally(function() { jQuery.unblockUI(); });
        });

        $(".fileinputajax").fileinput({
            showUpload: false,
            uploadUrl: SITE_URL + "admin/booking_reviews/saveImage",
            uploadAsync: true,
            maxFileCount: 15,
            deleteUrl: SITE_URL + "admin/booking_reviews/deleteImage",
            allowedFileExtensions: ['jpeg', 'jpg', 'png', 'pdf'],
            initialPreview: {!! json_encode(collect($CsOrderReviewImages ?? [])->map(fn($img) => legacy_asset('files/reviewimages/' . $img->image))->toArray()) !!},
            overwriteInitial: false,
            initialPreviewAsData: true,
            initialPreviewConfig: {!! json_encode(collect($CsOrderReviewImages ?? [])->map(function($img) {
                $ext = strtolower(pathinfo($img->image, PATHINFO_EXTENSION));
                $type = in_array($ext, ['jpeg', 'jpg', 'png']) ? 'image' : ($ext === 'pdf' ? 'pdf' : 'other');
                return [
                    'caption' => $img->image,
                    'filename' => $img->image,
                    'key' => $img->id,
                    'width' => '120px',
                    'downloadUrl' => legacy_asset('files/reviewimages/' . $img->image),
                    'type' => $type
                ];
            })->toArray()) !!},
            maxFileSize: 5120,
            uploadExtraData: {
                'id': '{{ $cr['id'] ?? '' }}'
            },
            fileActionSettings: {
                removeIcon: '<i class="icon-bin"></i>',
                removeClass: 'btn btn-link btn-xs btn-icon',
                uploadIcon: '<i class="icon-upload"></i>',
                uploadClass: 'btn btn-link btn-xs btn-icon',
                indicatorNew: '<i class="icon-file-plus text-slate"></i>',
                indicatorSuccess: '<i class="icon-checkmark3 file-icon-large text-success"></i>',
                indicatorError: '<i class="icon-cross2 text-danger"></i>',
                indicatorLoading: '<i class="icon-spinner2 spinner text-muted"></i>',
                showZoom: true,
            }
        }).on("filebatchselected", function(event, files) {
            $(".fileinputajax").fileinput("upload");
        }).on('fileuploaded', function(event, data, previewId, index) {
            $("#" + previewId + " button.kv-file-remove").attr('data-key', data.response.key);
        });
    });

    function processfinalreview() {
        jQuery.blockUI({ message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> loading...</h1>', css: { 'z-index': '9999' } });
        var fromdata = jQuery("#frmadmin").serialize();
        jQuery.ajax({
            url: SITE_URL + 'admin/booking_reviews/settlefinaldamage',
            data: fromdata,
            method: "POST",
            dataType: 'json',
            success: function(msg) {
                if (msg.status == 'success') {
                    alert(msg.message);
                    location.href = SITE_URL + 'admin/booking_reviews/nonreview';
                } else {
                    alert(msg.message);
                }
            }
        }).complete(function() {
            jQuery.unblockUI();
        });
    }
</script>
@endpush
