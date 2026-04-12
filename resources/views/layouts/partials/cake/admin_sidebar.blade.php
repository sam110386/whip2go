{{-- Port of Cake `app/View/Elements/admin/left_admin.ctp` (simplified active state vs request path). --}}
@php
    $adminModules = $adminModules ?? [];
    $adminSubModules = $adminSubModules ?? [];
    $currentPath = legacy_path_normalize(request()->path());

    $hrefPath = static function (string $href): string {
        $path = parse_url($href, PHP_URL_PATH);

        return legacy_path_normalize(is_string($path) ? $path : $href);
    };
@endphp
<div class="sidebar-category sidebar-category-visible">
    <div class="category-content no-padding">
        <ul class="navigation navigation-main navigation-accordion">
            <li class="start {{ str_contains($currentPath, 'homes/dashboard') ? 'active' : '' }}">
                <a href="{{ legacy_admin_menu_href('/admin/homes/dashboard') }}">
                    <i class="fa fa-angle-right"></i> <i class="icon-home"></i>
                    <span class="title">Dashboard</span>
                </a>
            </li>
            @foreach($adminModules as $module)
                @php
                    $mid = (int)($module['id'] ?? 0);
                    $linkName = $module['module'] ?? '';
                    $htmlId = $module['html_id'] ?? '';
                    $moduleUrl = trim((string)($module['module_url'] ?? ''), " \t\n\r\0\x0B");
                    $icon = $module['icon'] ?? '';
                    $resolved = $moduleUrl !== '' ? legacy_admin_menu_href($moduleUrl) : 'javascript:void(0)';
                    $subs = $adminSubModules[$mid] ?? [];
                    $selfActive = $moduleUrl !== '' && $hrefPath($resolved) === $currentPath;
                    $childActive = false;
                    foreach ($subs as $sub) {
                        $su = trim((string)($sub['module_url'] ?? ''), " \t\n\r\0\x0B");
                        if ($su !== '' && $hrefPath(legacy_admin_menu_href($su)) === $currentPath) {
                            $childActive = true;
                            break;
                        }
                    }
                    $liClass = $selfActive || $childActive ? 'active' : '';
                    $ulStyle = $childActive ? "style='display:block;'" : '';
                @endphp
                <li id="{{ $htmlId }}" class="{{ $liClass }}">
                    @if($moduleUrl === '')
                        <a href="javascript:void(0)"><i class="{{ $icon }}"></i><span>{{ $linkName }}</span></a>
                    @else
                        <a href="{{ $resolved }}"><i class="{{ $icon }}"></i><span>{{ $linkName }}</span></a>
                    @endif
                    @if(!empty($subs))
                        <ul class="hidden-ul" {!! $ulStyle !!}>
                            @foreach($subs as $subModule)
                                @php
                                    $subUrl = trim((string)($subModule['module_url'] ?? ''), " \t\n\r\0\x0B");
                                    $subResolved = $subUrl !== '' ? legacy_admin_menu_href($subUrl) : 'javascript:void(0)';
                                    $subClass = $subUrl !== '' && $hrefPath($subResolved) === $currentPath ? 'active btn-danger' : '';
                                @endphp
                                <li class="{{ $subClass }}">
                                    <a href="{{ $subResolved }}"><i class="{{ $subModule['icon'] ?? '' }}"></i><span>{{ $subModule['module'] ?? '' }}</span></a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
