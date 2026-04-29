@extends('layouts.main')

@section('title', 'Pick Ups')

@push('scripts')
<script src="{{ asset('assets/js/plugins/forms/editable/editable.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
<script src="{{ asset('js/booking.js') }}"></script>
<script src="{{ asset('js/jquery.maskedinput.js') }}"></script>
<script src="{{ asset('assets/js/plugins/media/fancybox.min.js') }}"></script>
<script src="{{ asset('Reservation/js/reservation.js') }}"></script>
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
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Pick </span>- Ups</h4>
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        <div style="width:100%; overflow: visible;" id="postsPaging">
            @include('cloud.reservation._index')
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
@endsection
