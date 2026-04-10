<div class="sidebar-category sidebar-category-visible">
    <div class="category-content no-padding">
        <ul class="navigation navigation-main navigation-accordion">

            <li class="start {{ request()->is('admin/homes/dashboard') ? 'active' : '' }}">
                <a href="{{ url('admin/homes/dashboard/') }}">
                    <i class="fa fa-angle-right"></i>
                    <i class="icon-home"></i>
                    <span class="title">{{ 'Dashboard' }}</span>
                </a>
            </li>

            @foreach ($adminModules as $module)
                @php
                    $moduleId = $module['id'];
                    $subModules = $adminSubModules[$moduleId] ?? [];
                    $hasSubmodules = !empty($subModules);
                    $patterns = [];
                    if (!empty($module['module_url']) && $module['module_url'] !== '***') {
                        $url = trim($module['module_url'], '/');
                        $patterns[] = $url;
                        $patterns[] = "{$url}/*";
                    }

                    foreach ($subModules as $subModule) {
                        $subUrl = trim($subModule['module_url'], '/');
                        $patterns[] = $subUrl;
                        $patterns[] = "{$subUrl}/*";
                    }

                    $isActive = !empty($patterns) && request()->is($patterns);
                @endphp

                <li id="{{ $module['html_id'] }}" class="{{ $isActive ? 'active' : '' }}" data-module-id="{{ $moduleId }}">
                    @if (empty($module['module_url']) || $module['module_url'] === '***')
                        <a href="javascript:void(0)">
                            <i class="{{ $module['icon'] }}"></i>
                            <span>{{ $module['module'] }}</span>
                        </a>
                    @else
                        <a href="{{ url($module['module_url']) }}">
                            <i class="{{ $module['icon'] }}"></i>
                            <span>{{ $module['module'] }}</span>
                        </a>
                    @endif

                    @if ($hasSubmodules)
                        <ul class="hidden-ul" style="{{ $isActive ? 'display:block;' : '' }}">
                            @foreach ($subModules as $subModule)
                                @php
                                    $subUrl = trim($subModule['module_url'], '/');
                                    $subActive = request()->is($subUrl) || request()->is($subUrl . '/*');
                                @endphp
                                <li class="{{ $subActive ? 'active' : '' }}">
                                    <a href="{{ url($subModule['module_url']) }}">
                                        <i class="{{ $subModule['icon'] }}"></i>
                                        <span>{{ $subModule['module'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>