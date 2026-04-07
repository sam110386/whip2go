<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no"/>
    <title>@yield('title', $title_for_layout ?? 'Whip2Go')</title>
    <script>var SITE_URL = "{{ url('/') }}/";</script>
    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.2.0/css/all.css"/>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('theme2/colors.css') }}">
    <link rel="stylesheet" href="{{ asset('stylenew.css') }}">
    @stack('styles')
</head>
<body>
    @includeIf('legacy.elements.header')
    @yield('content')
    @includeIf('legacy.elements.footer')
    <script src="{{ asset('assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/app.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/ui/moment/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/pickers/datepicker.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/forms/validation/validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/forms/validation/additional_methods.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
