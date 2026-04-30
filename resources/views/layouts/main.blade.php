{{-- Port of Cake `app/View/Layouts/main.ctp` — logged-in app shell (navbar + sidebar + content). --}}
<!DOCTYPE html>
<html>
<head>
    @include('layouts.partials.cake.head_user_main')
</head>
<body>
@include('layouts.partials.cake.navbar_user')

<div class="page-container">
    <div class="page-content">
        <div class="sidebar sidebar-main">
            <div class="sidebar-content">
                <div class="sidebar-user">
                    <div class="category-content">
                        <div class="media">
                            <a href="#" class="media-left"><img src="{{ legacy_asset('img/placeholder.jpg') }}" class="img-circle img-sm" alt=""></a>
                            <div class="media-body">
                                <span class="media-heading text-semibold">{{ ucfirst((string)session('userfullname', 'User')) }}</span>
                                <div class="text-size-mini text-muted">
                                    {{ ucfirst((string)session('dispacherBusinessName', '')) }}
                                </div>
                            </div>
                            <div class="media-right media-middle"></div>
                        </div>
                    </div>
                </div>
                @include('layouts.partials.cake.user_sidebar')
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
            </div>
            <div class="navbar navbar-default navbar-sm navbar-fixed-bottom">
                <div class="navbar-text">
                    &copy; {{ date('Y') }}. <a href="#">DriveItAway</a> All right reserved.
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

<script src="{{  legacy_asset('js/assets/js/plugins/forms/validation/validate.min.js') }}"></script>
<script src="{{  legacy_asset('js/assets/js/plugins/forms/validation/additional_methods.min.js') }}"></script>
{{-- Cake `elements/sql_dump` omitted; use Laravel debug tooling when needed. --}}
@stack('scripts')
</body>
</html>
