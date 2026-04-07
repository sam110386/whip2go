<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', $title_for_layout ?? 'Admin Booking')</title>
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
    <div class="navbar navbar-default header-highlight">
        <div class="navbar-header"><a class="navbar-brand" href="javascript:void(0);"><span style="color:#fff; font-size:18px;">DriveItAway</span></a></div>
    </div>
    <div class="page-container">
        <div class="page-content">
            <div class="sidebar sidebar-main"><div class="sidebar-content">@includeIf('admin.left_admin')</div></div>
            <div class="content-wrapper">
                <div class="content">
                    @yield('content')
                    <div class="footer text-muted">&copy; {{ date('Y') }} DriveItAway. <a href="#">All right reserved</a></div>
                </div>
            </div>
        </div>
    </div>
    <div id="plaidModal" class="modal fade" role="dialog"><div class="modal-dialog"><div class="modal-content"></div></div></div>
    <div id="statementModal" class="modal fade" role="dialog"><div class="modal-dialog"><div class="modal-content"></div></div></div>
    <script src="{{ asset('assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
