@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'NADA 2019')

@section('content')
    <div class="main-container">
        <div class="container">
            <div class="row">
                @if(session('success'))
                    <div class="col-xs-12">
                        <div class="alert alert-success">{{ session('success') }}</div>
                    </div>
                @endif
                @if(session('error'))
                    <div class="col-xs-12">
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    </div>
                @endif
                @if($errors->any())
                    <div class="col-xs-12">
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    </div>
                @endif
                <div class="col-xs-12">
                    <center>
                        <h1>NADA 2019 Private Meetings with John F. Possumato </h1>
                    </center>
                    <p>For several years now, I’ve written trade articles, spoken at trade shows, and counseled many dealers
                        on the best way to enter the emerging Shared Mobility marketplace. Please accept my personal
                        invitation to stop by and see me at our DriveItAway telematics partner PassTime booth 5450N, or
                        schedule private meeting with me, or email me for a future chat. </p>
                    <p>I look forward to seeing you at NADA 2019</p>

                </div>
                <div class="col-sm-8 col-xs-12">
                    <img src="{{ legacy_asset('img/DriveitAway_NADA_2019.png') }}" alt="" class="img-responsive">
                </div>
                <div class="col-sm-4 col-xs-12">

                    <form method="post" action="/nada2019" class="form-horizontal" name="frmadmin" id="CustomerContact"
                        onsubmit="return isvalid()">
                        @csrf
                        <fieldset>
                            <legend class="text-semibold"><i class="icon-reading position-left driveitawaycolor"></i><strong
                                    class="driveitawaycolor">Register Yourself</strong></legend>

                            <div class="form-group">
                                <div class="col-lg-6">
                                    <input type="text" name="Contact[name]" value="{{ old('Contact.name') }}"
                                        class="form-control required" placeholder="Your Name">
                                </div>
                                <div class="col-lg-6">
                                    <input type="email" name="Contact[email]" value="{{ old('Contact.email') }}"
                                        class="form-control required" placeholder="Your Email">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-6">
                                    <input type="text" name="Contact[phone]" value="{{ old('Contact.phone') }}"
                                        class="form-control phone required" placeholder="Your Phone">
                                </div>
                                <div class="col-lg-6">
                                    <input type="text" name="Contact[organization]"
                                        value="{{ old('Contact.organization') }}" class="form-control required"
                                        placeholder="Organization Name">
                                </div>
                            </div>
                            <div class="form-group">

                                <div class="col-lg-8">
                                    <textarea name="Contact[comment]" class="form-control" rows="5" cols="5"
                                        placeholder="Purpose of Meeting">{{ old('Contact.comment') }}</textarea>
                                </div>
                                <div class="col-lg-4 text-right">
                                    <button type="submit" class="btn btn-primary">Send <i
                                            class="icon-arrow-right14 position-right"></i></button>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
            <div class="row">&nbsp;</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#CustomerContact").validate();
        });
        function isvalid() {
            if (jQuery("#CustomerContact").valid()) {
                return true;
            } else {
                return false;
            }
            return false;
        }
    </script>
@endpush