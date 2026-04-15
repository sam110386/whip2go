@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Drivers')

@section('content')
    @php $sp = config('legacy.support_phone');
    $spTel = preg_replace('/\D+/', '', (string) $sp); @endphp
    <div class="main-container">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <center>
                        <h1>Drivers</h1>
                    </center>
                    <p>Need a Vehicle to Start Driving for Lyft or Uber? Don’t get caught in the high cost/short term rental
                        or lease trap! DriveItAway enables ownership for all ride share drivers. It’s as easy as 1, 2, 3!
                    </p>
                    <ol>
                        <li style="list-style: inherit;">Come to a DriveItAway powered “Lyft Your Down Payment” or “Drive
                            for Your Down Payment” authorized car dealership and see the DriveItAway specialist. Choose the
                            vehicle you want, and find out what you’ll need to buy or lease it long term. </li>
                        <li style="list-style: inherit;">If the deal can’t be done that day, pick up a DriveItAway vehicle
                            to drive for Lyft (or Uber) and “Lyft Your Down Payment” (discounted rental vehicles, with all
                            insurance included, for a month or however long it takes).</li>
                        <li style="list-style: inherit;">Come back when you raise enough money driving, and buy the vehicle
                            you choose! New drivers will receive a “kick start” bonus of up to $500 towards your down
                            payment.</li>

                    </ol>
                    <p>Want more info, phone {{ $sp }} or email info@driveitaway.com</p>
                </div>
                <div
                    class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2 no-pad text-center">
                    <div class="col-md-12 downloadapp-text">Download Our App</div>
                    <div class="col-md-6">
                        <a href="https://itunes.apple.com/in/app/whip-car-sharing/id1393803514?mt=8" target="_blank"
                            class=" btn btn-driveitaway btn-rounded pull-right">
                            App Store</a>
                    </div>
                    <div class="col-md-6">
                        <a href="https://play.google.com/store/apps/details?id=com.carshare" target="_blank"
                            class=" btn btn-driveitaway btn-rounded pull-left">
                            Play Store
                        </a>
                    </div>

                </div>
            </div>
            <div class="row">&nbsp;</div>
            <div class="row">
                <div class="col-xs-6">
                    <a class="btn btn-driveitaway btn-lg pull-left" role="button" href="/logins/index">Get Started</a>
                </div>
                <div class="col-xs-6">
                    <a href="/contactus" title="More Info" class="btn btn-driveitaway pull-right">More Info <i
                            class="icon-arrow-right14 position-right"></i></a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <center>
                        <h2><i>Frequently Asked Questions</i></h2>
                    </center>
                    <div class="panel-collapse collapse in" id="collapseOne">
                        <div class="faqlist">
                            <ul>
                                <li>
                                    <h4><i class="questionicon">How do I qualify for a DriveItAway “Lyft Your Down Payment”
                                            or “Drive for Your Down Payment” vehicle?</i></h4>
                                    <p>If you’re a new rideshare driver, apply to drive for Lyft <a
                                            href="https://www.lyft.com/partner/driveitaway?code=DRIVEITAWAY2"
                                            target="_blank">here</a></p>
                                    <p>Or you can call <a href="tel:{{ $spTel }}">{{ $sp }}</a>, email <a
                                            href="mailto:info@driveitaway.com">info@driveitaway.com</a>, or text “LYFT” at
                                        69696 for assistance. </p>
                                    <p>Once you are approved to drive for Lyft or Uber, reserve your vehicle on the <a
                                            href="https://itunes.apple.com/in/app/whip-car-sharing/id1393803514?mt=8"
                                            target="_blank">DriveItAway app</a>. (Please note: vehicles must have our code
                                        DRIVEITAWAY for you to receive the down payment bonus).</p>
                                    <p>If you are already an approved Lyft or Uber driver, reserve your vehicle on the
                                        DriveItAway app, choose your term, from a few days to a few months, then show the
                                        dealership your valid driver’s license and rideshare app when you pick up your
                                        vehicle. (Please note: you will need a debit or credit card in order to rent a
                                        vehicle).</p>
                                </li>
                                <li>
                                    <h4><i class="questionicon">Where do I go to pick up a vehicle to drive?</i></h4>
                                    <p>Any DriveItAway affiliated dealer, although it’s recommended you choose a dealer
                                        where you may also want to purchase your vehicle.</p>
                                </li>
                                <li>
                                    <h4><i class="questionicon">How do I go through the process of selecting and purchasing
                                            a vehicle?</i></h4>
                                    <p>That’s easy, choose the affiliated DriveItAway dealership of your choice, and when
                                        you stop to pick up your rental vehicle, you can also meet our DriveItAway “Lyft
                                        Your Down Payment” specialist, who can help you with your vehicle selection, answer
                                        any questions, calculate the down payment needed. All DriveItAway customers receive
                                        special consideration with our “Lyft Your Down Payment” program specialists.</p>
                                </li>
                                <li>
                                    <h4><i class="questionicon">How do I reserve the rental?</i></h4>
                                    <p>That’s easy too, once you are approved to drive for Lyft or Uber, just go on to the
                                        reservation section of the mobile app or website and choose your vehicle.</p>
                                </li>
                                <li>
                                    <h4><i class="questionicon">How do I pay for it?</i></h4>
                                    <p>You will need a major credit or debit card to pay for the vehicle, and you can choose
                                        the term. It’s okay to rent for just a few days and then renew, folks do it all the
                                        time.</p>
                                </li>
                                <li>
                                    <h4><i class="questionicon">While the rental process is automated, is it okay to call or
                                            email someone for additional questions on the program?</i></h4>
                                    <p>Absolutely! Personal service is what sets us apart from the many rental companies out
                                        there that will gladly lock you into a perpetual rental or short-term lease, but
                                        never talk to you about solving your transportation needs. We can help you at
                                        headquarters <a href="tel:{{ $spTel }}">{{ $sp }}</a>, email: <a
                                            href="mailto:info@driveitaway.com">info@driveitaway.com</a> or text “LYFT” to
                                        69696.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <center>
                        <a href="https://itunes.apple.com/in/app/whip-car-sharing/id1393803514?mt=8" target="_blank"
                            class=" btn btn-driveitaway btn-rounded">
                            GET STARTED NOW
                        </a>
                    </center>
                </div>
            </div>
            <div class="row">&nbsp;</div>
        </div>
    </div>
@endsection