<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
    <thead>
        <tr>
            @php
                $columns = [
                    ['field' => 'id', 'title' => 'ID', 'style' => 'text-align:center;', 'sortable' => true],
                    ['field' => 'slug', 'title' => 'Slug', 'style' => 'text-align:center;', 'sortable' => true],
                    ['field' => 'name', 'title' => 'Name', 'style' => 'text-align:center;', 'sortable' => true],
                    ['field' => 'permission', 'title' => 'Permission', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'created_at', 'title' => 'Created', 'style' => 'text-align:center;', 'sortable' => true],
                    ['field' => 'updated_at', 'title' => 'Updated', 'style' => 'text-align:center;', 'sortable' => true],
                    ['field' => 'action', 'title' => 'Action', 'style' => 'text-align:center;', 'sortable' => false],
                ];
            @endphp
            @include('partials.dispacher.sortable_header', ['columns' => $columns])
        </tr>
    </thead>

    <tbody>
        @foreach ($roles as $role)
            <tr class="cls_{{$role->id}}">
                <td class="text-center">
                    {{ $role->id }}
                </td>
                <td class="text-center">
                    {{ $role->slug }}
                </td>
                <td class="text-center">
                    {{ $role->name }}
                </td>
                <td class="text-center">
                    @forelse ($role->permissions as $permission)
                        <div style='margin-bottom: 5px;' class='label label-success'>{{ $permission->name ?? '-' }}</div>
                    @empty
                        <div style='margin-bottom: 5px;' class='label label-danger'>No Permissions</div>
                    @endforelse
                </td>
                <td class="text-center">
                    {{ $role->created_at }}
                </td>
                <td class="text-center">
                    {{ $role->updated_at }}
                </td>
                <td class="text-center">
                    <a href="/admin/roles/add/{{ $role->id }}"><i class='glyphicon glyphicon-edit'></i></a>
                    &nbsp;
                    <a href="/admin/roles/delete/{{ $role->id }}"><i class='glyphicon glyphicon-trash'></i></a>
                </td>
            </tr>
        @endforeach
        <tr>
            <td colspan="8" class="text-center">
                @include('partials.dispacher.paging_box', ['paginator' => $roles, 'limit' => $limit, 'url' => $url, 'update' => 'listing'])
            </td>
        </tr>
    </tbody>
</table>