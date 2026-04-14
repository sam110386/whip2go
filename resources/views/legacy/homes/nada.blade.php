@extends('layouts.driveitaway')
@section('content')
<div class="main-container">
    <div class="container">
        <div class="row">
            @if(session('success'))<div>{{ session('success') }}</div>@endif
            @if(session('error'))<div>{{ session('error') }}</div>@endif
            <div class="col-xs-12">
                <center><h1>NADA 2019 Private Meetings with John F. Possumato</h1></center>
                <p>For several years now, I’ve written trade articles, spoken at trade shows, and counseled many dealers on the best way to enter the emerging Shared Mobility marketplace.</p>
            </div>
            <div class="col-sm-8 col-xs-12">
                <img src="{{ asset('img/DriveitAway_NADA_2019.png') }}" alt="" class="img-responsive">
            </div>
            <div class="col-sm-4 col-xs-12">
                <form action="{{ route('legacy.nada') }}" method="POST" class="form-horizontal" id="CustomerContact">
                    @csrf
                    <fieldset>
                        <legend class="text-semibold"><i class="icon-reading position-left driveitawaycolor"></i><strong class="driveitawaycolor">Register Yourself</strong></legend>
                        <div class="form-group">
                            <div class="col-lg-6"><input name="Contact[name]" class="form-control required" placeholder="Your Name"></div>
                            <div class="col-lg-6"><input name="Contact[email]" class="form-control required" placeholder="Your Email"></div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-6"><input name="Contact[phone]" class="form-control phone required" placeholder="Your Phone"></div>
                            <div class="col-lg-6"><input name="Contact[organization]" class="form-control required" placeholder="Organization Name"></div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-8"><textarea name="Contact[comment]" class="form-control" rows="5" cols="5" placeholder="Purpose of Meeting"></textarea></div>
                            <div class="col-lg-4 text-right"><button type="submit" class="btn btn-primary">Send <i class="icon-arrow-right14 position-right"></i></button></div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
        <div class="row">&nbsp;</div>
    </div>
</div>
@endsection
