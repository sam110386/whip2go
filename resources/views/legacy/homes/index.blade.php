@extends('layouts.driveitaway')
@section('content')
<section class="home-banner">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2">
                <div class="banner-content">
                    <h1><span>Whip</span> Around Town Today</h1>
                    <p>Vehicle subscriptions through your local dealership. Avoid the hassles of owning a vehicle. Various subscription lengths available. Packages available for Ride Share drivers. Subscribe!</p>
                </div>
                <form class="">
                    <div class="col-xs-12 col-md-9 col-lg-9 bg-white">
                        <div class="form-group col-xs-12 col-sm-6 col-md-3 col-lg-3">
                            <label class="control-label">Location</label>
                            <input type="text" class="form-control" placeholder="Sanfrancisco, CA"/>
                        </div>
                        <div class="form-group col-xs-12 col-sm-6 col-md-3 col-lg-3">
                            <label class="control-label">Pick-up</label>
                            <input type="text" class="form-control" placeholder="28th Feb 2018"/>
                        </div>
                        <div class="form-group col-xs-12 col-sm-6 col-md-3 col-lg-3">
                            <label class="control-label">Return</label>
                            <input type="text" class="form-control" placeholder="31th Feb 2018"/>
                        </div>
                        <div class="form-group col-xs-12 col-sm-6 col-md-3 col-lg-3">
                            <label class="control-label">Car Types</label>
                            <select class="form-control selectpicker">
                                <option>Sedan</option>
                                <option>Hatchback</option>
                                <option>Compact</option>
                                <option>SUV</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 no-pad">
                        <button type="submit" class="btn btn-primary">Search &nbsp;&nbsp;<i class="fa fa-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<div id="vehicle-subscription">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                <div class="section-title">
                    <h2>What Is A <strong>Vehicle Subscription?</strong></h2>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="vehicle-subscription-box">
                        <span class="count">1</span>
                        <h3>Your vehicle for as long as you choose</h3>
                        <img src="{{ asset('img/vehicle-subscription-1.png') }}" alt="">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="vehicle-subscription-box">
                        <span class="count">2</span>
                        <h3>All normal vehicle costs included in 1 final price</h3>
                        <img src="{{ asset('img/vehicle-subscription-2.png') }}" alt="">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="vehicle-subscription-box">
                        <span class="count">3</span>
                        <h3>Easy to become a member</h3>
                        <img src="{{ asset('img/vehicle-subscription-3.png') }}" alt="">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                    <div class="vehicle-subscription-box">
                        <span class="count">4</span>
                        <h3>Bring back vehicle when your subscription is over and get another</h3>
                        <img src="{{ asset('img/vehicle-subscription-4.png') }}" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<div id="how-it-works">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                <div class="section-title">
                    <h2>How It <strong>Works</strong></h2>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                    <div class="how-it-works-list-item bg-white">
                        <img src="{{ asset('img/calendar.png') }}" alt="">
                        <h3>SELECT PICK UP<br/>& RETURN TIME</h3>
                        <p>Select location, time to pick up, return and car type</p>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                    <div class="how-it-works-list-item bg-white">
                        <img src="{{ asset('img/car.png') }}" alt="">
                        <h3>BROWSE ALL <br/>AVAILABLE VEHICLES</h3>
                        <p>Choose a vehicle that suits your style and needs</p>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                    <div class="how-it-works-list-item bg-white">
                        <img src="{{ asset('img/car-key.png') }}" alt="">
                        <h3>PICK A CAR<br/>AND ENJOY YOUR RIDE</h3>
                        <p>Pick up your vehicle and return when you no longer need it.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="download">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                <div class="section-title">
                    <h2>Download the <strong>WHIP APP</strong></h2>
                    <p>WHIP app available on iTune and Playstore</p>
                </div>
                <div class="download-link text-center col-xs-12">
                    <a href="https://play.google.com/store/apps/details?id=com.carshare" target="_blank">
                        <img class="img-responsive" src="{{ asset('img/google-store.png') }}" alt="">
                    </a>
                    <a href="https://itunes.apple.com/in/app/whip-car-sharing/id1393803514?mt=8" target="_blank">
                        <img class="img-responsive" src="{{ asset('img/apple-store.png') }}" alt="">
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="get-start">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad ">
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <div class="get-start-content">
                        <p><small>For Drivers</small></p>
                        <h3>Drivers</h3>
                        <p>Get affordable rental transportation while earning money to buy your vehicle</p>
                        <a class="btn btn-primary btn-lg" role="button" href="{{ url('/logins/index') }}">Get Started</a>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <div class="get-start-content">
                        <p><small>For Dealers</small></p>
                        <h3>Car Dealers</h3>
                        <p>Mobility as a service is fast approaching, join us and get in on the growth</p>
                        <a class="btn btn-primary btn-lg" role="button" href="{{ url('/logins/index') }}">Get Started</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
