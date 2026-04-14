@extends('layouts.admin')

@section('content')
<script src="{{ asset('assets/js/plugins/editors/ckeditor/ckeditor.js') }}"></script>

<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#frmadmin").validate({
            ignore: [':hidden:not(.vehicle_id)', ':hidden:not(.renter_id)']
        });

        CKEDITOR.replace('wysihtml5', {
            height: '600px',
            extraPlugins: 'forms',
            docType: '<!DOCTYPE html>',
            on: {
                instanceReady: function(ev) {
                    var editor = ev.editor;
                    editor.document.appendStyleSheet(
                        "html,body {\
                            font-family: 'Open Sans';\
                            font-weight: 500;\
                            font-size: 8px;\
                            -webkit-print-color-adjust: exact;\
                            box-sizing: border-box;\
                            letter-spacing: normal;\
                            text-rendering: optimize-speed;\
                        }\
                        .WordSection2 {\
                            width: 100%;\
                        }"
                    );
                }
            }
        });
    });
</script>

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold"></span> {{ $listTitle }}</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('admin/agreement_templates/index/' . base64_encode($userid)) }}" class="btn btn-default" style="float:right;">Back</a>
        </div>
    </div>
</div>
<div class="row ">
    @include('partials.flash')
</div>
<div class="panel">
    <form action="{{ url('admin/agreement_templates/rent_to_own/' . base64_encode($userid)) }}" method="POST" name="frmadmin" class="form-horizontal" enctype="multipart/form-data">
        @csrf
        <div class="panel-body">
            <div class="form-group">
                <textarea cols="18" rows="18" name="content" id="wysihtml5" class="wysihtml5 form-control" placeholder="Enter text ...">{{ $template }}</textarea>
            </div>
            <div class="ftext-right">
                <div class="col-lg-6">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ url('admin/agreement_templates/index/' . base64_encode($userid)) }}" class="btn btn-default" style="float:right;">Back</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
