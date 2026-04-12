@extends('layouts.default')

@section('title', $title_for_layout ?? 'Drivers')

@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <center><h1>Drivers</h1></center>
                <p>Stop paying for the time you’re not driving your vehicle! Explore new ways to drive: Car Rentals, Car Subscriptions, and Peer-to-Peer Car Sharing are all available to suit your needs, budget, and lifestyle. Different packages can get you into a car for less money and less commitment.</p>
                <ol>
                    <li style="list-style: inherit;">Determine how long you need your vehicle. Vehicles are available to reserve for as short as a few hours to as long as a month or more.</li>
                    <li style="list-style: inherit;">Search for availability</li>
                    <li style="list-style: inherit;">Choose your vehicle</li>
                    <li style="list-style: inherit;">Pick it up and drive away!</li>
                </ol>
                <p>Ride Share packages exist exclusively for Drivers who want to drive for Uber or Lyft. We provide the proper insurance to protect you at no additional cost!</p>
            </div>
        </div>
    </div>
</div>
@endsection
