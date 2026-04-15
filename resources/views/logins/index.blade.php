@extends('layouts.driveitaway')

@section('content')
    <div class="page-container login-container">
        <div class="page-content">
            <div class="content-wrapper">
                <div class="content">
                    <form action="{{ url('/logins/index') }}" method="POST" id="userlogin" name="userlogin">
                        @csrf
                        <div class="panel panel-body login-form">

                            <div class="row">
                                @include('partials.flash')
                            </div>

                            <div class="text-center">
                                <div class="icon-object border-slate-300 text-slate-300"><i class="icon-reading"></i></div>
                                <h5 class="content-group">
                                    {{ 'Login to your account' }}
                                    <small class="display-block">
                                        {{ 'Your credentials' }}
                                    </small>
                                </h5>
                            </div>
                            <div class="form-group has-feedback has-feedback-left">
                                <input name="User[email]" type="text" class="form-control required" placeholder="Username">
                                <div class="form-control-feedback">
                                    <i class="icon-user-check text-muted"></i>
                                </div>
                            </div>
                            <div class="form-group has-feedback has-feedback-left">
                                <input name="User[user_password]" type="password" class="form-control required"
                                    placeholder="Password">
                                <div class="form-control-feedback">
                                    <i class="icon-user-lock text-muted"></i>
                                </div>
                            </div>
                            <div class="form-group login-options">
                                <div class="row">
                                    <div class="col-sm-12 text-right">
                                        <a href="{{ url('/logins/forgotPassword') }}">
                                            {{ 'Forgot password?' }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn bg-blue btn-block">
                                    {{ 'Login' }}
                                    <i class="icon-arrow-right14 position-right"></i>
                                </button>
                            </div>
                            <div class="content-divider text-muted form-group">
                                <span>{{ 'Don\'t have an account?' }}</span>
                            </div>
                            <a href="{{ url('/logins/pre_register') }}" class="btn bg-pink btn-block content-group">
                                {{ 'Sign up' }}
                            </a>
                            <span class="help-block text-center no-margin">
                                {{ 'By continuing, you\'re confirming that you\'ve read our' }}
                                <a href="#">{{ 'Terms & Conditions' }}</a> {{ 'and' }}
                                <a href="#"> {{ 'Cookie Policy' }} </a>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#userlogin").validate({
                errorClass: 'validation-error-label'
            });
        });
    </script>
@endpush