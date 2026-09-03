@php
    $title ??= 'Final booking review';
    $orderid ??= null;
    $csOrder ??= collect();
    $csOrderReview ??= collect();
    $extras ??= [];

    $csOrderReviewImages = data_get($csOrderReview, 'csOrderReviewImages', []);
    $initialPreview = [];
    $initialPreviewConfig = [];

    foreach ($csOrderReviewImages as $img) {
        $initialPreview[] = legacy_asset('files/reviewimages/' . $img->image);
        $ext = strtolower(pathinfo($img->image, PATHINFO_EXTENSION));
        $type = 'image';

        if ($ext == 'pdf') {
            $type = "pdf";
        }

        if (in_array($ext, ['doc', 'docx'])) {
            $type = "gdocs";
        }

        $initialPreviewConfig[] = [
            'caption' => $img->image,
            'filename' => $img->image,
            'key' => $img->id,
            "width" => "120px",
            "downloadUrl" => legacy_asset('files/reviewimages/' . $img->image),
            "type" => $type
        ];
    }

@endphp

@extends('admin.layouts.app')

@section('title', $title)

@push('styles')
    <style type="text/css">
        .krajee-default.file-preview-frame .kv-file-content {
            width: 210px;
            height: 160px;
        }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    Final Booking <span class="text-semibold">Review</span>
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="post" action="{{ url('/admin/booking_reviews/finalreview/' . base64_encode($orderid)) }}"
                id="frmadmin" class="form-horizontal">
                @csrf

                @if((data_get($csOrder, 'deposit_type', '')) === 'C')

                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            Total Deposits:
                        </label>
                        <div class="col-lg-4">
                            <p class="form-control-static">
                                {{ \App\Helpers\Legacy\Number::currency(data_get($csOrder, 'deposit', 0), data_get($csOrder, 'currency', '$')) }}
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            Total Refund:
                        </label>
                        <div class="col-lg-4">
                            <input type="text" name="CsOrderReview[refund]" value="{{ data_get($csOrder, 'deposit', 0) }}"
                                class="form-control" max="{{ data_get($csOrder, 'deposit', 0)}}">
                        </div>
                    </div>

                @endif

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Vehicle condition report:
                    </label>
                    <div class="col-lg-4">
                        <textarea name="CsOrderReview[details]" rows="6"
                            class="form-control">{{ data_get($csOrderReview, 'details', '') }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Ending Odometer:
                    </label>
                    <div class="col-lg-4">
                        <input type="text" name="CsOrderReview[mileage]" id="CsOrderReviewMileage"
                            value="{{ data_get($csOrderReview, 'mileage', 0) }}" class="form-control">
                    </div>
                    <div class="col-lg-3">
                        <button type="button" id="btnOdo" class="btn-warning pull-right btn"
                            onclick="pullGpsProviderOdometer('{{ base64_encode(data_get($csOrder, 'vehicle_id', '')) }}')">
                            Pull GPS Provider Reading <i class="icon-sync position-right"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Vehicle Cleaned:
                    </label>
                    <div class="col-lg-4">
                        <select name="CsOrderReview[is_cleaned]" class="form-control">
                            <option value="0" @selected((int) (data_get($csOrderReview, 'is_cleaned', 0)) === 0)>
                                No
                            </option>
                            <option value="1" @selected((int) (data_get($csOrderReview, 'is_cleaned', 0)) === 1)>
                                Yes
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Vehicle Service:
                    </label>
                    <div class="col-lg-1 control-label">
                        <input type="radio" name="CsOrderReview[vehicle_service]" value="done"
                            @checked((data_get($csOrderReview, 'vehicle_service', 0)) == 1)>
                        Done
                    </div>
                    <div class="col-lg-1 control-label">
                        <input type="radio" name="CsOrderReview[vehicle_service]" value="needed"
                            @checked((data_get($csOrderReview, 'vehicle_service', 0)) != 1)>
                        Needed
                    </div>
                    <div class="col-lg-2 input-group">
                        <input type="text" name="CsOrderReview[service_date]" id="CsOrderReviewServiceDate"
                            value="{{ data_get($csOrderReview, 'service_date', '') }}" class="form-control daterange-left">
                        <span class="input-group-addon"><i class="icon-calendar22"></i></span>
                    </div>
                </div>

                @foreach($extras as $key => $label)
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            {{ $label }}:
                        </label>
                        <div class="col-lg-2 control-label">
                            <input type="checkbox" name="CsOrderReview[extra][{{ $key }}]" value="1"
                                class="form-control checkbox" @checked(!empty(data_get($csOrderReview, 'extra.' . $key, '')))>
                        </div>
                    </div>
                @endforeach

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        New Vehicle Body Damage:
                    </label>
                    <div class="col-lg-2">
                        <input type="checkbox" id="CsOrderReviewExtraNewVehicleBodyDamage"
                            name="CsOrderReview[extra][new_vehicle_body_damage]" value="1" class="form-control checkbox"
                            @checked(!empty(data_get($csOrderReview, 'extra.new_vehicle_body_damage', '')))>
                    </div>
                    <div class="col-lg-3 {{ !empty(data_get($csOrderReview, 'extra.new_vehicle_body_damage', '')) ? 'show' : 'hide' }}"
                        id="damageDetailsWrapper">
                        <textarea name="CsOrderReview[extra][new_vehicle_body_damage_text]"
                            id="CsOrderReviewExtraNewVehicleBodyDamageText" rows="2" class="form-control"
                            placeholder="Please enter damage details">{{ data_get($csOrderReview, 'extra.new_vehicle_body_damage_text', '') }}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-1">
                        <button type="submit" name="submit" value="save" class="btn left-margin btn-primary">
                            Save Only
                        </button>
                    </div>
                    <div class="col-lg-1">
                        @if((data_get($csOrder, 'deposit_type', '')) === 'C')
                            <button type="button" onclick="processfinalreview()" class="btn btn-danger">
                                Process
                            </button>
                        @else
                            <button type="submit" name="submit" value="update" class="btn btn-primary">
                                Update
                            </button>
                        @endif
                    </div>
                    <div class="col-lg-1">
                        <button type="button" class="btn left-margin btn-cancel"
                            onclick="goBack('/admin/booking_reviews/nonreview')">
                            Cancel
                        </button>
                    </div>
                </div>

                <input type="hidden" name="CsOrderReview[id]" value="{{ data_get($csOrderReview, 'id', '') }}">
                <input type="hidden" name="CsOrderReview[cs_order_id]" value="{{ $orderid }}">
            </form>

            <div class="form-group">
                <label class="text-semibold">Additional Review Images</label>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">
                    Review Images
                </label>
                <div class="col-lg-8">
                    <input type="file" class="fileinputajax" multiple="multiple" name="reviewimage" data-show-preview="true"
                        data-show-upload="true">
                    <span class="help-block">You can select multiple images.</span>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/assets/js/plugins/uploaders/fileinput.min.js') }}"></script>

    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
            jQuery("#CsOrderReviewServiceDate").datepicker();

            jQuery("#CsOrderReviewExtraNewVehicleBodyDamage").change(function () {
                if (jQuery(this).is(":checked")) {
                    jQuery("#CsOrderReviewExtraNewVehicleBodyDamageText").parent('div').removeClass('hide').addClass('show');
                } else {
                    jQuery("#CsOrderReviewExtraNewVehicleBodyDamageText").parent('div').removeClass('show').addClass('hide');
                    jQuery("#CsOrderReviewExtraNewVehicleBodyDamageText").val('');
                }
            });
        });

        function processfinalreview() {
            jQuery.blockUI({
                message: '<h1><img src="' + '{{ legacy_asset('img/select2-spinner.gif') }}' + '" /> loading...</h1>',
                css: {
                    'z-index': '9999'
                }
            });
            var fromdata = jQuery("#frmadmin").serialize();
            jQuery.ajax({
                url: '{{ url('admin/booking_reviews/settlefinaldamage') }}',
                data: fromdata,
                method: "POST",
                dataType: 'json',
                success: function (msg) {
                    if (msg.status == 'success') {
                        alert(msg.message);
                        location.href = '{{ url('admin/booking_reviews/nonreview') }}';
                    } else {
                        alert(msg.message);
                    }
                }
            }).complete(function () {
                jQuery.unblockUI();
            });

        }

        function pullGpsProviderOdometer(vehicle) {
            jQuery.blockUI({
                message: '<h1><img src="' + '{{ legacy_asset('img/select2-spinner.gif') }}' + '" /> loading...</h1>',
                css: {
                    'z-index': '9999'
                }
            });
            var fromdata = { vehicle: vehicle };
            jQuery.ajax({
                url: '{{ url('admin/booking_reviews/pullVehicleOdometer') }}',
                data: fromdata,
                method: "POST",
                dataType: 'json',
                success: function (msg) {
                    if (msg.status) {
                        $("#CsOrderReviewMileage").val(msg.miles);
                    } else {
                        alert(msg.message);
                    }
                }
            }).complete(function () {
                jQuery.unblockUI();
            });
        }

        $(function () {
            $(".fileinputajax").fileinput({
                showUpload: false,
                uploadUrl: '{{ url('admin/booking_reviews/saveImage') }}',
                uploadAsync: true,
                maxFileCount: 15,
                deleteUrl: '{{ url('admin/booking_reviews/deleteImage') }}',
                allowedFileExtensions: ['jpeg', 'jpg', 'png', 'pdf'],
                initialPreview: @json($initialPreview),
                overwriteInitial: false,
                initialPreviewAsData: true,
                initialPreviewConfig: @json($initialPreviewConfig),
                maxFileSize: 5120,
                uploadExtraData: {
                    'id': '{{ data_get($csOrderReview, 'id', '') }}'
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
            }).on("filebatchselected", function (event, files) {
                $(".fileinputajax").fileinput("upload");
            }).on('fileuploaded', function (event, data, previewId, index) {
                $("#" + previewId + " button.kv-file-remove").attr('data-key', data.response.key);
            });
        });

    </script>
@endpush