<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>@yield('title', $title_for_layout ?? 'Admin')</title>

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('css/colorbox.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/icons/icomoon/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/icons/fontawesome/styles.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/core.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/theme2/colors.css') }}">

    @yield('meta')
    @stack('styles')

    <script>
        var SITE_URL = "{{ url('/') }}/";
    </script>

    <script src="{{ asset('js/assets/js/plugins/loaders/pace.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/core/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/core/libraries/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/core/app.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/forms/selects/bootstrap_select.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/ui/moment/moment.min.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/pickers/datetimepicker.js') }}"></script>
    <script src="{{ asset('js/assets/js/plugins/pickers/datepicker.js') }}"></script>

    @stack('head-scripts')

</head>

<body>
    <div class="navbar navbar-default header-highlight">
        <div class="navbar-header">
            <a class="navbar-brand" href="javascript:void(0);">
                <span style="color:#fff; font-size:18px;">{{ 'DriveItAway' }}</span>
            </a>
            <ul class="nav navbar-nav visible-xs-block">
                <li>
                    <a data-toggle="collapse" data-target="#navbar-mobile">
                        <i class="icon-tree5"></i>
                    </a>
                </li>
                <li>
                    <a class="sidebar-mobile-main-toggle">
                        <i class="icon-paragraph-justify3"></i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="navbar-collapse collapse" id="navbar-mobile">
            <ul class="nav navbar-nav">
                <li>
                    <a class="sidebar-control sidebar-main-toggle hidden-xs">
                        <i class="icon-paragraph-justify3"></i>
                    </a>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown dropdown-user">
                    <a class="dropdown-toggle" data-toggle="dropdown">
                        <img src="{{ asset('img/placeholder.jpg') }}" alt="">
                        <span>{{ ucfirst(session('adminName')) }}</span>
                        <i class="caret"></i>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                            <a href="#">
                                {{ 'My profile' }}
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="{{ url('admin/admins/logout') }}" title = 'Logout'>
                                {{ 'Logout' }}
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <div class="page-container">
        <div class="page-content">
            <div class="sidebar sidebar-main">
                <div class="sidebar-content">

                    <div class="sidebar-user">
                        <div class="category-content">
                            <div class="media">
                                <a href="#" class="media-left">
                                    <img src="{{ asset('img/placeholder.jpg') }}" class="img-circle img-sm">
                                </a>
                                <div class="media-body">
                                    <span class="media-heading text-semibold">
                                        {{ ucfirst(session('adminName')) }}
                                    </span>
                                    <div class="text-size-mini text-muted">
                                        {{ ucfirst(session('adminName')) }}
                                    </div>
                                </div>

                                <div class="media-right media-middle">

                                </div>
                            </div>
                        </div>
                    </div>

                    @includeif('admin.elements.admin.left_admin')

                </div>
            </div>

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

    @includeIf('admin.elements.sql_dump')
    @stack('scripts')

</body>

</html>
