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
            <legend>Stay tuned!!</legend>
            <div class="col-lg-12">
                <img src="{{ asset('img/DriveitawayBluelogo.png') }}" class="img-responsive mb-3" />
            </div>
            <div class="col-lg-12 col-sm-12">
                <p class="text-large">Driveitaway is in the process of issuing your virtual debit card to use for purchasing the insurance policy. Please give us a moment. We'll be in touch shortly.</p>
            </div>
        </div>
    </div>
</div>
@endsection
