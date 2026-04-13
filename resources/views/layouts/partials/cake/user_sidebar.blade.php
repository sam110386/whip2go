{{-- Port of Cake `app/View/Elements/left_dispacher.ctp` using Laravel-shared module arrays. --}}
@php
    $userModules = $userModules ?? [];
    $userSubModules = $userSubModules ?? [];
    $currentPath = legacy_path_normalize(request()->path());

    $normalizeMenuPath = static function (?string $url): string {
        if ($url === null || $url === '') {
            return '';
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $url;
        }

        return legacy_path_normalize($path);
    };
@endphp
<div class="sidebar-category sidebar-category-visible">
    <div class="category-content no-padding">
        <ul class="navigation navigation-main navigation-accordion">
            <li class="{{ $currentPath === legacy_path_normalize('dashboard/index') ? 'active' : '' }}">
                <a href="/dashboard/index"><i class="icon-home"></i><span>Dashboard</span></a>
            </li>
            @foreach($userModules as $module)
                @php
                    $mid = (int)($module['id'] ?? 0);
                    $linkName = $module['module'] ?? '';
                    $htmlId = $module['html_id'] ?? '';
                    $moduleUrl = trim((string)($module['module_url'] ?? ''), " \t\n\r\0\x0B");
                    $icon = $module['icon'] ?? '';
                    $href = $moduleUrl !== '' ? $moduleUrl : 'javascript:void(0)';
                    $subs = $userSubModules[$mid] ?? [];
                    $selfPath = $normalizeMenuPath($moduleUrl);
                    $childActive = false;
                    foreach ($subs as $sub) {
                        $su = trim((string)($sub['module_url'] ?? ''), " \t\n\r\0\x0B");
                        if ($su !== '' && $normalizeMenuPath($su) === $currentPath) {
                            $childActive = true;
                            break;
                        }
                    }
                    $liClass = ($selfPath !== '' && $selfPath === $currentPath) || $childActive ? 'active' : '';
                    $ulStyle = $childActive ? "style='display:block;'" : '';
                @endphp
                <li id="{{ $htmlId }}" class="{{ $liClass }}">
                    @if($moduleUrl === '')
                        <a href="javascript:void(0)"><i class="{{ $icon }}"></i><span>{{ $linkName }}</span></a>
                    @else
                        <a href="{{ $href }}"><i class="{{ $icon }}"></i><span>{{ $linkName }}</span></a>
                    @endif
                    @if(!empty($subs))
                        <ul class="hidden-ul" {!! $ulStyle !!}>
                            @foreach($subs as $subModule)
                                @php
                                    $subUrl = trim((string)($subModule['module_url'] ?? ''), " \t\n\r\0\x0B");
                                    $subPath = $normalizeMenuPath($subUrl);
                                    $subClass = $subPath !== '' && $subPath === $currentPath ? 'active btn-danger' : '';
                                @endphp
                                <li class="{{ $subClass }}">
                                    <a href="{{ $subUrl !== '' ? $subUrl : 'javascript:void(0)' }}"><i class="{{ $subModule['icon'] ?? '' }}"></i><span>{{ $subModule['module'] ?? '' }}</span></a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
