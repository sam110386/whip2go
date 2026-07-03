@php
    $roles ??= [];
    $limit ??= 50;
@endphp

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
    <thead>
        <tr>
            <th align="center" style="text-align:center;">ID</th>
            <th align="center" style="text-align:center;">Slug</th>
            <th align="center" style="text-align:center;">Name</th>
            <th align="center" style="text-align:center;">Permission</th>
            <th align="center" style="text-align:center;">Created </th>
            <th align="center" style="text-align:center;">Updated</th>
            <th align="center" style="text-align:center;">Action</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($roles as $role)
            <tr class="cls_{{data_get($role, 'id', '')}}">
                <td class="text-center">
                    {{ data_get($role, 'id', '') }}
                </td>
                <td class="text-center">
                    {{ data_get($role, 'slug', '') }}
                </td>
                <td class="text-center">
                    {{ data_get($role, 'name', '') }}
                </td>
                <td class="text-center">
                    @php
                        $permissions = data_get($role, 'permissions', []);
                    @endphp
                    @forelse ($permissions as $permission)
                        <div style='margin-bottom: 5px;' class='label label-success'>
                            {{ data_get($permission, 'name', '')}}
                        </div>
                    @empty
                        <div style='margin-bottom: 5px;' class='label label-danger'>
                            No Permissions
                        </div>
                    @endforelse
                </td>
                <td class="text-center">
                    {{ data_get($role, 'created_at', '-') }}
                </td>
                <td class="text-center">
                    {{ data_get($role, 'updated_at', '') }}
                </td>
                <td class="text-center">
                    <a href="{{ url('admin/roles/add/' . data_get($role, 'id', '')) }}">
                        <i class='glyphicon glyphicon-edit'></i>
                    </a>
                    &nbsp;
                    <a href="{{ url('admin/roles/delete/' . data_get($role, 'id', '')) }}">
                        <i class='glyphicon glyphicon-trash'></i>
                    </a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@include('partials.dispacher.paging_box', ['paginator' => $roles, 'limit' => $limit])