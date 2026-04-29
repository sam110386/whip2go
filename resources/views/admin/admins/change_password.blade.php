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
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="frmadmin" class="btn btn-primary">Submit</button>
                    <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form method="POST" action="/admin/admins/change_password" id="frmadmin" name="frmadmin" class="form-horizontal">
        @csrf

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Change Password</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Old Password :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="password" name="User[oldPassword]" class="form-control" required autocomplete="current-password">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">New Password :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="password" name="User[newpassword]" id="UserNewpassword" class="form-control" required autocomplete="new-password">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Confirm Password :<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="password" name="User[confirmpassword]" id="UserConfirmpassword" class="form-control" required autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
            </div>
        </div>
    </form>
@endsection
