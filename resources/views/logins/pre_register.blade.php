@extends('layouts.driveitaway')

@section('content')
    <div class="page-container login-container">
        <div class="page-content">
            <div class="content-wrapper">
                <div class="content">
                    <form action="{{ url('/logins/pre_register') }}" method="POST" id="CustomerRegister" name="frmadmin">
                        @csrf
                        <div class="panel panel-body login-form">
                            <div class="row">
                                @include('partials.flash')
                            </div>
                            <div class="panel-body">
                                <div class="text-center">
                                    <div class="icon-object border-success text-success">
                                        <i class="icon-plus3"></i>
                                    </div>
                                    <h5 class="content-group-lg">{{ 'Create account' }}
                                        <small class="display-block">
                                            {{ 'All fields are required' }}
                                        </small>
                                    </h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group has-feedback">
                                            <input name="User[contact_number]" maxlength="14" class="form-control required"
                                                placeholder="Phone">
                                            <div class="form-control-feedback">
                                                <i class="icon-user-lock text-muted"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <img id="captcha" src="{{ url('logins/securimage/0') }}" alt="CAPTCHA image">
                                            <a href="#" title="{{'Load a different image.'}}"
                                                onclick="document.getElementById('captcha').src = '{{ url('logins/securimage') }}/' + Math.random(); return false;">
                                                <i class="icon-refresh">
                                                    <img src="{{ asset('img/reset-btn.png') }}" alt="Reload"
                                                        class="reload_img">
                                                </i>
                                            </a>
                                            <br />
                                            <input type="text" name="captcha_code" id="captcha_code"
                                                class="form-control required" value="" required>

                                            <div style="margin-top: 5px; color: #000000; font-size: 10px;">
                                                {{'Enter the above code' }}
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="text-right">
                                    <a class="btn btn-link" href="{{ url('/logins/index') }}">
                                        <i class="icon-arrow-left13 position-left"></i>
                                        {{ 'Back to login' }}
                                    </a>
                                    <button type="submit" class="btn bg-teal-400 btn-labeled btn-labeled-right ml-10">
                                        <b><i class="icon-arrow-right13 position-right"></i></b>
                                        {{ 'Next' }}
                                    </button>
                                </div>
                            </div>
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
            jQuery("#CustomerRegister").validate();
        });
    </script>
@endpush