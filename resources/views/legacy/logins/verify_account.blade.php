@extends('layouts.driveitaway')

@section('content')
    <div class="page-container login-container">
        <div class="page-content">
            <div class="content-wrapper">
                <div class="content">
                    <form action="{{ url('/logins/verifyAccount') }}" method="POST" id="CustomerRegister" name="frmadmin">
                        @csrf
                        <div class="panel panel-body login-form">
                            @if (session('success'))
                                <div>{{ session('success') }}</div>
                            @endif
                            @if (session('error'))
                                <div>{{ session('error') }}</div>
                            @endif
                            <div class="panel-body">
                                <div class="text-center">
                                    <div class="icon-object border-success text-success">
                                        <i class="icon-plus3"></i>
                                    </div>
                                    <h5 class="content-group-lg">
                                        {{ 'Verify Account' }}
                                    </h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group has-feedback">
                                            <input name="User[activationCode]" maxlength="14" class="form-control required"
                                                placeholder="Enter activation code">
                                            <div class="form-control-feedback">
                                                <i class="icon-user-lock text-muted"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <a class="btn btn-link" href="{{ url('/logins/resendActivation') }}">
                                        <i class="icon-arrow-left13 position-left"></i>
                                        {{ 'Resend' }}
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
        jQuery("#CustomerRegister").validate();
        jQuery('#UserActivationCode').mask("0-0-0-0-0");
    </script>
@endpush
