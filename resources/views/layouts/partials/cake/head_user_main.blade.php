{{-- Port of Cake `app/View/Layouts/main.ctp` head (logged-in dealer / user shell). --}}
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no"/>
<script type="text/javascript">
    {{-- Cake `SITE_URL` includes a trailing slash; many scripts do `SITE_URL + "controller/action"`. --}}
    var SITE_URL = @json(rtrim(legacy_site_url(), '/').'/');
</script>
<meta charset="utf-8">
<title>@yield('title', $title_for_layout ?? 'DriveItAway')</title>
<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
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
<script src="{{ legacy_asset('assets/js/plugins/forms/styling/switch.min.js') }}"></script>
<link rel="icon" type="image/x-icon" href="{{ legacy_asset('favicon.ico') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/icons/icomoon/styles.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/bootstrap.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/core.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/components.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/colors.css') }}">
@stack('meta')
@stack('css')
@stack('head_scripts')
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
