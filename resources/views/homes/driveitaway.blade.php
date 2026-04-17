@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'DriveItAway Today')

@section('content')
    <style type="text/css">
        a.btn-rounded {
            font-size: 19px;
            padding: 9px 35px;
            color: #999;
            background: #fff;
            border: 1px solid #999;
        }

        .letsbegan li {
            list-style: unset;
        }

        .letsbegan {
            text-align: left;
            font-size: 19px;
        }

        .letsbegantitle {
            margin-bottom: 20px;
        }

        a.btn-rounded {
            background: #EA0B8C;
            color: #fff;
            font-weight: bold;
        }

        @media(max-width: 768px) {
            a.btn-rounded {
                font-weight: unset;
            }
        }
    </style>
    <section class="home-banner">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2">
                    <div class="banner-content">
                        <h1><span>DriveItAway</span> Today</h1>
                        <p>Need a car to drive for Lyft or Uber?<br />
                            Renting is expensive and endless. Buying may be out of your budget. Temp your vehicle as a path
                            to ownership.<br />
                            No Usage fees. No background check fees. No fees. Period.</p>
                    </div>

                    <div
                        class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                        <div class="col-md-6">
                            <a href="https://itunes.apple.com/in/app/whip-car-sharing/id1393803514?mt=8" target="_blank"
                                class=" btn btn-default btn-rounded pull-right">
                                App Store</a>
                        </div>
                        <div class="col-md-6">
                            <a href="https://play.google.com/store/apps/details?id=com.carshare" target="_blank"
                                class=" btn btn-default btn-rounded pull-left">
                                Play Store
                            </a>
                        </div>

                    </div>
                    <div
                        class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                        <span class="downloadapp-text">Download Our App</span></div>

                </div>
            </div>
        </div>
    </section>
    <div id="vehicle-subscription">
        <div class="container">
            <div class="row">
                <div
                    class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                    <div class="section-title">
                        <h2>Turning A Temporary Vehicle Into <strong>Your Vehicle</strong></h2>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                        <div class="vehicle-subscription-box">
                            <span class="count">1</span>
                            <h3>Your vehicle for as long as you choose</h3>
                            <img src="{{ legacy_asset('img/driveitaway_vehicle-subscription-1.png') }}" alt="">
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                        <div class="vehicle-subscription-box">
                            <span class="count">2</span>
                            <h3>All normal vehicle costs included in 1 final price</h3>
                            <img src="{{ legacy_asset('img/driveitaway_vehicle-subscription-2.png') }}" alt="">
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                        <div class="vehicle-subscription-box">
                            <span class="count">3</span>
                            <h3>Easy to become a member</h3>
                            <img src="{{ legacy_asset('img/driveitaway_vehicle-subscription-3.png') }}" alt="">
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                        <div class="vehicle-subscription-box">
                            <span class="count">4</span>
                            <h3>Transition from the temporary vehicle package to owning the vehicle</h3>
                            <img src="{{ legacy_asset('img/driveitaway_vehicle-subscription-4.png') }}" alt=""
                                style="width: 150px;margin-left: -8px;">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div id="how-it-works">
        <div class="container">
            <div class="row">
                <div
                    class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                    <div class="section-title">
                        <h2>How It <strong>Works</strong></h2>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <div class="how-it-works-list-item bg-white">
                            <img src="{{ legacy_asset('img/driveitaway_calendar.png') }}" alt="">
                            <h3>SELECT PICK UP<br />& RETURN TIME</h3>
                            <p>Select location, time to pick up, return and car type</p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <div class="how-it-works-list-item bg-white">
                            <img src="{{ legacy_asset('img/driveitaway_car.png') }}" alt="">
                            <h3>BROWSE ALL <br />AVAILABLE VEHICLES</h3>
                            <p>Choose a temporary vehicle that you may want to one day own</p>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
                        <div class="how-it-works-list-item bg-white">
                            <img src="{{ legacy_asset('img/driveitaway_car-key.png') }}" alt="">
                            <h3>PICK A CAR<br />AND ENJOY YOUR RIDE</h3>
                            <p>Pick up your temp vehicle and convert to an owning package when you're ready</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="download">
        <div class="container">
            <div class="row">
                <div
                    class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                    <div class="section-title letsbegantitle">
                        <h2><strong>Let's begin this relationship with your new car the right way...</strong></h2>
                    </div>
                    <div class="letsbegan">
                        <div class="col-md-12">
                            <ol>
                                <li> You'll be spending a lot of time together! Find the exact car you want before
                                    committing to leasing or buying. Don't just get in any rental. You're better than that!
                                </li>
                                <li> Invest in your car! This may be the one. Temp payments may be used towards purchasing
                                    the car if you decide to buy it.</li>
                                <li> After days and months together, we've gotten to know each other. Build your credit
                                    through the temporary vehicle with our available credit repair program.</li>
                                <li> Exclusive to DriveItAway, we have incentives to help you pay for your vehicle up to
                                    $1000. And, we don't charge the 10% driver fees nor background check fees like some
                                    other folks.</li>
                            </ol>
                        </div>
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
                            <p>Get affordable temp transportation while earning money to buy your vehicle</p>
                            <a class="btn btn-primary btn-lg" role="button" href="/logins/index"
                                style="border:medium solid #fff;">Get Started</a>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                        <div class="get-start-content">
                            <p><small>For Dealers</small></p>
                            <h3>Car Dealers</h3>
                            <p>Mobility as a service is fast approaching, join us and get in on the growth</p>
                            <a class="btn btn-primary btn-lg" role="button" href="/logins/index"
                                style="border:medium solid #fff;">Get Started</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection