@php
    $slug = session('SESSION_ADMIN.slug', 'admin');
    $logoutPrefix = $slug === 'cloud' ? '/cloud' : '/admin';
@endphp
<div class="navbar navbar-default header-highlight">
    <div class="navbar-header">
        <a class="navbar-brand" href="javascript:void(0);"><span style="color:#ffffff; font-size:18px;">DriveItAway</span></a>
        <ul class="nav navbar-nav visible-xs-block">
            <li><a data-toggle="collapse" data-target="#navbar-mobile"><i class="icon-tree5"></i></a></li>
            <li><a class="sidebar-mobile-main-toggle"><i class="icon-paragraph-justify3"></i></a></li>
        </ul>
    </div>
    <div class="navbar-collapse collapse" id="navbar-mobile">
        <ul class="nav navbar-nav">
            <li><a class="sidebar-control sidebar-main-toggle hidden-xs"><i class="icon-paragraph-justify3"></i></a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
            <li class="dropdown dropdown-user">
                <a class="dropdown-toggle" data-toggle="dropdown">
                    <img src="{{ legacy_asset('img/placeholder.jpg') }}" alt="">
                    <span>{{ ucfirst((string)session('adminName', 'Admin')) }}</span>
                    <i class="caret"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li><a href="#">My profile</a></li>
                    <li class="divider"></li>
                    <li><a href="{{ $logoutPrefix }}/admins/logout" title="Logout">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>
