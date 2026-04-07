<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no"/>
    <title>@yield('title', $title_for_layout ?? 'Whip2Go')</title>
    <script>var SITE_URL = "{{ url('/') }}/";</script>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/colors.css') }}">
    @stack('styles')
</head>
<body>
    <div class="navbar navbar-inverse">
        <div class="navbar-header"><a class="navbar-brand" href="{{ url('/') }}"><h1>Whip2go</h1></a></div>
        <div class="navbar-collapse collapse" id="navbar-mobile">
            <ul class="nav navbar-nav navbar-right">
                <li><a href="{{ session()->has('dispacherId') ? url('/logins/logout') : url('/logins/index') }}">{{ session()->has('dispacherId') ? 'Logout' : 'Login' }}</a></li>
                <li><a href="{{ url('/users/dashboard') }}">Dashboard</a></li>
            </ul>
        </div>
    </div>
    <div class="page-container login-container"><div class="page-content"><div class="content-wrapper"><div class="content">@yield('content')</div></div></div></div>
    <div class="navbar navbar-default navbar-sm navbar-fixed-bottom"><div class="navbar-text">&copy; {{ date('Y') }}. <a href="#">DriveItAway</a> All right reserved.</div></div>
    <script src="{{ asset('assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/forms/validation/validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/app.js') }}"></script>
    <script src="{{ asset('assets/js/pages/login_validation.js') }}"></script>
    @stack('scripts')
</body>
</html>
