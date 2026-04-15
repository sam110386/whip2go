@extends('admin.layouts.app')

@section('title', $listTitle)

@section('content')
<script src="{{ asset('js/assets/js/plugins/editors/ckeditor/ckeditor.js') }}"></script>

<script type="text/javascript">
    jQuery(document).ready(function() {
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
                            font-size: 14px;\
                            -webkit-print-color-adjust: exact;\
                            box-sizing: border-box;\
                            letter-spacing: normal;\
                            text-rendering: optimize-speed;\
                        }\
                        .WordSection2 {\
                            width: 100%;\
                        }\
                        .left{float: left;}\
                        .right{float: right;}\
                        .d-full {width: 100%;float: left;}\
                        .ul-full{width: 100%;float: left;list-style: none;}\
                        .ul-full li{line-height: 14px;}"
                    );
                }
            }
        });
    });
</script>

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <a href="{{ url('admin/agreement_templates/index', $useridB64) }}"><i class="icon-arrow-left52 position-left"></i></a>
                <span class="text-semibold">{{ 'User' }}</span> — {{ 'Agreement Templates' }} — <small>{{ $listTitle }}</small>
            </h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('admin/agreement_templates/index', $useridB64) }}" class="btn btn-default">Back To Templates</a>
        </div>
    </div>
</div>

<div class="row">
    @include('partials.flash')
</div>

<div class="panel panel-flat">
    <div class="panel-heading">
        <h5 class="panel-title">{{ $listTitle }}</h5>
    </div>
    <form action="{{ url('admin/agreement_templates/rent_to_own', $useridB64) }}" method="POST" name="frmadmin" class="form-horizontal" enctype="multipart/form-data">
        @csrf
        <div class="panel-body">
            <div class="form-group">
                <textarea name="content" id="wysihtml5" class="wysihtml5 form-control">{{ $template }}</textarea>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">Save Template <i class="icon-arrow-right14 position-right"></i></button>
            </div>
        </div>
    </form>
</div>
@endsection
