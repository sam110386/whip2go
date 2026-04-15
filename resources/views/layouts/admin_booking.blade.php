{{-- Cake `app/View/Layouts/admin_booking.ctp` — same shell as admin with booking JS bundle in head. --}}
<!DOCTYPE html>
<html>

<head>
    @include('layouts.partials.cake.head_admin_booking')
</head>

<body class="">
    @include('layouts.partials.cake.navbar_admin')

    <div class="page-container">
        <div class="page-content">
            <div class="sidebar sidebar-main">
                <div class="sidebar-content">
                    <div class="sidebar-user">
                        <div class="category-content">
                            <div class="media">
                                <a href="#" class="media-left">
                                    <img src="{{ legacy_asset('img/placeholder.jpg') }}" class="img-circle img-sm"
                                        alt="">
                                </a>
                                <div class="media-body">
                                    <span class="media-heading text-semibold">
                                        {{ ucfirst((string) session('adminName', 'Admin')) }}
                                    </span>
                                    <div class="text-size-mini text-muted">
                                        {{ ucfirst((string) session('adminName', '')) }}
                                    </div>
                                </div>
                                <div class="media-right media-middle"></div>
                            </div>
                        </div>
                    </div>
                    @include('layouts.partials.cake.admin_sidebar')
                </div>
            </div>
            <div class="content-wrapper">
                <div class="content">
                    @hasSection('header_title')
                        <div class="page-header" style="margin-bottom:16px;">
                            <h1 class="text-semibold" style="margin:0;">@yield('header_title')</h1>
                        </div>
                    @endif
                    @yield('content')
                    <div class="footer text-muted">
                        &copy; {{ date('Y') }} DriveItAway. <a href="#">All right reserved</a>
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