{{-- Port of Cake `app/View/Layouts/admin.ctp` head. --}}
<script type="text/javascript">
    var SITE_URL = @json(rtrim(legacy_site_url(), '/').'/');
</script>
<meta charset="utf-8">
<title>@yield('title', $title_for_layout ?? 'Admin')</title>
<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
<script src="{{ legacy_asset('assets/js/plugins/loaders/pace.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/core/libraries/jquery.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/loaders/blockui.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/core/app.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/forms/selects/bootstrap_select.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/ui/moment/moment.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/pickers/datetimepicker.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/pickers/datepicker.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/forms/styling/switch.min.js') }}"></script>
<script src="{{ legacy_asset('js/jquery.validate.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/notifications/noty.min.js') }}"></script>
<script src="{{ legacy_asset('assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
<link rel="icon" href="{{ legacy_asset('favicon.ico') }}">
<link rel="stylesheet" href="{{ legacy_asset('colorbox.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/icons/icomoon/styles.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/icons/fontawesome/styles.min.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/bootstrap.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/core.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/components.css') }}">
<link rel="stylesheet" href="{{ legacy_asset('theme2/colors.css') }}">
@stack('meta')
@stack('css')
@stack('head_scripts')
