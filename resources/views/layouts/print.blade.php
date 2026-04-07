<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', $title_for_layout ?? 'Print')</title>
    <script>var SITE_URL = "{{ url('/') }}/";</script>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css"/>
    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.2.0/css/all.css"/>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('stylenew.css') }}">
</head>
<body>
    @yield('content')
    <script src="{{ asset('assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/app.js') }}"></script>
</body>
</html>
