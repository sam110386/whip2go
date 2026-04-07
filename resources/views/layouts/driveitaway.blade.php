<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no"/>
    <title>@yield('title', $title_for_layout ?? 'Drive IT AWAY')</title>

    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.2.0/css/all.css"/>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('driveitawaystyle.css') }}">
    @yield('meta')
    @stack('styles')

    <script type="text/javascript">
        var SITE_URL = "{{ url('/') }}/";
    </script>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-GLQS917RCM"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-GLQS917RCM');
    </script>

    <!-- <script src="{{ asset('assets/js/plugins/loaders/pace.min.js') }}"></script> -->
    <script src="{{ asset('assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <!-- <script src="{{ asset('assets/js/plugins/loaders/blockui.min.js') }}"></script> -->
    <script src="{{ asset('assets/js/core/app.js') }}"></script>
    <!-- <script src="{{ asset('assets/js/plugins/forms/styling/switchery.min.js') }}"></script> -->
    <!-- <script src="{{ asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script> -->
    <!-- <script src="{{ asset('assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script> -->
    <!-- <script src="{{ asset('assets/js/plugins/ui/moment/moment.min.js') }}"></script> -->
    <!-- <script src="{{ asset('assets/js/plugins/pickers/datepicker.js') }}"></script> -->
    <!-- <script src="{{ asset('assets/js/plugins/forms/validation/validate.min.js') }}"></script> -->
    <!-- <script src="{{ asset('assets/js/plugins/forms/validation/additional_methods.min.js') }}"></script> -->
    @stack('head-scripts')
</head>
<body>
        @if(!session()->has('userid'))
        <div class="container-fluid topcontainer">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <span class="pull-right topcontainertext">
                            <h6>Dealers. This platform was built for your vehicles.<br>Earn up to $1100 per month for each car on your lot!</h6>
                        </span>
                    </div>
                    <div class="col-md-6 ">
                        <a href="{{ url('/logins/index') }}" title="Get Started" class="btn btn-getstarted pull-left">Get Started</a>
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
                            <i class="icon-mobile driveitawaycolor"></i> <span class="text-white text-size-12">{{ env('SUPPORT_PHONE', '') }}</span>
                        </div>
                        <div class="col-md-3">
                            <!--i class="icon-alarm driveitawaycolor"></i> <span class="text-white text-size-12">Mon-Fri 9AM - 5PM</span-->
                        </div>
                    </div>
                    
                </div>
            </div>    
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="row row-border">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 no-pad">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar" aria-expanded="false">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="{{ url('/') }}">
                            <img src="{{ asset('img/driveitaway-logo-blue.svg') }}" alt="Drive IT AWAY" style="width:200px;margin-top:18px;">
                        </a>
                    </div>

                    <!-- Collect the nav links, forms, and other content for toggling -->
                    <div class="collapse navbar-collapse" id="main-navbar">
                        <ul class="nav navbar-nav navbar-right">
                            @if(session()->has('userid'))
                            <li><a href="{{ url('/users/dashboard') }}" title="My Account">My Account</a></li>
                            @else
                            <li><a href="{{ url('/logins/index') }}" title="Login">Login</a></li>
                            @endif
                            {{-- Legacy commented navigation kept in Cake version. --}}
                        </ul>
                    </div><!-- /.navbar-collapse -->
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </nav>
</header>

        @yield('content')
        <!-- Get Start Section End  -->
       <div id="footer">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div class="col-md-4 col-lg-4 col-sm-12">
                    <div class="aboutus">
                        <h5>About Us</h5>

                        The creator of the "Lyft Your Down Payment" program and the
                        "Drive For Your Down Payment"
                        program- Ride Sharing drivers can get a temporary vehicle to
                        Fund their vehicle purchase!
                    </div>
                    <img src="{{ asset('img/driveitaway-logo-blue.svg') }}" alt="Drive IT AWAY" style="width:200px;margin-top:18px;">
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
                                <li><a href="{{ url('/users/dashboard') }}" title="My Account">My Account</a></li>
                                @else
                                <li><a href="{{ url('/logins/index') }}" title="Login">Login</a></li>
                                @endif
                                {{-- Legacy commented footer links kept in Cake version. --}}
                            </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        <script type='text/javascript' src='https://c.la2-c1-ia2.salesforceliveagent.com/content/g/js/48.0/deployment.js'></script>
        <script type='text/javascript'>
        liveagent.init('https://d.la2-c1-ia2.salesforceliveagent.com/chat', '5721U000000dd55', '00D1U000000uO5w');
        </script>
        <script type="text/javascript">
            $(function () {
                $("header nav.navbar").addClass("nav-dark");
                $("header nav.navbar").removeClass("navbar-fixed-top");
                $(window).on('scroll', function () {
                    var scroll = $(window).scrollTop();
                    //alert(scroll);
                    if (scroll > 80) {
                        $("header nav.navbar").addClass("navbar-fixed-top");
                    } else {
                        $("header nav.navbar").removeClass("navbar-fixed-top");
                    }
                });
            });
        </script>    
        <!-- /page container -->
        @includeIf('legacy.elements.sql_dump')
        <!-- script src="//txtdash.com/chat/textchat.php?widgets=664" type="text/javascript"></script>

        <a class="stc_textchat" onclick="startchat(this)" data-affid="" data-aid="" data-chattype="general" data-cdk="" data-shadow="" data-departments="" data-success="true" data-product="" style="cursor:pointer;width:auto;position:fixed;right:0;bottom:0;margin:5px;transform: scale(0.8, 0.8) rotate(0deg);  -ms-transform: scale(0.8, 0.8) rotate(0deg);  -moz-transform: scale(0.8, 0.8) rotate(0deg);  -webkit-transform: scale(0.8, 0.8) rotate(0deg);  -o-transform: scale(0.8, 0.8) rotate(0deg);"><img class="stc_textchat_img" src="" style="width:auto;display: none;"></a!-->
        <script>
            window.intercomSettings = {
              app_id: "lq57p3jl"
            };
        </script>

          <script>
          // We pre-filled your app ID in the widget URL: 'https://widget.intercom.io/widget/lq57p3jl'
          (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/lq57p3jl';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
          </script>
          @stack('scripts')
    </body>
</html>
