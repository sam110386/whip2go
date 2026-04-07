@extends('layouts.driveitaway')
@section('content')
<div class="page-container login-container">
    <div class="page-content">
        <div class="content-wrapper">
            <div class="content">
                <form action="{{ url('/logins/register/' . ($contact_number ?? '')) }}" method="POST" id="CustomerRegister" name="frmadmin">
                    @csrf
                    <div class="col-lg-6 col-lg-offset-3">
                        <div class="panel registration-form">
                            @if(session('success'))<div>{{ session('success') }}</div>@endif
                            @if(session('error'))<div>{{ session('error') }}</div>@endif
                            <div class="panel-body">
                                <div class="text-center">
                                    <div class="icon-object border-success text-success"><i class="icon-plus3"></i></div>
                                    <h5 class="content-group-lg">Complete Registration <small class="display-block">All fields are required</small></h5>
                                </div>
                                <input name="User[first_name]" class="form-control required" placeholder="First name">
                                <input name="User[last_name]" class="form-control required" placeholder="Last name">
                                <input name="User[email]" class="form-control email required" placeholder="Your Email">
                                <input name="User[cemail]" class="form-control email required" placeholder="Repeat Email">
                                <input name="User[npwd]" type="password" class="form-control required" id="password" placeholder="Password">
                                <input name="User[conpwd]" type="password" class="form-control required" placeholder="Repeat password">
                                <input name="User[address]" class="form-control required" placeholder="Address">
                                <input name="User[city]" class="form-control required" placeholder="City">
                                <input name="User[zip]" class="form-control required" placeholder="Zip">
                                <input name="User[state]" class="form-control required" placeholder="State">
                                <input type="hidden" name="User[username]" value="{{ $contact_number ?? '' }}">
                                <div class="checkbox">
                                    <label><input type="checkbox" name="User[terms_conditions]" class="required"> Please confirm that you have read our Terms & Conditions and Privacy Policy</label>
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="btn bg-teal-400 btn-labeled btn-labeled-right ml-10"><b><i class="icon-plus3"></i></b> Create account</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
