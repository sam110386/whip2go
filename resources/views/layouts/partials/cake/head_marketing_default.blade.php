{{-- Port of Cake `app/View/Layouts/default.ctp` head (public marketing pages). --}}
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no" />

<script type="text/javascript">
    var SITE_URL = @json(rtrim(legacy_site_url(), '/') . '/');
</script>

<meta charset="utf-8">
<title>@yield('title', $title_for_layout ?? 'WHIP2GO')</title>

<link rel="stylesheet" href="//use.fontawesome.com/releases/v5.2.0/css/all.css" />
<link rel="icon" type="image/x-icon" href="{{ legacy_asset('favicon.ico') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/icons/icomoon/styles.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/bootstrap.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/core.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/components.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/colors.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/stylenew.css') }}">

@stack('meta')

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

@stack('head_scripts')
