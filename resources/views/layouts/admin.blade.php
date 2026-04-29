{{--
    Port of Cake `app/View/Layouts/admin.ctp`.

    Parity exception: several cloud-area controllers in the legacy app set
    `$this->layout = 'admin'` (HitchBookings, HitchReports, CustomerReports,
    Lead/Leads, Report/* cloud_index actions), so the migrated cloud/**/*.blade.php
    pages extend this layout by name. Mirrors `admin/layouts/app.blade.php`
    intentionally — keep the two in sync until the cloud area has a dedicated
    chrome of its own.
--}}
<!DOCTYPE html>
<html>

<head>
    @include('layouts.partials.cake.head_admin')
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
                                    <span class="media-heading text-semibold">{{ ucfirst((string) session('adminName', 'Admin')) }}</span>
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

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
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
