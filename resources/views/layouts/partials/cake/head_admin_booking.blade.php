{{-- Cake `app/View/Layouts/admin_booking.ctp` head — extra legacy booking scripts. --}}
<script type="text/javascript">
    var SITE_URL = @json(legacy_site_url());
</script>

<meta charset="utf-8">
<title>@yield('title', $title_for_layout ?? 'Admin')</title>
<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">

<script src="{{ legacy_asset('js/assets/js/plugins/loaders/pace.min.js') }}"></script>
<script src="{{ legacy_asset('js/assets/js/core/libraries/jquery.min.js') }}"></script>
<script src="{{ legacy_asset('js/assets/js/core/libraries/bootstrap.min.js') }}"></script>
<script src="{{ legacy_asset('js/assets/js/plugins/loaders/blockui.min.js') }}"></script>
<script src="{{ legacy_asset('js/assets/js/core/app.js') }}"></script>
<script src="{{ legacy_asset('js/assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
<script src="{{ legacy_asset('js/assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
<script src="{{ legacy_asset('js/assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
<script src="{{ legacy_asset('js/assets/js/plugins/ui/moment/moment.min.js') }}"></script>
<script src="{{ legacy_asset('js/jquery.validate.js') }}"></script>
<script src="{{ legacy_asset('js/admin_booking.js') }}"></script>
<script src="{{ legacy_asset('js/all.js') }}"></script>

<link rel="icon" type="image/x-icon" href="{{ legacy_asset('favicon.ico') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/icons/icomoon/styles.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/bootstrap.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/core.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/components.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('css/theme2/colors.css') }}">

@stack('meta')
@stack('css')
@stack('head_scripts')