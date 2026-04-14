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
<div class="row">
    <div class="col-lg-12">
        <img src="{{ asset('img/insurance_providers/lincoln-insurance-logo-blue.webp') }}" class="img-responsive mb-3">
    </div>
</div>
<div class="">
    <div class="insuranceprovider-wrap">
        <div class="d-grid">
            <!-- block start here -->
            <div class="w-100 reviewwrap">
                <div class="panel panel-flat">
                    <div class="panel-body">
                        <p>Driveitaway Partners is willing to pay for your insurance policy in full, in the form of a short term loan. You will make payments back on this loan through the Driveitaway app alongside and before your regular prepaid usage payments for the vehicle. This program pays for the policy in full to ensure that it will never lapse or be cancelled for lack of payment. In the event that the policy is voluntarily cancelled (e.g. if the vehicle is returned), the unused amount will be refunded by the insurance provider and reduce and void the loan by that amount. This is a required program by Driveitaway. Please read the documents below to continue.</p>
                        <p><a href="{{ url('/insuprovider/docusign/signDocument/' . $insuranceQuoteId . '/' . $orderandusers) }}" class="btn btn-primary">Click Here To Sign Documents</a></p>
                    </div>
                </div>
            </div>
            <!-- block end here -->
        </div>
    </div>
</div>
@endsection
