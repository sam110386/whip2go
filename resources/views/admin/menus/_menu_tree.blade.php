<ol class="dd-list">
    @foreach(($nodes ?? []) as $node)
        @php
            $m = $node['AdminModule'] ?? [];
            $children = $node['children'] ?? [];
            $id = $m['id'] ?? null;
            $icon = $m['icon'] ?? '';
            $moduleName = $m['module'] ?? '';
            $moduleUrl = $m['module_url'] ?? '';
        @endphp

        @if(!empty($id))
            <li class="dd-item" data-id="{{ $id }}">
                <div class="dd-handle">
                    <i class="{{ $icon }}"></i>&nbsp;<strong>{{ $moduleName }}</strong>
                    &nbsp;&nbsp;&nbsp;
                    <a href="{{ !empty($moduleUrl) ? $moduleUrl : 'javascript:void(0);' }}" class="dd-nodrag">
                        {{ $moduleUrl }}
                    </a>
                    <span class="pull-right dd-nodrag">
                        <a href="javascript:void(0);" data-id="{{ $id }}" class="tree_branch_edit">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="javascript:void(0);" data-id="{{ $id }}" class="tree_branch_delete">
                            <i class="fa fa-trash"></i>
                        </a>
                    </span>
                </div>

                @if(!empty($children))
                    @include('admin.menus._menu_tree', ['nodes' => $children])
                @endif
            </li>
        @endif
    @endforeach
</ol>

