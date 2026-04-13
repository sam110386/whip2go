{{-- Cake filename `dispacher.ctp` (typo preserved). Dispatcher shell: same chrome as `main` but inner footer. --}}
<!DOCTYPE html>
<html>
<head>
    @include('layouts.partials.cake.head_user_dispacher')
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
                                <div class="text-size-mini text-muted">{{ ucfirst((string)session('dispacherBusinessName', '')) }}</div>
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
                @yield('content')
                <div class="footer text-muted">
                    &copy; {{ date('Y') }}. <a href="#">DriveItAway</a> All right reserved.
                </div>
            </div>
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
