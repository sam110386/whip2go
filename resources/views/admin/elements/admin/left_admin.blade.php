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
                    $hasSubmodules = isset($adminSubModules[$moduleId]);
                    $isActive = request()->is($module['module_url']) && !$hasSubmodules ?? false;
                @endphp

                <li id="{{ $module['html_id'] }}" class="{{ $isActive ? 'active' : '' }}" {{ $moduleId }}>
                    {{-- @if ($moduleId == 17)
                        {{ dd(empty($module['module_url']) || $module['module_url'] === '***') }}
                    @endif --}}
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
                            @foreach ($adminSubModules[$moduleId] as $subModule)
                                @php
                                    $subActive = request()->is(trim($subModule['module_url'], '/'));
                                @endphp
                                <li class="{{ $subActive ? 'active btn-danger' : '' }}">
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
