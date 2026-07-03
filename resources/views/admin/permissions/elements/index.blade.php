@php
    $limit ??= 50;
    $permissions ??= [];
@endphp

<table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th align="center" style="text-align:center;">ID</th>
            <th align="center" style="text-align:center;">Name</th>
            <th align="center" style="text-align:center;">Permission</th>
            <th align="center" style="text-align:center;">Updated</th>
            <th align="center" style="text-align:center;">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($permissions as $permission)
            <tr class="cls_{{ data_get($permission, 'id', '') }}">
                <td align="center">
                    {{ data_get($permission, 'id', '')}}
                </td>
                <td align="center">
                    {{ data_get($permission, 'name', '')  }}
                </td>
                <td align="center">
                    {{ ucfirst(data_get($permission, 'type', ''))}}
                </td>
                <td align="center">
                    {{ data_get($permission, 'updated_at', '')  }}
                </td>
                <td align="center">
                    <a href="{{ url('admin/permissions/add/' . data_get($permission, 'id', '')) }}" title="Edit">
                        <i class='glyphicon glyphicon-edit'></i>
                    </a>
                    &nbsp;
                    <a href="{{ url('admin/permissions/delete/' . data_get($permission, 'id', '')) }}" title="Delete">
                        <i class='glyphicon glyphicon-trash'></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" align="center">No record found</td>
            </tr>
        @endforelse
    </tbody>
</table>

@include('partials.dispacher.paging_box', ['paginator' => $permissions, 'limit' => $limit])