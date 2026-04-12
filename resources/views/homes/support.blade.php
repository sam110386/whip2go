@extends('layouts.default')

@section('title', $metatitle ?? ($title_for_layout ?? 'Support'))

@push('meta')
@if(!empty($metakeywords))
    <meta name="keywords" content="{{ e($metakeywords) }}">
@endif
@if(!empty($metadescription))
    <meta name="description" content="{{ e($metadescription) }}">
@endif
@endpush

@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                @if(session('success'))<p class="text-success">{{ session('success') }}</p>@endif
                @if(session('error'))<p class="text-danger">{{ session('error') }}</p>@endif

                @if(!empty($discription))
                    <div class="support-page-body">{!! $discription !!}</div>
                @endif

                <div class="panel" style="margin-top:20px;">
                    <div class="panel-body">
                        <form method="post" action="/homes/support" class="form-horizontal" id="CustomerContact">
                            @csrf
                            <fieldset>
                                <legend class="text-semibold"><i class="icon-reading position-left"></i> Contact</legend>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-lg-3 control-label">First Name:</label>
                                        <div class="col-lg-9">
                                            <input type="text" name="Contact[first_name]" class="form-control required" value="{{ old('Contact.first_name') }}" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-3 control-label">Last Name:</label>
                                        <div class="col-lg-9">
                                            <input type="text" name="Contact[last_name]" class="form-control required" value="{{ old('Contact.last_name') }}" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-3 control-label">Email:</label>
                                        <div class="col-lg-9">
                                            <input type="email" name="Contact[email]" class="form-control email required" value="{{ old('Contact.email') }}" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-3 control-label">Phone #:</label>
                                        <div class="col-lg-9">
                                            <input type="text" name="Contact[phone]" class="form-control" value="{{ old('Contact.phone') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-lg-3 control-label">Your message:</label>
                                        <div class="col-lg-9">
                                            <textarea name="Contact[comment]" class="form-control" rows="5" cols="5" placeholder="Enter your message here" required>{{ old('Contact.comment') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary">Send <i class="icon-arrow-right14 position-right"></i></button>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(document).ready(function () {
    if (jQuery('#CustomerContact').validate) {
        jQuery('#CustomerContact').validate();
    }
});
</script>
@endpush
