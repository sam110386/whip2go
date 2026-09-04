@php
    $title ??= 'Reservation pickup review';
    $csOrderReview ??= collect();
    $pickupData ??= [];

    $csOrderReviewImages = data_get($csOrderReview, 'csOrderReviewImages', []);
    $initialPreview = [];
    $initialPreviewConfig = [];

    foreach ($csOrderReviewImages as $img) {
        $initialPreview[] = legacy_asset('files/reviewimages/' . $img->image);
        $initialPreviewConfig[] = [
            'caption' => $img->image,
            'filename' => $img->image,
            'key' => $img->id,
            "width" => "120px",
            "downloadUrl" => legacy_asset('files/reviewimages/' . $img->image),
        ];
    }

@endphp

@extends('admin.layouts.app')

@push('styles')
    <style type="text/css">
        .krajee-default.file-preview-frame .kv-file-content {
            width: 210px;
            height: 160px;
        }
    </style>
@endpush

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Beginning</span> Condition Report
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-6">
                    <legend class="text-semibold">
                        <i class="icon-file-text2 position-left"></i> Review Details
                    </legend>

                    <form method="post"
                        action="{{ url('admin/booking_reviews/reservationreview/' . base64_encode($orderid)) }}"
                        id="frmadmin" class="form-horizontal" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                Details:
                            </label>
                            <div class="col-lg-9">
                                <textarea name="CsOrderReview[details]" rows="3"
                                    class="form-control">{{ data_get($csOrderReview, 'details', '') }}</textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                Beginning Mileage
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="CsOrderReview[mileage]"
                                    value="{{ data_get($csOrderReview, 'mileage', 0) }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">&nbsp;</label>
                            <div class="col-lg-9">
                                <button type="submit" class="btn btn-primary">
                                    Update <i class="icon-database-insert position-right"></i>
                                </button>
                                <button type="button" class="btn left-margin btn-cancel"
                                    onclick="goBack('/admin/vehicle_reservations/index')">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <input type="hidden" name="CsOrderReview[id]" value="{{ data_get($csOrderReview, 'id', '') }}">
                        <input type="hidden" name="CsOrderReview[reservation_id]" value="{{ $orderid }}">

                    </form>

                    <div class="form-group">
                        <label class="text-semibold">
                            Additional Review Images
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">
                            Review Images
                        </label>
                        <div class="col-lg-9">
                            <input type="file" class="fileinputajax" multiple="multiple" name="reviewimage"
                                data-show-preview=true data-show-upload="true">
                            <span class="help-block">You can select multiple images.</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <legend class="text-semibold">
                        <i class="icon-file-text2 position-left"></i> Pickup Driver's License Details
                    </legend>

                    @if(
                            empty($pickupData)
                            || (empty($pickupData['LicenseDetail']) && empty($pickupData['LICENSEDOC']))
                        )
                        <div class="alert alert-warning alert-styled-left">
                            <span class="text-semibold">Licence Data: No data available</span>
                        </div>
                    @endif

                    @if(
                            !empty($pickupData)
                            && !empty($pickupData['LicenseDetail'])
                        )
                        <div class="col-lg-12">
                            <span class="text-semibold">Licence Data:</span>
                            <pre
                                class="content-group language-markup"> <code class="language-markup"> {{ print_r($pickupData['LicenseDetail']) }} </code> </pre>
                        </div>
                    @endif

                    @if(
                            !empty($pickupData)
                            && !empty($pickupData['LICENSEDOC'])
                        )
                        <div class="col-lg-12">
                            <span class="text-semibold">License Scan:</span><br />
                            @foreach($pickupData['LICENSEDOC'] as $doc)
                                <a href="{{ legacy_asset('files/reservation/' . $doc) }}" target="_blank">
                                    <img height="150px" width="150px" src="{{ legacy_asset('files/reservation/' . $doc) }}" />
                                </a>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/assets/js/plugins/uploaders/fileinput.min.js') }}"></script>
    <script>
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
        });

        $(function () {
            $(".fileinputajax").fileinput({
                showUpload: false,
                uploadUrl: "{{ url('admin/booking_reviews/saveImage') }}",
                uploadAsync: true,
                maxFileCount: 15,
                deleteUrl: "{{ url('admin/booking_reviews/deleteImage') }}",
                allowedFileExtensions: ['jpeg', 'jpg', 'png', 'pdf'],
                initialPreview: @json($initialPreview),
                overwriteInitial: false,
                initialPreviewAsData: true,
                initialPreviewFileType: 'image',
                initialPreviewConfig: @json($initialPreviewConfig),
                maxFileSize: 1024,
                uploadExtraData: {
                    'id': '{{ data_get($csOrderReview, 'id', '')}}'
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
                    //showDrag: false,
                    showZoom: true,
                    //showUpload: false,
                    //showDelete: false,
                }
            }).on("filebatchselected", function (event, files) {
                $(".fileinputajax").fileinput("upload");
            }).on('fileuploaded', function (event, data, previewId, index) {
                $("#" + previewId + " button.kv-file-remove").attr('data-key', data.response.key);
            });
        });

    </script>
@endpush