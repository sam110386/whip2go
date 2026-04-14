@extends('layouts.main')

@section('content')
<script type="text/javascript">
    $(document).ready(function(){
        $("#InsurancePayerPopupForm").validate();
        $(".fancybox").fancybox();
    });
</script>
<div class="panel">

    <form action="{{ url('admin/insurance/payers/save') }}" method="POST" name="frmadmin" id="InsurancePayerPopupForm" class="form-horizontal" enctype="multipart/form-data">
    @csrf
    <div class="panel-body">
        <legend class="text-size-large text-bold">Declaration Doc :</legend>
        <div class="form-group">
            <label class="col-lg-2 control-label">Upload :</label>
            <div class="col-lg-8">
                <input type="file" name="declaration_doc" class="file-input required" id="InsurancePayerDeclarationDoc" data-show-preview="false" data-id="{{ $recordid }}" data-type="declaration_doc" />
            </div>
            <div class="col-lg-2">
                @if(!empty($data['InsurancePayer']['declaration_doc']))
                    <a href="{{ config('app.url') }}/files/reservation/{{ $data['InsurancePayer']['declaration_doc'] }}" title="Driver License" class="fancybox"><i class="icon-magazine"></i></a>
                @endif
            </div>
        </div>
        <legend class="text-size-large text-bold">Insurance Card :</legend>
        <div class="form-group">
            <label class="col-lg-2 control-label">Upload :</label>
            <div class="col-lg-8">
                <input type="file" name="insurance_card" class="file-input required" id="InsurancePayerInsuranceCard" data-show-preview="false" data-id="{{ $recordid }}" data-type="insurance_card" />
            </div>
            <div class="col-lg-2">
                @if(!empty($data['InsurancePayer']['insurance_card']))
                    <a href="{{ config('app.url') }}/files/reservation/{{ $data['InsurancePayer']['insurance_card'] }}" title="Driver License" class="fancybox"><i class="icon-magazine"></i></a>
                @endif
            </div>
        </div>

        <div class="col-lg-12">
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-6">
                    <button type="button" class="btn bg-slate-400 btn-ladda  pl-3 pr-3" onclick="goBack('/insurance/roi/display')"><i class="icon-arrow-left8 position-left"></i> Back</button>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="InsurancePayer[id]" value="{{ $data['InsurancePayer']['id'] ?? '' }}" />
    <input type="hidden" name="InsurancePayer[order_deposit_rule_id]" value="{{ $recordid }}" />
    </form>
</div>
<script src="{{ asset('assets/js/plugins/media/fancybox.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/uploaders/fileinput.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function(){
    $('#InsurancePayerDeclarationDoc').fileinput({
            browseLabel: 'Browse',
            browseIcon: '<i class="icon-file-plus"></i>',
            uploadIcon: '<i class="icon-file-upload2"></i>',
            removeIcon: '<i class="icon-cross3"></i>',
            layoutTemplates: {
                icon: '<i class="icon-file-check"></i>'
            },
            uploadUrl: SITE_URL + "insurance/roi/saveImage", // server upload action
            uploadAsync: true,
            maxFileCount: 1,
            deleteUrl: SITE_URL + "insurance/roi/deleteImage",
            allowedFileExtensions: ['jpeg', 'jpg', 'png','pdf'],
            overwriteInitial: false,
            maxFileSize: 10024,
            uploadExtraData:{'id':'{{ $recordid }}','type':'declaration_doc'},
            showCancel:false,
            showRemove:false,
        }); 
        $('#InsurancePayerInsuranceCard').fileinput({
            browseLabel: 'Browse',
            browseIcon: '<i class="icon-file-plus"></i>',
            uploadIcon: '<i class="icon-file-upload2"></i>',
            removeIcon: '<i class="icon-cross3"></i>',
            layoutTemplates: {
                icon: '<i class="icon-file-check"></i>'
            },
            uploadUrl: SITE_URL + "insurance/roi/saveImage", // server upload action
            uploadAsync: true,
            maxFileCount: 1,
            deleteUrl: SITE_URL + "insurance/roi/deleteImage",
            allowedFileExtensions: ['jpeg', 'jpg', 'png','pdf'],
            overwriteInitial: false,
            maxFileSize: 10024,
            uploadExtraData:{'id':'{{ $recordid }}','type':'insurance_card'},
            showCancel:false,
            showRemove:false,
        }); 
    });
</script>
@endsection
