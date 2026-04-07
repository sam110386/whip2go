@extends('layouts.driveitaway')
@section('content')
<div class="page-container login-container">
    <div class="page-content">
        <div class="content-wrapper">
            <div class="content">
                <form action="{{ url('/logins/forgotPassword') }}" method="POST" id="frmAdminuser" name="frmLogin">
                    @csrf
                    <div class="panel panel-body login-form">
                        @if(session('success'))<div>{{ session('success') }}</div>@endif
                        @if(session('error'))<div>{{ session('error') }}</div>@endif
                        <div class="text-center">
                            <div class="icon-object border-warning text-warning"><i class="icon-spinner11"></i></div>
                            <h5 class="content-group">Password recovery <small class="display-block">We'll send you instructions in email</small></h5>
                        </div>
                        <div class="form-group has-feedback">
                            <input name="User[email]" maxlength="100" class="form-control required" placeholder="Email">
                            <div class="form-control-feedback"><i class="icon-mail5 text-muted"></i></div>
                        </div>
                        <button class="btn bg-blue btn-block" type="submit">Reset password <i class="icon-arrow-right14 position-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
