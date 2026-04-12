@extends('layouts.main')

@section('title', $title_for_layout ?? 'Messages')
@section('header_title', $title_for_layout ?? 'Messages')

@section('content')
<style type="text/css">
    .media { cursor: pointer; }
</style>
<div class="panele">
    <section class="right_content">
        <div class="page-header">
            <div class="page-header-content">
                <div class="pagetitle">
                    <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Messages</span></h4>
                </div>
            </div>
        </div>
        @if(session('success'))<p class="text-success">{{ session('success') }}</p>@endif
        @if(session('error'))<p class="text-danger">{{ session('error') }}</p>@endif
        <form method="get" action="/smslogs/index" class="form-inline" style="margin-bottom:12px;">
            <input type="text" name="searchKey" value="{{ e($keyword ?? '') }}" class="form-control" placeholder="Phone#">
            <button type="submit" class="btn btn-default">Search</button>
        </form>
        <div class="row">
            <div class="col-md-5" id="postsPaging">
                @include('smslogs.partials.userlist', ['logsPaginator' => $logsPaginator, 'keyword' => $keyword ?? ''])
            </div>
            <div class="col-md-7">
                <div id="chatwindow" class="modalfade"></div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
jQuery(document).ready(function () {
    if (jQuery('ul#userlist li').length) {
        jQuery('ul#userlist li:first').trigger('click');
    }
});
function loadchat(phone, userid) {
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Loading...</h1>',
        css: { 'z-index': '9999' }
    });
    jQuery('#chatwindow').load(SITE_URL + 'smslogs/loadchat', { phone: phone, userid: userid }, function () {
        jQuery.unblockUI();
        jQuery('#chatwindow ul.chat-list').animate({
            scrollTop: jQuery('#chatwindow ul.chat-list').prop('scrollHeight')
        }, 500);
    });
}
function sendmessage(phone, userid) {
    var message = jQuery('#chatwindow #usermessage').val();
    if (message.trim().length === 0) {
        alert('Please enter message to send.');
        return false;
    }
    jQuery.blockUI({
        message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
        css: { 'z-index': '9999' }
    });
    jQuery.post(SITE_URL + 'smslogs/sendmessage', { phone: phone, userid: userid, message: message }, function (resp) {
        jQuery.unblockUI();
        if (resp.status) {
            jQuery('#chatwindow').load(SITE_URL + 'smslogs/loadchat', { phone: phone, userid: userid }, function () {
                jQuery('#chatwindow ul.chat-list').animate({
                    scrollTop: jQuery('#chatwindow ul.chat-list').prop('scrollHeight')
                }, 500);
            });
        } else {
            alert(resp.message);
        }
    }, 'json');
}
</script>
@endpush
