@extends('layouts.driveitaway')
@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <center><h1>Car Dealers</h1></center>
                <p>Car Dealers- Mobility as a Service is exploding, are you read to join in the growth? Get in the Game of Shared Mobility (car sharing) or risk being displaced by those that do!</p>
                <p>DriveItAway enables any dealer to enter & own the Mobility as a Service business:</p>
                <ol>
                    <li style="list-style: inherit;">DriveItAway provides a turn-key platform—hardware/software, comprehensive insurance coverage and training—for NO UPFRONT COSTS.</li>
                    <li style="list-style: inherit;">You choose how and where you want to implement your car sharing program- we give you the tools and market insight, you set all of the terms.</li>
                    <li style="list-style: inherit;">Quickly increase sale and fixed operations revenue, and get experience in the emerging Mobility as a Service model.</li>
                </ol>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <center><a href="{{ url('/contactus') }}" title="More Info" class="btn btn-driveitaway">More Info <i class='icon-arrow-right14 position-right'></i></a></center>
            </div>
        </div>
    </div>
</div>
@endsection
