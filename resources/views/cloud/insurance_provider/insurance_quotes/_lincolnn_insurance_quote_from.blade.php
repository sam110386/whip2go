@extends('layouts.main')
@section('content')
<script type="text/javascript">
    $(document).ready(function() {
        $("#DriverFinancedInsuranceLincolnreviewForm").validate({
            ignore: [],
        });
    });
</script>
<div class="row ">
    @if(session('flash_message'))
        <div class="alert alert-success">{{ session('flash_message') }}</div>
    @endif
    @if(session('flash_error'))
        <div class="alert alert-danger">{{ session('flash_error') }}</div>
    @endif
</div>
<div class="panel">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-12">
                <img src="{{ asset('img/insurance_providers/lincoln-insurance-logo-blue.webp') }}" class="img-responsive mb-3">
            </div>
        </div>
        @if (!empty($orderandusers))
            <div class="">
                <div class="panel-heading">
                    <span>Please fill the following Insurance Quote from</span>
                </div>

                <form action="{{ url('/insuprovider/quotes/lincolnquotesave/' . $orderandusers) }}" method="POST" name="frmadmin" class="form-horizontal stepy-validation">
                    @csrf

                <div class="panel-group panel-group-control panel-group-control-right content-group-lg" id="accordion-control-right">
                    <div class="panel">
                        <div class="panel-heading bg-primary">
                            <h6 class="panel-title">
                                <a data-toggle="collapse" data-parent="#accordion-control-right" href="#accordion-control-personal-info">Personal Info</a>
                            </h6>
                        </div>
                        <div id="accordion-control-personal-info" class="panel-collapse collapse in">
                            <div class="panel-body">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Date Policies Should Start:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[start_date]" class="form-control date" readonly value="{{ date('m/d/Y', strtotime($booking['VehicleReservation']['start_datetime'])) }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">First Name:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[first_name]" class="form-control" maxlength="30" readonly value="{{ $booking['Driver']['first_name'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Last Name:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[last_name]" class="form-control" maxlength="30" readonly value="{{ $booking['Driver']['last_name'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Email:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="email" name="quote[email]" class="form-control" readonly value="{{ $booking['Driver']['email'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Phone:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[phone]" class="form-control" readonly value="{{ $booking['Driver']['contact_number'] }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel ">
                        <div class="panel-heading bg-primary">
                            <h6 class="panel-title">
                                <a class="collapsed" data-toggle="collapse" data-parent="#accordion-control-right" href="#accordion-control-address">Address</a>
                            </h6>
                        </div>
                        <div id="accordion-control-address" class="panel-collapse collapse">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Street:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[address]" class="form-control" value="{{ $booking['Driver']['address'] }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">City:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[city]" class="form-control" value="{{ $booking['Driver']['city'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">State:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[state]" class="form-control" value="{{ $driverStateName ?? $booking['Driver']['state'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Postal:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[zip]" class="form-control" value="{{ $booking['Driver']['zip'] }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel ">
                        <div class="panel-heading bg-primary">
                            <h6 class="panel-title">
                                <a class="collapsed" data-toggle="collapse" data-parent="#accordion-control-right" href="#accordion-control-applicant-detail">Applicant Details</a>
                            </h6>
                        </div>
                        <div id="accordion-control-applicant-detail" class="panel-collapse collapse" aria-expanded="true">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Date of Birth:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[dob]" class="form-control date" readonly value="{{ date('m/d/Y', strtotime($booking['UserLicenseDetail']['dateOfBirth'])) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Gender:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <select name="quote[gender]" class="form-control">
                                                    <option value="m" {{ ($booking['UserLicenseDetail']['sex'] ?? '') == 'm' ? 'selected' : '' }}>Male</option>
                                                    <option value="f" {{ ($booking['UserLicenseDetail']['sex'] ?? '') == 'f' ? 'selected' : '' }}>Female</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Driver License Number:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[license_number]" class="form-control" readonly value="{{ $booking['UserLicenseDetail']['documentNumber'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Driver License State:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[license_state]" class="form-control" readonly value="{{ $licenseStateName ?? $booking['UserLicenseDetail']['addressState'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Marital Status:</label>
                                            <div class="col-lg-8">
                                                <select name="quote[marital_status]" class="form-control">
                                                    @foreach($marital_status as $key => $val)
                                                        <option value="{{ $key }}">{{ $val }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Ocupation:</label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[occupation]" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Education:</label>
                                            <div class="col-lg-8">
                                                <select name="quote[education]" class="form-control">
                                                    @foreach($education as $key => $val)
                                                        <option value="{{ $key }}">{{ $val }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel ">
                        <div class="panel-heading bg-primary">
                            <h6 class="panel-title">
                                <a class="collapsed" data-toggle="collapse" data-parent="#accordion-control-right" href="#accordion-control-vehicle-info">Vehicle Info</a>
                            </h6>
                        </div>
                        <div id="accordion-control-vehicle-info" class="panel-collapse collapse">
                            <div class="panel-body">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">VIN:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[vin]" class="form-control" readonly value="{{ $booking['Vehicle']['vin_no'] }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Year:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[year]" class="form-control" readonly value="{{ $booking['Vehicle']['year'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Make:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[make]" class="form-control" readonly value="{{ $booking['Vehicle']['make'] }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Model:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[model]" class="form-control" readonly value="{{ $booking['Vehicle']['model'] }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel ">
                        <div class="panel-heading bg-primary">
                            <h6 class="panel-title">
                                <a class="collapsed" data-toggle="collapse" data-parent="#accordion-control-right" href="#accordion-control-wrapping-up">Wrapping Up</a>
                            </h6>
                        </div>
                        <div id="accordion-control-wrapping-up" class="panel-collapse collapse">
                            <div class="panel-body">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="display-block text-semibold col-lg-12">Any Tickets or Claims in the Past Three (3) Years?<span class="text-danger">*</span></label>
                                            <div class="col-xs-6">
                                                <label class="radio-inline">
                                                    <input type="radio" name="quote[past_cliam]" class="styled required" checked="checked" value="Yes">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label class="radio-inline">
                                                    <input type="radio" name="quote[past_cliam]" class="styled required" value="No">
                                                    No
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="display-block text-semibold col-lg-12">Do you need any SR-22 filings?<span class="text-danger">*</span></label>
                                            <div class="col-xs-6">
                                                <label class="radio-inline">
                                                    <input type="radio" name="quote[need_sr_filling]" class="styled required" value="Yes" />
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="col-xs-6">
                                                <label class="radio-inline">
                                                    <input type="radio" name="quote[need_sr_filling]" class="styled required" value="No" checked="checked" />
                                                    No
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Current Auto Liability Limit:</label>
                                            <div class="col-lg-8">
                                                <select name="quote[current_auto_libility]" class="form-control">
                                                    @foreach($libilities as $key => $val)
                                                        <option value="{{ $key }}">{{ $val }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Desired Auto Liability Limit:</label>
                                            <div class="col-lg-8">
                                                <select name="quote[desired_auto_libility]" class="form-control">
                                                    @foreach($libilities as $key => $val)
                                                        <option value="{{ $key }}" {{ in_array($key, ['$25k/$50K/$25K', '$50k/$100K/$25K']) ? 'disabled' : '' }}>{{ $val }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Current Auto Insurance Company If Any:</label>
                                            <div class="col-lg-8">
                                                <input type="text" name="quote[current_insurance_company]" class="form-control" maxlength="50">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label class="col-lg-3 control-label text-default">Consent:<span class="text-danger">*</span></label>
                                            <div class="col-lg-8">
                                                <p class="text-danger"><em>Like most insurance agencies, we use information from you and other sources, such as your driving and claims histories, insurance score, and other factors to calculate an accurate rate for your insurance. New or updated information may be used to calculate your renewal premium.</em></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">

                                            <label class="checkbox-inline checkbox-right">
                                                <input type="checkbox" class="styled required" name="quote[consent]" value="I Agree" checked="checked">
                                                I Agree <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /accordion with right control button -->
                <button type="submit" class="btn btn-primary w-100">Submit <i class="icon-arrow-right8 position-right"></i></button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
