@extends('layouts.driveitaway')
@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <center><h1>Fill below form if you want to delete your account</h1></center>
                <div class="panel">
                    <div class="panel-body">
                        @if(session('success'))<div>{{ session('success') }}</div>@endif
                        @if(session('error'))<div>{{ session('error') }}</div>@endif
                        <div class="row">
                            <div class="col-md-10 col-xs-12">
                                <form action="{{ url('/contactus') }}" method="POST" class="form-horizontal" id="CustomerContact">
                                    @csrf
                                    <fieldset>
                                        <legend class="text-semibold"><i class="icon-reading position-left"></i> General details</legend>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">First Name:</label>
                                                <div class="col-lg-9"><input name="Contact[first_name]" class="form-control required"></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">Last Name:</label>
                                                <div class="col-lg-9"><input name="Contact[last_name]" class="form-control required"></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">Email:</label>
                                                <div class="col-lg-9"><input name="Contact[email]" class="form-control email required"></div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">Phone #:</label>
                                                <div class="col-lg-9"><input name="Contact[phone]" class="form-control phone required"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">I am a:</label>
                                                <div class="col-lg-9">
                                                    <select name="Contact[usertype]" class="form-control required">
                                                        <option value="Driver">Driver</option>
                                                        <option value="Dealer">Dealer</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">Your message:</label>
                                                <div class="col-lg-9"><textarea name="Contact[comment]" class="form-control" rows="5" cols="5" placeholder="Enter your message here"></textarea></div>
                                            </div>
                                            <div class="text-right"><button type="submit" class="btn btn-primary">Send <i class="icon-arrow-right14 position-right"></i></button></div>
                                        </div>
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
