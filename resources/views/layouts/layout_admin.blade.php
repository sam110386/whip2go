{{-- Cake `app/View/Layouts/layout_admin.ctp` — admin-branded login shell. --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>@yield('title', $title_for_layout ?? 'DriveItAway')</title>
    <script type="text/javascript">
        var SITE_URL = @json(legacy_site_url());
    </script>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet"
        type="text/css">

    <script src="{{ legacy_asset('js/assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/validation/validate.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/core/app.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/pages/login_validation.js') }}"></script>

    <link rel="icon" href="{{ legacy_asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/core.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/components.css') }}">
    <link rel="stylesheet" href="{{ legacy_asset('css/theme2/colors.css') }}">

    @stack('meta')
    @stack('css')
    @stack('head_scripts')

</head>

<body>
    <div class="navbar navbar-default nav-dark">
        <div class="row">
            <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2">
                <div class="navbar-header">
                    <a class="navbarbrand" href="{{ legacy_site_url() }}/admin">
                        <img src="{{ legacy_asset('img/driveitaway-logo-blue.svg') }}" alt="DriveItAway"
                            style="width:200px;margin-top:18px;">
                    </a>
                    <ul class="nav navbar-nav pull-right visible-xs-block">
                        <li>
                            <a data-toggle="collapse" data-target="#navbar-mobile">
                                <i class="icon-tree5"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="page-container login-container">
        <div class="page-content">
            <div class="content-wrapper">
                <div class="content">
                    @yield('content')
                    <div class="footer text-muted">
                        &copy; {{ date('Y') }}. <a href="#">DriveItAway</a> All right reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="plaidModal" class="modal fade" role="dialog" data-modal-parent="#myModal">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>

    <div id="statementModal" class="modal fade" role="dialog" data-modal-parent="#plaidModal">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>

    @stack('scripts')

</body>

</html>