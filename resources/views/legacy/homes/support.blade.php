@extends('layouts.driveitaway')
@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <center><h1>Support</h1></center>
                <p>Please use the contact form below to reach support.</p>
                @if(session('success'))<div>{{ session('success') }}</div>@endif
                @if(session('error'))<div>{{ session('error') }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
