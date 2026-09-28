@extends('admin.layouts.app')

@php
    $title ??= 'Change Phone#';
    $id ??= '';
    $user ??= collect();
@endphp

@section('title', $title)

@section('content')

    <div class="panel">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
            <h3 style="width: 80%; float: left;"> {{ $title }} </h3>
        </section>

        <div class="row">
            @includeif('partials.flash')
        </div>

        <div class="row">
            <fieldset class="col-lg-12">
                <form action="{{ url('admin/users/change_phone/' . base64_encode($id)) }}" method="POST" name="frmadmin"
                    id="frmadmin" class="form-horizontal">
                    @csrf

                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">
                                Phone # :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-4">
                                <input type="text" name="contact_number" id="UserContactNumber"
                                    value="{{ old('contact_number', $user->contact_number ?? '') }}" maxlength="12"
                                    class="form-control phone required">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="submit" class="btn">
                                    Update
                                </button>
                                <button type="button" class="btn left-margin btn-cancel"
                                    onclick="goBack('/admin/users/index')">
                                    Return
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="id" value="{{ data_get($user, 'id', '') }}">
                    <input type="hidden" name="old_username"
                        value="{{ old('old_username', data_get($user, 'username', '')) }}">

                </form>
            </fieldset>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/jquery.maskedinput.js') }}"></script>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
            jQuery('#UserContactNumber').mask("(999)-999-9999", { placeholder: "(xxx)-xxx-xxxx" });
        });
    </script>
@endpush