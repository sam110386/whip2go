@extends('layouts.without_header_footer', ['title' => 'Credit Application - Step 2'])
@section('content')
<style>.stepy-header li.stepy-active div, .stepy-header li div { border-color: #E91E63; background-color: #fff; color: #E91E63; }</style>
<div class="panel panel-white">
    <div class="panel-heading text-center">
        <h6 class="panel-title">DriveItAway will run a soft credit check so that lenders can periodically send offers if you are interested in buying the vehicle.</h6>
    </div>
    <ul id="stepyvalidation-header" class="stepy-header">
        <li id="stepyvalidation-head-0"><div>1</div><span>Personal data</span></li>
        <li id="stepyvalidation-head-1" class="stepy-active" style="cursor: default;"><div>2</div><span>Provide more info to get more options</span></li>
    </ul>
    <form action="{{ url('eland/elandmob/saveFinalStep/' . base64_encode($userdata->id)) }}" method="POST" id="finalstep" class="stepy-validation">
        @csrf
        <input type="hidden" name="ElandResidence[userid]" value="{{ base64_encode($userdata->id) }}">
        <fieldset title="2" class="stepy-step">
            <legend class="text-semibold">Provide more info to get more options</legend>
            <div class="lengendwrap bg-pink mb-10"><legend class="text-bold text-white panel-title pt-5 pb-5 text-left"><i class="icon-user pr-5 pl-5"></i>Employment</legend></div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label class="form-label">Type of Employment <span class="text-danger">*</span></label><select name="ElandResidence[emptype]" class="form-control required"><option value="">Employment Type</option>@foreach($emptype as $k => $v)<option value="{{ $k }}" {{ ($formData['ElandResidence']['emptype'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Employer Name <span class="text-danger">*</span></label><input type="text" name="ElandResidence[employer]" class="form-control required" placeholder="Employer Name" value="{{ $formData['ElandResidence']['employer'] ?? '' }}"></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Occupation or Rank <span class="text-danger">*</span></label><input type="text" name="ElandResidence[occupation]" class="form-control required" placeholder="Occupation or Rank" value="{{ $formData['ElandResidence']['occupation'] ?? '' }}"></div></div>
            </div>
            <div class="row">
                <div class="col-md-3"><div class="form-group"><label class="form-label">Work Phone <span class="text-danger">*</span></label><input type="text" name="ElandResidence[workphone]" class="form-control required" placeholder="(xxx) xxx-xxxx" data-mask="999-999-9999" value="{{ $formData['ElandResidence']['workphone'] ?? '' }}"></div></div>
                <div class="col-md-2"><div class="form-group"><label class="form-label">Extension</label><input type="text" name="ElandResidence[workphone_ext]" class="form-control" placeholder="xxx" data-mask="999" value="{{ $formData['ElandResidence']['workphone_ext'] ?? '' }}"></div></div>
                <div class="col-md-7"><div class="form-group"><label>Time at Employment</label><div class="row"><div class="col-md-6"><select name="ElandResidence[emp_years]" class="form-control"><option value="">Years</option>@foreach($years as $k => $v)<option value="{{ $k }}" {{ ($formData['ElandResidence']['emp_years'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div><div class="col-md-6"><select name="ElandResidence[emp_months]" class="form-control"><option value="">Months</option>@foreach($months as $k => $v)<option value="{{ $k }}" {{ ($formData['ElandResidence']['emp_months'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div></div></div></div>
            </div>
            <div class="lengendwrap bg-pink mb-10"><legend class="text-bold text-white panel-title pt-5 pb-5 text-left"><i class="icon-cash pr-5 pl-5"></i>Income</legend></div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label class="form-label">Gross Monthly <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-addon"><span class="icon-coin-dollar"></span></span><input type="text" name="ElandResidence[gross_income]" class="form-control required" value="{{ $formData['ElandResidence']['gross_income'] ?? '' }}"></div></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Other Monthly</label><div class="input-group"><span class="input-group-addon"><span class="icon-coin-dollar"></span></span><input type="text" name="ElandResidence[other_income]" class="form-control" value="{{ $formData['ElandResidence']['other_income'] ?? '' }}"></div></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Description</label><input type="text" name="ElandResidence[other_income_des]" class="form-control" placeholder="Description" value="{{ $formData['ElandResidence']['other_income_des'] ?? '' }}"></div></div>
            </div>
            <div class="lengendwrap bg-pink mb-10"><legend class="text-bold text-white panel-title pt-5 pb-5 text-left"><i class="icon-users pr-5 pl-5"></i>Co-Buyer Selection</legend></div>
            <div class="row"><div class="col-md-12"><div class="checkbox"><label><div class="checkr"><span><input type="checkbox" class="styled" name="ElandResidence[hascobuyer]"></span></div>Is there a Co-buyer?</label></div></div></div>
            <div class="row"><div class="col-md-12 text-right"><button type="button" class="btn btn-primary bg-pink stepy-finish">Submit <i class="icon-check position-right"></i></button></div></div>
        </fieldset>
    </form>
</div>
<script src="{{ asset('assets/js/plugins/forms/wizards/stepy.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/forms/selects/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
<script src="{{ asset('js/jquery.maskedinput.js') }}"></script>
<script src="{{ asset('eland/js/script.js') }}"></script>
@endsection
