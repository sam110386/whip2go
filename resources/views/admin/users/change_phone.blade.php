@extends('admin.layouts.app')

@section('title', $listTitle)

@section('content')
<div class="panel">
    <script src="{{ legacy_asset('js/jquery.maskedinput.js') }}"></script>
    <script>
        jQuery(document).ready(function () {
            jQuery('#frmadmin').validate();
            jQuery('#UserContactNumber').mask('(999)-999-9999', {placeholder: '(xxx)-xxx-xxxx'});
        });
    </script>

    <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
        <h3 style="width: 80%; float: left;">{{ $listTitle }}</h3>
    </section>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <fieldset class="col-lg-12">
            <form action="/admin/users/change_phone/{{ base64_encode((string)$id) }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
                @csrf
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Phone # :<span class="text-danger">*</span></label>
                        <div class="col-lg-4">
                            <input type="text" id="UserContactNumber" maxlength="12" name="User[contact_number]" class="form-control phone required" value="{{ $user->contact_number ?? '' }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="submit" class="btn">Update</button>
                            <button type="button" class="btn left-margin btn-cancel" onclick="window.location.href='/admin/users/index'">Return</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="User[id]" value="{{ $user->id }}">
                <input type="hidden" name="User[old_username]" value="{{ $user->username }}">
            </form>
        </fieldset>
    </div>
</div>
@endsection
