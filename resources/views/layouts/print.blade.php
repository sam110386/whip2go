{{-- Cake `app/View/Layouts/print.ctp` --}}
<!DOCTYPE html>
<html>
<head>
    <script type="text/javascript">
        var SITE_URL = @json(legacy_site_url());
    </script>
    <meta charset="utf-8">
    <title>@yield('title', $title_for_layout ?? 'Print')</title>
    <script src="{{ legacy_asset('js/jquery.validate.js') }}"></script>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css"/>
    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.2.0/css/all.css"/>
    <link rel="icon" href="{{ legacy_asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/core.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/components.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/colors.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('stylenew.css') }}">
    @stack('head_scripts')
</head>
<body>
@yield('content')
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
@stack('scripts')
</body>
</html>
