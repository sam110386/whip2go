@extends('layouts.driveitaway')

@section('content')
    <div class="page-container login-container">
        <div class="page-content">
            <div class="content-wrapper">
                <div class="content">
                    <form action="{{ url('/logins/resetpassword/' . ($token ?? '')) }}" method="POST" id="frmAdminuser"
                        name="frmLogin">
                        @csrf
                        <div class="panel panel-body login-form">
                            @if (session('success'))
                                <div>{{ session('success') }}</div>
                            @endif
                            @if (session('error'))
                                <div>{{ session('error') }}</div>
                            @endif
                            <div class="text-center">
                                <div class="icon-object border-warning text-warning"><i class="icon-spinner11"></i></div>
                                <h5 class="content-group">{{ 'Reset Password' }}</h5>
                            </div>
                            <div class="form-group has-feedback has-feedback-left">
                                <input type="password" name="User[password]" class="form-control required"
                                    placeholder="New Password">
                                <div class="form-control-feedback">
                                    <i class="icon-user-lock text-muted"></i>
                                </div>
                            </div>
                            <div class="form-group has-feedback has-feedback-left">
                                <input type="password" name="User[con_password]" class="form-control required"
                                    placeholder="Confirm Password">
                                <div class="form-control-feedback">
                                    <i class="icon-user-lock text-muted"></i>
                                </div>
                            </div>
                            <button class="btn bg-blue btn-block" type="submit">
                                {{ 'Update Password' }}
                                <i class="icon-arrow-right14 position-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery("#frmAdminuser").validate({
            errorClass: 'validation-error-label',
            rules: {
                'data[User][password]': {
                    minlength: 5
                },
                'data[User][con_password]': {
                    minlength: 5,
                    equalTo: "#UserPassword"
                }
            }
        });
    </script>
@endpush
