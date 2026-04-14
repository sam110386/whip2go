@extends('layouts.without_header_footer', ['title' => 'Credit Application'])
@section('content')
<style>.stepy-header li.stepy-active div { border-color: #E91E63; background-color: #fff; color: #E91E63; }</style>
<div class="panel panel-white">
    <div class="panel-heading text-center">
        <h6 class="panel-title">DriveItAway will run a soft credit check so that lenders can periodically send offers if you are interested in buying the vehicle. This will not impact your credit NOR change your eligibility with picking up the vehicle.</h6>
    </div>
    <ul id="stepyvalidation-header" class="stepy-header">
        <li id="stepyvalidation-head-0" class="stepy-active" style="cursor: default;"><div>1</div><span>Personal data</span></li>
        <li id="stepyvalidation-head-1" style="cursor: default;"><div>2</div><span>Provide more info to get more options</span></li>
    </ul>
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <form action="{{ url('eland/elandmob/saveStepOne/' . base64_encode($userdata->id)) }}" method="POST" name="frmadmin" id="stepyvalidation" class="stepy-validation">
        @csrf
        <fieldset title="1" class="stepy-step">
            <legend class="text-semibold">Personal data</legend>
            <div class="row">
                <div class="col-md-4 col-sm-12">
                    <div class="form-group">
                        <label>First name: <span class="text-danger">*</span></label>
                        <input type="text" name="Eland[fname]" class="form-control required" placeholder="First Name" value="{{ $formData['Eland']['fname'] ?? $userdata->first_name }}" {{ !empty($userdata->first_name) ? 'readonly' : '' }}>
                        <input type="hidden" name="Eland[userid]" value="{{ base64_encode($userdata->id) }}">
                        <input type="hidden" name="Eland[licence_number]" value="{{ $userdata->licence_number ?? '' }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="form-group">
                        <label>Middle name:</label>
                        <input type="text" name="Eland[mname]" class="form-control" placeholder="Middle Name" value="{{ $formData['Eland']['mname'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="form-group">
                        <label>Last name: <span class="text-danger">*</span></label>
                        <input type="text" name="Eland[lname]" class="form-control required" placeholder="Last Name" value="{{ $formData['Eland']['lname'] ?? $userdata->last_name }}" {{ !empty($userdata->last_name) ? 'readonly' : '' }}>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Date Of Birth: <span class="text-danger">*</span></label>
                        <input type="text" name="Eland[dob]" class="form-control required" placeholder="MM/DD/YYYY" data-mask="99/99/9999" value="{{ $formData['Eland']['dob'] ?? $userdata->dob }}" {{ !empty($userdata->dob) ? 'readonly' : '' }}>
                    </div>
                </div>
            </div>
            <div class="lengendwrap bg-pink mb-10"><legend class="text-bold text-white panel-title pt-5 pb-5 text-center h5">Contact info</legend></div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-addon"><span class="icon-phone2"></span></span>
                            <input type="text" name="Eland[phone]" class="form-control required" placeholder="(xxx) xxx-xxxx" data-mask="999-999-9999" value="{{ $formData['Eland']['phone'] ?? $userdata->contact_number }}" {{ !empty($userdata->contact_number) ? 'readonly' : '' }}>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-addon"><span class="icon-envelop3"></span></span>
                            <input type="email" name="Eland[email]" class="form-control required" placeholder="Email" value="{{ $formData['Eland']['email'] ?? $userdata->email }}" {{ !empty($userdata->email) ? 'readonly' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8"><div class="form-group"><label class="form-label">Address <span class="text-danger">*</span></label><input type="text" name="Eland[address]" class="form-control required" placeholder="Your address" value="{{ $formData['Eland']['address'] ?? $userdata->address }}"></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Apt/Unit</label><input type="text" name="Eland[houseno]" class="form-control" placeholder="Apt/unit #" value="{{ $formData['Eland']['houseno'] ?? '' }}"></div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label class="form-label">City <span class="text-danger">*</span></label><input type="text" name="Eland[city]" class="form-control required" placeholder="City" value="{{ $formData['Eland']['city'] ?? $userdata->city }}"></div></div>
                <div class="col-md-3"><div class="form-group"><label class="form-label">State <span class="text-danger">*</span></label><select name="Eland[state]" class="form-control"><option value="">Select State</option>@foreach($states as $k => $v)<option value="{{ $k }}" {{ ($formData['Eland']['state'] ?? $userdata->state) == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
                <div class="col-md-3"><div class="form-group"><label class="form-label">Zip Code <span class="text-danger">*</span></label><input type="text" name="Eland[zip]" class="form-control required" placeholder="Zip" data-mask="999999" value="{{ $formData['Eland']['zip'] ?? $userdata->zip }}" {{ !empty($userdata->zip) ? 'readonly' : '' }}></div></div>
            </div>
            <div class="lengendwrap bg-pink mb-10"><legend class="text-bold text-white panel-title pt-5 pb-5 text-left"><i class="icon-home2 pr-5 pl-5"></i>Residence</legend></div>
            <div class="row">
                <div class="col-md-4 col-sm-12"><div class="form-group"><label>Time at Current Address: <span class="text-danger">*</span></label><div class="row"><div class="col-md-6"><select name="Eland[years]" class="form-control required"><option value="">Years</option>@foreach($years as $k => $v)<option value="{{ $k }}" {{ ($formData['Eland']['years'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div><div class="col-md-6"><select name="Eland[months]" class="form-control required"><option value="">Months</option>@foreach($months as $k => $v)<option value="{{ $k }}" {{ ($formData['Eland']['months'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div></div></div>
                <div class="col-md-4 col-sm-12"><div class="form-group"><label>Do you Own or Rent?</label><select name="Eland[OwnOrRent]" class="form-control">@foreach($residenceType as $k => $v)<option value="{{ $k }}" {{ ($formData['Eland']['OwnOrRent'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
                <div class="col-md-4 col-sm-12"><div class="form-group"><label>Rent/Mortgage Payment</label><div class="input-group"><span class="input-group-addon"><span class="icon-coin-dollar"></span></span><input type="text" name="Eland[rent]" class="form-control" placeholder="Rent/Mortgage" value="{{ $formData['Eland']['rent'] ?? '' }}"></div></div></div>
            </div>
            <div class="row"><div class="col-md-12"><div id="terms" class="alert alert-secondary p-3 mt-3 disclosure"><p class="small mb-0">You understand that by clicking on the I AGREE button immediately following this notice, you are providing 'written instructions' to Abc Motors under the Fair Credit Reporting Act authorizing Abc Motors to obtain your personal consumer report from one or more credit reporting agencies. You authorize Abc Motors to obtain such information solely to conduct a prequalification for credit.</p><input type="hidden" name="Eland[disclosure_type]" value="13"></div></div></div>
            <div class="row"><div class="col-md-12"><div class="checkbox"><label><div class="checkr"><span><input type="checkbox" class="styled required" name="Eland[term]"></span></div>I have read the Privacy Policy, agree with the consent statement above, and authorize you to contact me via phone, text and/or email</label></div></div></div>
            <div class="row"><div class="col-md-12"><button type="submit" class="btn btn-primary bg-pink pull-right">I Agree <i class="icon-check position-right"></i></button></div></div>
        </fieldset>
    </form>
</div>
<script src="{{ asset('assets/js/plugins/forms/wizards/stepy.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/forms/selects/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
<script src="{{ asset('js/jquery.maskedinput.js') }}"></script>
<script src="{{ asset('eland/js/script.js') }}"></script>
@endsection
