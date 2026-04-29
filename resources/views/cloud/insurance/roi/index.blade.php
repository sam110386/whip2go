@extends('layouts.main')

@section('title', 'ROI Insurance Quote')

@push('scripts')
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#frmadmin").validate();
    });
</script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">ROI </span>- Insurance Quote</h4>
        </div>
    </div>
</div>

<div class="row">
    @if(session('flash_message'))
        <div class="alert alert-success">{{ session('flash_message') }}</div>
    @endif
    @if(session('flash_error'))
        <div class="alert alert-danger">{{ session('flash_error') }}</div>
    @endif
</div>

<div class="panel">
    <div class="panel-body">
        <form action="https://roi-insurance.com/driveitaway" method="GET" name="frmadmin" id="frmadmin" class="form-horizontal">
        
            <div class="form-group">
                <label class="col-lg-12 control-label">First Name :<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="first" id="first" maxlength="50" class="form-control required" value="{{ $booking['User']['first_name'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Last Name :<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="last" id="last" maxlength="50" class="form-control required" value="{{ $booking['User']['last_name'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Phone :<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="phone" id="phone" maxlength="16" class="form-control required" value="{{ $booking['User']['contact_number'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Email :<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="email" name="email" id="email" maxlength="35" class="form-control required" value="{{ $booking['User']['email'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">DOB :<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="dob" id="dob" maxlength="10" class="form-control required" value="{{ $booking['User']['dob'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">License #:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="license" id="license" maxlength="30" class="form-control required" value="{{ $booking['User']['licence_number_decrypted'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">License State:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="licensestate" id="licensestate" maxlength="30" class="form-control required" value="{{ $booking['User']['licence_state'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">VIN #:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="vin1" id="vin1" maxlength="30" class="form-control required" value="{{ $booking['Vehicle']['vin_no'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Vehicle Year:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="year1" id="year1" maxlength="30" class="form-control required" value="{{ $booking['Vehicle']['year'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Vehicle Make:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="make1" id="make1" maxlength="30" class="form-control required" value="{{ $booking['Vehicle']['make'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Vehicle Model:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="model1" id="model1" maxlength="30" class="form-control required" value="{{ $booking['Vehicle']['model'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Vehicle Deductible:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="deductible1" id="deductible1" maxlength="30" class="form-control required" value="$1000" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Address:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="street" id="street" maxlength="30" class="form-control required" value="{{ $booking['User']['address'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Address 2:</label>
                <div class="col-lg-12">
                    <input type="text" name="street2" id="street2" maxlength="30" class="form-control" value="" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">City:<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="city" id="city" maxlength="30" class="form-control required" value="{{ $booking['User']['city'] }}" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">State :<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <select name="state" id="state" class="form-control required">
                        @foreach($states as $value)
                            <option value="{{ $value }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-12 control-label">Postal Code :<span class="text-danger">*</span></label>
                <div class="col-lg-12">
                    <input type="text" name="zip" id="zip" maxlength="10" class="form-control required" value="{{ $booking['User']['zip'] }}" />
                </div>
            </div>
            <input type="hidden" name="country" value="United State" />
            <input type="hidden" name="start" value="{{ date('m/d/Y') }}" />
            
            <div class="form-group">
                <div class="col-lg-6">
                    <button type="submit" class="btn">Get Quote</button>
                </div>
           </div>
        </div>
        </form>
   </div>
</div>
@endsection
