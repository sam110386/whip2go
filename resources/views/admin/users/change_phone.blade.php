@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Change Phone')

@section('content')
    @php
        $returnUrl = url('admin/users/index');
        $action = url('admin/users/change_phone/' . base64_encode($id));
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $listTitle ?? 'Change Phone' }}</span>
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="frmadmin" class="btn btn-primary">Update</button>
                    <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form action="{{ $action }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
        @csrf
        <input type="hidden" name="id" value="{{ $user->id ?? '' }}">
        <input type="hidden" name="old_username" value="{{ old('old_username', $user->username ?? '') }}">

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Phone</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Phone # :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="contact_number" id="UserContactNumber" value="{{ old('contact_number', $user->contact_number ?? '') }}" maxlength="12" class="form-control phone required">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#frmadmin").validate();
        // jQuery('#UserContactNumber').mask("(999)-999-9999", {placeholder: "(xxx)-xxx-xxxx"});
    });
</script>
@endpush
