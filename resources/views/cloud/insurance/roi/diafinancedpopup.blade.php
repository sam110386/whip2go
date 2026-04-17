@extends('layouts.main')

@section('content')
<script type="text/javascript">
    $(document).ready(function() {
        $("#DriverFinancedInsuranceDiafinancedpopupForm").validate();
    });
</script>
<div class="panel">
    <form action="{{ url('insurance/roi/diafinacedsave/' . $orderandusers) }}" method="POST" name="frmadmin" id="DriverFinancedInsuranceDiafinancedpopupForm" class="form-horizontal" enctype="multipart/form-data">
    @csrf
    <div class="panel-body">
        <legend class="text-size-large text-bold">Quote Details :</legend>
        <div class="form-group">
            <label class="col-lg-2 control-label">Quote Number :</label>
            <div class="col-lg-8">
                <input type="input" name="quote_number" class="form-control required" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">Upload Quote Doc :</label>
            <div class="col-lg-12">
                <input type="file" name="quote_doc" class="file-input required" id="InsurancePayerQuoteDoc" data-show-preview="false" data-id="{{ $recordid }}" data-type="quote_doc" />
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-6">
                <button type="button" class="btn btn-primary pl-3 pr-3" id="DriverFinancedInsuranceSave">Save <i class="icon-arrow-right8 position-right"></i></button>
            </div>
        </div>
    </div>
    <input type="hidden" name="DriverFinancedInsurance[providerid]" value="{{ $providerid }}" />
    <input type="hidden" name="DriverFinancedInsurance[orderandusers]" value="{{ $orderandusers }}" />
    </form>
</div>
<script src="{{ asset('assets/js/plugins/media/fancybox.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/uploaders/fileinput.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $("#DriverFinancedInsuranceSave").click(function(){
            if($("#DriverFinancedInsuranceDiafinancedpopupForm").valid()){
                $('#InsurancePayerQuoteDoc').fileinput('upload');
            }
        });
        $('#InsurancePayerQuoteDoc').fileinput({
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
            allowedFileExtensions: ['jpeg', 'jpg', 'png', 'pdf'],
            overwriteInitial: false,
            maxFileSize: 10024,
            uploadExtraData: {
                'id': '{{ $recordid }}',
                'type': 'quote_doc',
                'providerid':'{{ $providerid }}'
            },
            showCancel: false,
            showRemove: false,
            showCaption:false
        }).on('fileuploaded', function(event, data, previewId, index) {
            $("#DriverFinancedInsuranceDiafinancedpopupForm").submit();
        }).on('filebatchuploadcomplete', function() {
            $("#DriverFinancedInsuranceDiafinancedpopupForm").submit();
        });
    });
</script>
@endsection
