@extends('layouts.main')

@section('content')
<script type="text/javascript">
    $(document).ready(function() {
        $("#DriverFinancedCreditCardForm").validate();
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
    <form action="{{ url('insurance/roi/diafinancedsaveinsuranceaccount/' . $orderandusers) }}" method="POST" id="DriverFinancedCreditCardForm" class="form-horizontal">
    @csrf
    <div class="panel-body">
        <legend class="text-size-large text-bold">Insurance Provider Details :</legend>
        <p class="text-warning"><em>Please enter your username and password to your insurance policy account so that we can connect to the carrier.</em></p>
        <div class="form-group">
            <div class="col-lg-12">
                <label class="">Username:</label>
                <input type="text" name="DriverFinancedCreditCard[username]" id="DriverFinancedCreditCardUsername" maxlength="50" class="required form-control" value="" />
            </div>
        </div>
        <div class="form-group">
            <div class="col-lg-12">
                <label class="">Password:</label>
                <input type="text" name="DriverFinancedCreditCard[password]" id="DriverFinancedCreditCardPassword" maxlength="50" class="required form-control" value="" />
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-6">
                <button type="submit" class="btn btn-primary pl-3 pr-3 w-100">Save <i class="icon-arrow-right8 position-right"></i></button>
            </div>
        </div>
    </div>
    </form>
</div>
@endsection
