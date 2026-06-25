@php
    $users ??= [];
@endphp

<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
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
                ]])
            </tr>
        </thead>
        <tbody>
            @forelse($users as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->username }}</td>
                    <td>{{ $u->first_name }}</td>
                    <td>{{ $u->last_name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->contact_number }}</td>
                    <td>{{ $u->created }}</td>
                    <td align="center">
                        @if((int)$u->status === 1)
                            <a href="/admin/admins/status/{{ base64_encode((string)$u->id) }}/0" onclick="return confirm('Are you sure to update this User?')">
                                <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Active" title="Active">
                            </a>
                        @else
                            <a href="/admin/admins/status/{{ base64_encode((string)$u->id) }}/1" onclick="return confirm('Are you sure to update this User?')">
                                <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Inactive" title="Inactive">
                            </a>
                        @endif
                    </td>
                    <td>{{ $u->role_name ?? '--' }}</td>
                    <td class="action">
                        <a href="/admin/admins/add/{{ base64_encode((string)$u->id) }}" title="Edit">
                            <i class="glyphicon glyphicon-edit"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" align="center">No record found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>