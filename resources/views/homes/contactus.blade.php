@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Contact Us')

@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <center><h1>Fill below form if you want to delete your account</h1></center>

                <div class="panel">
                    <div class="panel-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif
                        <div class="row">
                            <div class="col-md-10 col-xs-12">

                                <form method="post" action="/contactus" class="form-horizontal" name="frmadmin" id="CustomerContact" onsubmit="return isvalid()">
                                    @csrf
                                    <fieldset>
                                        <legend class="text-semibold"><i class="icon-reading position-left"></i> General details</legend>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">First Name:</label>
                                                <div class="col-lg-9">
                                                    <input type="text" name="Contact[first_name]" value="{{ old('Contact.first_name') }}" class="form-control required">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">Last Name:</label>
                                                <div class="col-lg-9">
                                                    <input type="text" name="Contact[last_name]" value="{{ old('Contact.last_name') }}" class="form-control required">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">Email:</label>
                                                <div class="col-lg-9">
                                                    <input type="email" name="Contact[email]" value="{{ old('Contact.email') }}" class="form-control email required">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">Phone #:</label>
                                                <div class="col-lg-9">
                                                    <input type="text" name="Contact[phone]" value="{{ old('Contact.phone') }}" class="form-control phone required">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">I am a:</label>
                                                <div class="col-lg-9">
                                                    <select name="Contact[usertype]" class="form-control required">
                                                        @php $ut = old('Contact.usertype', 'Driver'); @endphp
                                                        <option value="Driver" @selected($ut === 'Driver')>Driver</option>
                                                        <option value="Dealer" @selected($ut === 'Dealer')>Dealer</option>
                                                        <option value="Other" @selected($ut === 'Other')>Other</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label class="col-lg-3 control-label">Your message:</label>
                                                <div class="col-lg-9">
                                                    <textarea name="Contact[comment]" class="form-control" rows="5" cols="5" placeholder="Enter your message here">{{ old('Contact.comment') }}</textarea>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <button type="submit" class="btn btn-primary">Send <i class="icon-arrow-right14 position-right"></i></button>
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>
                            </div>
                            <div class="col-md-4 col-xs-12">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
jQuery(document).ready(function () {
    jQuery("#CustomerContact").validate();
});
function isvalid(){
    if(jQuery("#CustomerContact").valid()){
        return true;
    }else{
       return false;
    }
    return false;
}
</script>
@endpush
