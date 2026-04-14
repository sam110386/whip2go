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
                <p class="">We need you to get insurance in place now. The way our insurance process works is we have an "enforced savings plan" where we require you to prepay weekly (or biweekly) insurance installments through our app that we will then put onto a virtual debit card for you.</p>
                <p class="">As an example, if your monthly insurance is $400/month, you would pay $100/week for insurance, alongside your vehicle usage payments. We would take the $100 and transfer it onto the virtual debit card for you. By doing so, when the insurance carrier goes to charge $400, it is already available and you won't need to lay out a large lump sum.</p>
                <p class="">This card is technically laying out the first payment for insurance. We will treat this as a short term loan to you. The card will also be kept with the carrier on auto-renew to pay the monthly insurance installments going forward.</p>
                <p class="">This pay card is in your name and will ensure that insurance payments will always go through for insurance so that the insurance never lapses for lack of payment.</p>
                <p class="">As mentioned, you'll make prorated (weekly, biweekly, or monthly) payments for insurance to our company alongside the usage payments for the car, and we'll transfer those insurance payments onto the virtual card so that there are funds set aside and available to pay the insurance carrier when they bill monthly.</p>
                <p class="">We require our company to be Power of Attorney on the insurance policy so we can always make sure the insurance policy stays in place and to help manage any insurance related issues.</p>
                <p class="">Please sign the insurance related documents.</p>
            </div>
            <div class="col-lg-12 col-sm-12 text-center">
                <a href="{{ $url }}" class="btn btn-primary w-100">Proceed To Sign <i class="icon-arrow-right8 position-right"></i></a>
            </div>
            <div class="col-lg-12 col-sm-12 text-center">
                <p class="text-danger"><em>Once this is done, we will issue the virtual card that you can use to purchase the policy. Please let us know if you have any questions.</em></p>
            </div>
        </div>
    </div>
</div>
@endsection
