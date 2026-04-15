@extends('admin.layouts.app')
@section('title', 'Pick Ups')
@section('content')
<script src="{{ asset('assets/js/plugins/forms/editable/editable.min.js') }}"></script>
<link rel="stylesheet" href="{{ asset('assets/css/plugins/forms/editable/editable.css') }}">
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content"></div></div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Pick</span> Ups</h4>
        </div>
    </div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="panel">
    <div class="panel-body">
        <div style="width:100%; overflow: visible;" id="postsPaging">
            @include('admin.reservation._admin_index')
        </div>
    </div>
</div>
<script src="{{ asset('assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
<script src="{{ asset('js/admin_booking.js') }}"></script>
<script src="{{ asset('assets/js/plugins/media/fancybox.min.js') }}"></script>
<script src="{{ asset('Reservation/js/admin_reservation.js') }}"></script>
<script type="text/javascript">
$(document).ready(function(){ $(".fancybox").fancybox(); });
$(function(){
    $.fn.editable.defaults.highlight = false;
    $.fn.editable.defaults.mode = 'popup';
    $.fn.editableform.template = '<form class="editableform form-horizontal"><div class="control-group"><div class="editable-input"></div> <div class="editable-buttons"></div><div class="editable-error-block"></div></div></form>';
    $.fn.editableform.buttons = '<button type="submit" class="btn btn-info btn-icon editable-submit"><i class="icon-check"></i></button><button type="button" class="btn btn-default btn-icon editable-cancel"><i class="icon-x"></i></button>';
});
</script>
<style>.datepicker .prev,.datepicker .next{background: none;}</style>
@endsection
