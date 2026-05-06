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

    <div class="row">
        <div class="col-lg-6">
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
                            <label class="col-lg-3 control-label text-semibold">Beginning Mileage:</label>
                            <div class="col-lg-9">
                                <input type="text" name="CsOrderReview[mileage]" value="{{ $CsOrderReview['CsOrderReview']['mileage'] ?? 0 }}" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-lg-9 col-lg-offset-3">
                                <button type="submit" class="btn btn-primary">Update</button>
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

        <div class="col-lg-6">
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
                            <pre class="content-group language-markup"><code class="language-markup">{{ print_r($pickup_data['LicenseDetail'], true) }}</code></pre>
                        </div>
                    @endif

                    @if(!empty($pickup_data) && !empty($pickup_data['LICENSEDOC']))
                        <div class="col-lg-12">
                            <span class="text-semibold">License Scan:</span><br/>
                            @foreach($pickup_data['LICENSEDOC'] as $doc)
                                <img height="150px" width="150px" src="{{ legacy_asset('files/reservation/' . $doc) }}" class="img-thumbnail" style="margin-right:5px; margin-bottom:5px;" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/plugins/uploaders/fileinput.min.js') }}"></script>
<script>
    $(function() {
        $(".fileinputajax").fileinput({
            showUpload: false,
            uploadUrl: SITE_URL + "admin/booking_reviews/saveImage",
            uploadAsync: true,
            maxFileCount: 15,
            deleteUrl: SITE_URL + "admin/booking_reviews/deleteImage",
            allowedFileExtensions: ['jpeg', 'jpg', 'png', 'pdf'],
            initialPreview: {!! json_encode(collect($CsOrderReview['CsOrderReviewImage'] ?? [])->map(fn($img) => legacy_asset('files/reviewimages/' . $img['image']))->toArray()) !!},
            overwriteInitial: false,
            initialPreviewAsData: true,
            initialPreviewConfig: {!! json_encode(collect($CsOrderReview['CsOrderReviewImage'] ?? [])->map(function($img) {
                $ext = strtolower(pathinfo($img['image'], PATHINFO_EXTENSION));
                $type = in_array($ext, ['jpeg', 'jpg', 'png']) ? 'image' : ($ext === 'pdf' ? 'pdf' : 'other');
                return [
                    'caption' => $img['image'],
                    'filename' => $img['image'],
                    'key' => $img['id'],
                    'width' => '120px',
                    'downloadUrl' => legacy_asset('files/reviewimages/' . $img['image']),
                    'type' => $type
                ];
            })->toArray()) !!},
            maxFileSize: 5120,
            uploadExtraData: {
                'id': '{{ $CsOrderReview['CsOrderReview']['id'] ?? '' }}'
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
</script>
@endpush
