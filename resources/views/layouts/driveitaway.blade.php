{{-- Cake `app/View/Layouts/driveitaway.ctp` — DriveItAway marketing shell (subset; third-party widgets gated in
config/legacy.php). --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no" />
    <title>@yield('title', $title_for_layout ?? 'DriveItAway')</title>
    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.2.0/css/all.css" />
    <link rel="icon" type="image/x-icon" href="{{ legacy_asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/core.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/components.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/colors.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/driveitawaystyle.css') }}">
    <script type="text/javascript">
        var SITE_URL = @json(rtrim(legacy_site_url(), '/') . '/');
    </script>
    @php
        $gaId = (string) config('legacy.analytics_measurement_id', '');
    @endphp
    @if($gaId !== '')
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', @json($gaId));
        </script>
    @endif
    <script src="{{ legacy_asset('js/assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/app.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/ui/moment/moment.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/pickers/datepicker.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/validation/validate.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/validation/additional_methods.min.js') }}"></script>
    @stack('meta')
    @stack('styles')
    @stack('head_scripts')
</head>

<body>
    @if(!session()->has('userid'))
        <div class="container-fluid topcontainer">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <span class="pull-right topcontainertext">
                            <h6>Dealers. This platform was built for your vehicles.<br>Earn up to $1100 per month for each
                                car on your lot!</h6>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <a href="/logins/index" title="Get Started" class="btn btn-getstarted pull-left">Get Started</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <header>
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 no-pad">
                        <div class="col-md-12 no-pad ">
                            <div class="col-md-3">
                                <i class="icon-mobile driveitawaycolor"></i>
                                <span class="text-white text-size-12">{{ config('legacy.support_phone') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row row-border">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 no-pad">
                        <div class="navbar-header">
                            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                                data-target="#main-navbar" aria-expanded="false">
                                <span class="sr-only">Toggle navigation</span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                            </button>
                            <a class="navbar-brand" href="{{ rtrim(legacy_site_url(), '/') }}/">
                                <img src="{{ legacy_asset('img/driveitaway-logo-blue.svg') }}" alt="Drive IT AWAY"
                                    style="width:200px;margin-top:18px;">
                            </a>
                        </div>
                        <div class="collapse navbar-collapse" id="main-navbar">
                            <ul class="nav navbar-nav navbar-right">
                                @if(session()->has('userid'))
                                    <li><a href="/users/dashboard" title="My Account">My Account</a></li>
                                @else
                                    <li><a href="/logins/index" title="Login">Login</a></li>
                                @endif
                                <li><a href="/homes/featured" title="Featured">Featured</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    @yield('content')

    <div id="footer">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="col-md-4 col-lg-4 col-sm-12">
                        <div class="aboutus">
                            <h5>About Us</h5>
                            The creator of the "Lyft Your Down Payment" program and the "Drive For Your Down Payment"
                            program—
                            Ride Sharing drivers can get a temporary vehicle to fund their vehicle purchase!
                        </div>
                        <img src="{{ legacy_asset('img/driveitaway-logo-blue.svg') }}" alt="Drive IT AWAY"
                            style="width:200px;margin-top:18px;">
                    </div>
                </div>
            </div>
            <div class="row row-border">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 ">
                    <div class="col-md-12 ">
                        <div class="col-md-4">
                            <p style="padding-top:12px;">&copy; 2018 DriveItAway LLC. All Right Reserved</p>
                        </div>
                        <div class="col-md-8 text-center">
                            <ul class="nav navbar-nav navbar-right">
                                @if(session()->has('userid'))
                                    <li><a href="/users/dashboard" title="My Account">My Account</a></li>
                                @else
                                    <li><a href="/logins/index" title="Login">Login</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(config('legacy.enable_salesforce_live_agent'))
        <script src="https://c.la2-c1-ia2.salesforceliveagent.com/content/g/js/48.0/deployment.js"></script>
        <script>
            liveagent.init('https://d.la2-c1-ia2.salesforceliveagent.com/chat', '5721U000000dd55', '00D1U000000uO5w');
        </script>
    @endif
    <script type="text/javascript">
        $(function () {
            $("header nav.navbar").addClass("nav-dark");
            $("header nav.navbar").removeClass("navbar-fixed-top");
            $(window).on('scroll', function () {
                var scroll = $(window).scrollTop();
                if (scroll > 80) {
                    $("header nav.navbar").addClass("navbar-fixed-top");
                } else {
                    $("header nav.navbar").removeClass("navbar-fixed-top");
                }
            });
        });
    </script>
    @if(config('legacy.enable_intercom'))
        <script>
            window.intercomSettings = { app_id: "lq57p3jl" };
        </script>
        <script>
            (function () { var w = window; var ic = w.Intercom; if (typeof ic === "function") { ic('reattach_activator'); ic('update', w.intercomSettings); } else { var d = document; var i = function () { i.c(arguments); }; i.q = []; i.c = function (args) { i.q.push(args); }; w.Intercom = i; var l = function () { var s = d.createElement('script'); s.type = 'text/javascript'; s.async = true; s.src = 'https://widget.intercom.io/widget/lq57p3jl'; var x = d.getElementsByTagName('script')[0]; x.parentNode.insertBefore(s, x); }; if (w.attachEvent) { w.attachEvent('onload', l); } else { w.addEventListener('load', l, false); } } })();
        </script>
    @endif
    @stack('scripts')
</body>

</html>