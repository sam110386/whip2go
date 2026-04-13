{{-- Cake `app/View/Layouts/home.ctp` — marketing home (lighter head + scroll nav script). --}}
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no"/>
    <script type="text/javascript">
        var SITE_URL = @json(rtrim(legacy_site_url(), '/').'/');
    </script>
    <meta charset="utf-8">
    <title>@yield('title', $title_for_layout ?? 'WHIP2GO')</title>
    <script src="{{ legacy_asset('js/jquery.validate.js') }}"></script>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css"/>
    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.2.0/css/all.css"/>
    <link rel="icon" type="image/x-icon" href="{{ legacy_asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/bootstrap.css') }}">
    @stack('meta')
    <link rel="stylesheet" href="{{ legacy_asset('stylenew.css') }}">
    @stack('head_scripts')
</head>
<body>
@include('layouts.partials.cake.marketing_header')

@yield('content')

@include('layouts.partials.cake.marketing_footer')
<script src="{{ legacy_asset('assets/js/plugins/loaders/pace.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/core/libraries/jquery.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/loaders/blockui.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/core/app.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/ui/moment/moment.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/pickers/datepicker.js') }}"></script>
<script type="text/javascript">
    $(function () {
        $(window).on('scroll', function () {
            var scroll = $(window).scrollTop();
            if (scroll > 70) {
                $("nav.navbar.navbar-fixed-top").addClass("nav-dark");
            } else {
                $("nav.navbar.navbar-fixed-top").removeClass("nav-dark");
            }
        });
    });
</script>
@stack('scripts')
</body>
</html>
