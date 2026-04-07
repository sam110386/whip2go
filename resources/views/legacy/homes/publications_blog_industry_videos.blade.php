@extends('layouts.driveitaway')
@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <center><h1>Publications, Blog & Industry Videos</h1></center>
                <p>We wrote extensively on dealer focused Mobility as a Service.</p>
            </div>
            <div class="col-xs-12">
                <p><strong>Industry Association Videos -</strong></p>
                <div class="col-md-12 pull-left"><img src="{{ asset('img/LeadershipandCompanyMissionPic.png') }}" alt="" class="img-responsive"></div>
                <a href="https://slideslive.com/38906705/de9r-mobility-as-a-service-a-threat-or-greatest-opportunity" target="_blank"><h5>March 2018 NADA Convention Workshop – Mobility as a Service: Threat or Greatest Opportunity</h5></a>
            </div>
            <div class="col-xs-12">
                <p><strong>Published articles –</strong></p>
                <a href="https://www.wardsauto.com/industry-voices/are-car-dealers-thin-ice-or-lacing-speed-skates" target="_blank"><h5>Are Car Dealers on Thin Ice or Lacing Up Speed Skates?</h5></a>
                <p>WardsAuto, January 2019</p>
                <a href="https://www.wardsauto.com/dealers/vehicle-subscription-services-car-sharing-another-name-rentals" target="_blank"><h5>Vehicle Subscription Services, Car Sharing, by Another Name: Rentals</h5></a>
                <p>WardsAuto, July 2018</p>
            </div>
            <div class="col-xs-12">
                <p><strong>Webinars -</strong></p>
                <div class="col-md-12 pull-left"><img src="{{ asset('img/companypublicationsblog_pic_webinarseries.png') }}" alt="" class="img-responsive"></div>
                <a href="https://marketing.nada.org/acton/media/4712/mobilityasaservice" target="_blank"><h5>December 2018 NADA Webinar – How to Profit Today While Preparing for the Future</h5></a>
            </div>
        </div>
    </div>
</div>
@endsection
