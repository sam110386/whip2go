<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>@yield('title', $title_for_layout ?? 'Drive IT AWAY')</title>

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('css/theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/colors.css') }}">

    @yield('meta')
    @stack('styles')

    <script type="text/javascript">
        var SITE_URL = "{{ url('/') }}";
    </script>

    <script src="{{ asset('js/assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/forms/validation/validate.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/core/app.js') }}"></script>
    <script src="{{ asset('js/assets/js/pages/login_validation.js') }}"></script>

    @stack('head-scripts')

</head>

<body>
    <div class="navbar navbar-default nav-dark">
        <div class="row">
            <div class="col-xs-12 col-sm-10 col-md-8 col-lg-8 col-sm-offset-1 col-md-offset-2 col-lg-offset-2">
                <div class="navbar-header">
                    <a class="navbarbrand" href="{{ url('/admin') }}">
                        <img src="{{ asset('img/driveitaway-logo-blue.svg') }}" alt="DriveItAway"
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
                        &copy; {{ date('Y') }} <a href="#">DriveItAway</a> All rights reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @includeIf('legacy.elements.sql_dump')

    <div id="plaidModal" class="modal fade" role="dialog" data-modal-parent="#myModal">
        <div class="modal-dialog">
            <div class="modal-content">
            </div>
        </div>
    </div>

    <div id="statementModal" class="modal fade" role="dialog" data-modal-parent="#plaidModal">
        <div class="modal-dialog">
            <div class="modal-content">
            </div>
        </div>
    </div>

    @stack('scripts')
</body>

</html>
