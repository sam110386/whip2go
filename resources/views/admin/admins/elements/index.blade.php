@php
    $users ??= [];
    $limit ??= 50;
    $columns = [
        ['title' => '#', 'field' => 'id'],
        ['title' => 'Username', 'sortable' => false],
        ['title' => 'First Name', 'sortable' => false],
        ['title' => 'Last Name', 'sortable' => false],
        ['title' => 'Email', 'sortable' => false],
        ['title' => 'Contact#', 'sortable' => false],
        ['title' => 'Created', 'field' => 'created'],
        ['title' => 'Status', 'field' => 'status'],
        ['title' => 'Role', 'sortable' => false],
        ['title' => 'Actions', 'sortable' => false]
    ];
@endphp

<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', compact('columns'))
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        {{ data_get($user, 'id', '') }}
                    </td>
                    <td>
                        {{ data_get($user, 'username', '')  }}
                    </td>
                    <td>
                        {{ data_get($user, 'first_name', '')  }}
                    </td>
                    <td>
                        {{ data_get($user, 'last_name', '')  }}
                    </td>
                    <td>
                        {{data_get($user, 'email', '')   }}
                    </td>
                    <td>
                        {{ data_get($user, 'contact_number', '')  }}
                    </td>
                    <td>
                        {{ data_get($user, 'created', '')  }}
                    </td>
                    <td align="center">
                        @if(data_get($user, 'status', '') == 1)
                            <a href="{{ url('admin/admins/status/' . base64_encode(data_get($user, 'id')) . '/0') }}"
                                onclick="return confirm('Are you sure to update this User?')">
                                <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Active" title="Active">
                            </a>
                        @else
                            <a href="{{ url('admin/admins/status/' . base64_encode(data_get($user, 'id')) . '/1') }}"
                                onclick="return confirm('Are you sure to update this User?')">
                                <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Inactive" title="Inactive">
                            </a>
                        @endif
                    </td>
                    <td>
                        {{ data_get($user, 'role.name', '--') }}
                    </td>
                    <td class="action">
                        <a href="{{ url('admin/admins/add/' . base64_encode(data_get($user, 'id'))) }}" title="Edit">
                            <i class="glyphicon glyphicon-edit"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" align="center">No record found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit])