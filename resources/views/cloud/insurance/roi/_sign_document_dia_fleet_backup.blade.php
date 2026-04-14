@extends('layouts.main')

@section('content')
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
        <div class="form-group">
            <legend>Good news!!</legend>
            <div class="col-lg-12 col-sm-12">
                <p class="">Good news! The insurance quote has been reviewed and it appears affordable.</p>
                <p class="">We need you to get insurance in place now. Please go forward with purchasing insurance from one of the approved providers. Please go ahead and purchase the insurance policy. Here are some points:</p>
                <ul class="list-circle">
                    <li style="list-style-type: disc;">This program is considered a lease.</li>
                    <li style="list-style-type: disc;">The vehicle is registered to the company during the program. You don't need to list the registered owner.</li>
                    <li style="list-style-type: disc;">The effective date can be the day you plan to pick up the car.</li>
                    <li style="list-style-type: disc;">The VIN of the vehicle is listed at the top in Pending Bookings.</li>
                    <li style="list-style-type: disc;">When they ask if you'd like them to notify the leasing company, you can choose No. We'll log in after to grab the declaration page and insurance car.</li>
                    <li style="list-style-type: disc;">The insurance liability limits required are 50/100/50 and the comprehensive and collision coverage deductibles can be no greater than $500.</li>
                    <li style="list-style-type: disc;"><strong>DIA Leasing LLC</strong> needs to be listed as additional insured and loss payee.</li>
                </ul>
                <p class="">Company Address:</p>
                <address>DIA Leasing LLC <br/>P.O. Box 421669 <br/>Atlanta, GA 30342</address>
                <p class="">Please sign the finalized documents for the program.</p>
            </div>
            <div class="col-lg-12 col-sm-12 text-center">
                <a href="{{ $url }}" class="btn btn-primary w-100">Proceed To Sign <i class="icon-arrow-right8 position-right"></i></a>
            </div>
        </div>
    </div>
</div>
@endsection
