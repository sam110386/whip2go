{{-- Cake `app/View/Layouts/without_header_footer.ctp` (login-style minimal chrome). --}}
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no"/>
    <meta charset="utf-8">
    <title>@yield('title', $title_for_layout ?? 'DriveItAway')</title>
    <script type="text/javascript">
        var SITE_URL = @json(legacy_site_url());
    </script>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <script src="{{  legacy_asset('js/assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ legacy_asset('assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ legacy_asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{  legacy_asset('js/assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{  legacy_asset('js/assets/js/plugins/forms/validation/validate.min.js') }}"></script>
    <script src="{{  legacy_asset('js/assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ legacy_asset('assets/js/core/app.js') }}"></script>
    <script src="{{ legacy_asset('assets/js/pages/login_validation.js') }}"></script>
    <link rel="icon" type="image/x-icon" href="{{ legacy_asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/core.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/components.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('theme2/colors.css') }}">
    @stack('meta')
    @stack('styles')
    @stack('head_scripts')
</head>
<body>
<div class="page-container login-container">
    <div class="page-content">
        <div class="content-wrapper">
            <div class="content">
                @yield('content')
            </div>
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
