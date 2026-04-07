@extends('layouts.default')
@section('content')
<div class="page-container login-container">
    <div class="page-content">
        <div class="content-wrapper">
            <div class="content">
                <div class="row">
                    <div class="col-lg-6 col-lg-offset-3">
                        <div class="panel registration-form">
                            <div class="panel-body">
                                <div class="text-center">
                                    <h5 class="content-group-lg">Complete Payments</h5>
                                </div>
                                @if(session('success'))<div>{{ session('success') }}</div>@endif
                                @if(session('error'))<div>{{ session('error') }}</div>@endif
                                <p class="text-center">Your account is verified successfully. Continue to complete card/connect setup.</p>
                                <div class="text-center">
                                    <a href="{{ url('/logins/index') }}" class="btn btn-success">Go to Login</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
