@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Dealers')

@section('content')
@php $sp = config('legacy.support_phone'); $spTel = preg_replace('/\D+/', '', (string) $sp); @endphp
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <center><h1>Car Dealers</h1></center>
                <p>Car Dealers- Mobility as a Service is exploding, are you read to join in the growth? Get in the Game of Shared Mobility (car sharing) or risk being displaced by those that do! </p>
                <p>DriveItAway enables any dealer to enter & own the Mobility as a Service business:</p>
                <ol>
                    <li style="list-style: inherit;">DriveItAway provides a turn-key platform—hardware/software, comprehensive insurance coverage and training—for NO UPFRONT COSTS.</li>
                    <li style="list-style: inherit;">You choose how and where you want to implement your car sharing program- we give you the tools and market insight, you set all of the terms. </li>
                    <li style="list-style: inherit;">Quickly increase sale and fixed operations revenue, and get experience in the emerging Mobility as a Service model.</li>
                </ol>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <center><a href="/contactus" title="More Info" class="btn btn-driveitaway">More Info <i class="icon-arrow-right14 position-right"></i></a></center>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <center><h2><i>Frequently Asked Questions</i></h2></center>
                <div class="panel-collapse collapse in" id="collapseOne">
                    <div class="faqlist">
                        <ul>
                            <li>
                                <h4><i class="questionicon">How do I get into the DriveItAway turn-key Mobility as a Service/Car Sharing Program?</i></h4>
                                <p><i class="questionicon">Give us a call at <a href="tel:{{ $spTel }}">{{ $sp }}</a> , we are dedicated to making it easy and cost effective for any dealer to get into the Mobility as a Service business. Each DriveItAway affiliated dealer can design their own car sharing program, focused on both satisfying the needs of current Ride Sharing (Lyft/Uber) drivers, and your retail prospects who want a subscription service—why let them migrate to 3rd party car sharing providers? We give you a fully automated process, in a turn-key, comprehensive, solutions driven program (including a mobile software platform, insurance, and training) to get your dealership up and running in this emerging, rapidly expanding new area of the transportation business.</i></p>
                            </li>
                            <li>
                                <h4><i class="questionicon">What does it cost?</i></h4>
                                <p><i class="questionicon">We are dedicated to making our DriveItAway, turn-key Car Sharing program affordable to all dealerships, franchise and independent, large and small. There are literally no start-up fees, and no minimum usage requirements of any kind. We only charge a small revenue share from your profits, so give us a call and we will give you the details: 856 495 3138. Your only fixed cost is the gps module you need to install on each car sharing vehicle, to allow the program to be fully “self-service” for the customer (including vehicle tracking, condition, fuel level, and even keyless entry).</i></p>
                            </li>
                            <li>
                                <h4><i class="questionicon">Are there any start-up fees?</i></h4>
                                <p><i class="questionicon">None. We are dedicated to enabling every dealer to get into the game of Mobility as a Service, so you may offer subscription vehicle services or “cents per mile” models right along with traditional dealership activities. We truly believe that “MaaS” or whatever Silicon Valley wants to call it, is best suited to be conducted inside car dealerships, not by third parties—the place transportation needs have been satisfied for the last 100 years.</i></p>
                            </li>
                            <li>
                                <h4><i class="questionicon">Are there any volume obligations?</i></h4>
                                <p><i class="questionicon">None – we make it easy!</i></p>
                            </li>
                            <li>
                                <h4><i class="questionicon">Who decides what vehicles I rent and under what rates and terms?</i></h4>
                                <p><i class="questionicon">You do, we just facilitate (software/hardware), the insurance and the training. It’s your business to operate how you would like…we work for you and give you everything you need to custom design your “Mobility as a Service” business…and we add the strength of the “powered by DriveItAway label” with our national “Lyft Your Down Payment” and “Drive for Your Down Payment” programs.</i></p>
                            </li>
                            <li>
                                <h4><i class="questionicon">What does the comprehensive insurance cover?</i></h4>
                                <p><i class="questionicon">All of the State minimums and any time your driver does not happen to be covered under a Lyft/Uber Ride Sharing policy. The vehicle, in operation, is never under your garage policy coverage. You may title vehicles to be on your car sharing program in a separate LLC if desired. </i></p>
                            </li>
                            <li>
                                <h4><i class="questionicon">I want to try it, how do I start?</i></h4>
                                <p><i class="questionicon"><a href="/contactus" title="CLICK HERE">CLICK HERE</a> to get started. For a limited time, we will even throw in, at our expense, one premium GPS module that does everything (tracks location mileage, fuel, engine light, hard acceleration or breaking, geo-fence locations, even opens the door of the vehicle by app), a $330 value, when you sign up. Give us a call <a href="tel:{{ $spTel }}">{{ $sp }}</a>, email us –<a href="mailto:dealers@driveitaway.com">dealers@driveitaway.com</a>, text DRIVEITAWAY to 69696, or just hit this <a href="/contactus" title="link">link</a>. We will get you started today. </i></p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <center><a href="/contactus" title="START TODAY" class="btn btn-driveitaway ">START TODAY <i class="icon-arrow-right14 position-right"></i></a></center>
            </div>
        </div>
        <div class="row">&nbsp;</div>
    </div>
</div>
@endsection
