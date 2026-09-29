@extends('admin.layouts.app')

@php
    $title ??= 'Update Rental Agreement Template';
    $template ??= '';
    $userid ??= '';
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i> {{ $title }}
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/agreement_templates/index', $userid) }}" class="btn btn-default float-right;">
                    Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <form action="{{ url('admin/agreement_templates/rental', base64_encode($userid)) }}" method="POST" name="frmadmin"
            class="form-horizontal" enctype="multipart/form-data">
            @csrf

            <div class="panel-body">
                <div class="form-group">
                    <textarea name="AgreementTemplate[content]" id="wysihtml5" class="wysihtml5 form-control" cols="18"
                        rows="18" placeholder="Enter text ...">{{ $template }}</textarea>
                </div>
                <div class="ftext-right">
                    <div class="col-lg-6">
                        <button type="submit" class="btn btn-primary"> Save </button>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/assets/js/plugins/editors/ckeditor/ckeditor.js') }}"></script>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate({
                ignore: [':hidden:not(.vehicle_id)', ':hidden:not(.renter_id)']
            });

            CKEDITOR.replace('wysihtml5', {
                height: '600px',
                extraPlugins: 'forms',
                docType: '<!DOCTYPE html>',
                on: {
                    instanceReady: function (ev) {
                        ev.editor.document.appendStyleSheet('{{ asset('js/ckeditor/editor-content.css') }}');
                    }
                }
            });
        });
    </script>
@endpush