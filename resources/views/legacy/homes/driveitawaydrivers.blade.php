@extends('layouts.driveitaway')
@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <center><h1>Drivers</h1></center>
                <p>Need a Vehicle to Start Driving for Lyft or Uber? Don’t get caught in the high cost/short term rental or lease trap! DriveItAway enables ownership for all ride share drivers. It’s as easy as 1, 2, 3!</p>
                <ol>
                    <li style="list-style: inherit;">Come to a DriveItAway powered "Lyft Your Down Payment" or "Drive for Your Down Payment" authorized car dealership and see the DriveItAway specialist. Choose the vehicle you want, and find out what you’ll need to buy or lease it long term.</li>
                    <li style="list-style: inherit;">If the deal can’t be done that day, pick up a DriveItAway vehicle to drive for Lyft (or Uber) and "Lyft Your Down Payment" (discounted rental vehicles, with all insurance included, for a month or however long it takes).</li>
                    <li style="list-style: inherit;">Come back when you raise enough money driving, and buy the vehicle you choose! New drivers will receive a "kick start" bonus of up to $500 towards your down payment.</li>
                </ol>
                <p>Want more info, phone {{ config('app.support_phone', '8564953138') }} or email info@driveitaway.com</p>
            </div>
            <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                <div class="col-md-12 downloadapp-text">Download Our App</div>
                <div class="col-md-6">
                    <a href="https://itunes.apple.com/in/app/whip-car-sharing/id1393803514?mt=8" target="_blank" class=" btn btn-driveitaway btn-rounded pull-right">App Store</a>
                </div>
                <div class="col-md-6">
                    <a href="https://play.google.com/store/apps/details?id=com.carshare" target="_blank" class=" btn btn-driveitaway btn-rounded pull-left">Play Store</a>
                </div>
            </div>
        </div>
        <div class="row">&nbsp;</div>
        <div class="row">
            <div class="col-xs-6">
                <a class="btn btn-driveitaway btn-lg pull-left" role="button" href="{{ url('/logins/index') }}">Get Started</a>
            </div>
            <div class="col-xs-6">
                <a href="{{ url('/contactus') }}" title="More Info" class="btn btn-driveitaway pull-right">More Info <i class='icon-arrow-right14 position-right'></i></a>
            </div>
        </div>
    </div>
</div>
@endsection
