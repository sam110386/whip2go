@extends('admin.layouts.app')

@section('title', 'Change Password')

@section('content')
    @php
        $returnUrl = '/admin/homes/dashboard';
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage Change Password</span>
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <form method="POST" action="{{ url('/admin/admins/change_password') }}" id="frmadmin" name="frmadmin" class="form-horizontal">
            @csrf

            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> Old Password : </label>
                    <div class="col-lg-9">
                        <input type="password" name="User[oldPassword]" class="form-control required" required maxlength="20" minlength="6" autocomplete="current-password">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> New Password : </label>
                    <div class="col-lg-9">
                        <input type="password" name="User[newpassword]" id="UserNewpassword" class="form-control required" required minlength="6" autocomplete="new-password">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label"><span class="text-danger">*</span> Confirm Password : </label>
                    <div class="col-lg-9">
                        <input type="password" name="User[confirmpassword]" id="UserConfirmpassword" class="form-control required" required minlength="6" autocomplete="new-password">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-offset-3 col-lg-9">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-default" onclick="window.location.href='{{ $returnUrl }}'">Cancel</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            // Block spaces in password fields
            $('#UserNewpassword, #UserConfirmpassword').on('keypress', function(e) {
                if (e.which == 32) {
                    return false;
                }
            });
        });
    </script>
@endpush
