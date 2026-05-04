@php
    $users ??= [];
    $limit ??= 50;
@endphp

<form method="post" action="/admin/admin_staffs/multiplAction" onsubmit="return confirm('Apply bulk action to selected staff?');">
    @csrf
    <input type="hidden" name="Search[keyword]" value="{{ request('keyword', '') }}">
    <input type="hidden" name="Search[searchin]" value="{{ request('searchin', '') }}">
    <input type="hidden" name="Search[show]" value="{{ request('show', '') }}">

    <div class="table-responsive">
        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['field' => 'select_all', 'title' => '<input type="checkbox" id="selectAllChildCheckboxs" value="1">', 'sortable' => false, 'html' => true],
                        ['field' => 'id', 'title' => '#'],
                        ['field' => 'username', 'title' => 'Username', 'sortable' => false],
                        ['field' => 'first_name', 'title' => 'First Name', 'sortable' => false],
                        ['field' => 'last_name', 'title' => 'Last Name', 'sortable' => false],
                        ['field' => 'email', 'title' => 'Email', 'sortable' => false],
                        ['field' => 'contact_number', 'title' => 'Contact#', 'sortable' => false],
                        ['field' => 'created', 'title' => 'Created'],
                        ['field' => 'status', 'title' => 'Status'],
                        ['field' => 'role_id', 'title' => 'Role', 'sortable' => false],
                        ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                    ]])
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td><input type="checkbox" name="select[{{ $u->id }}]" value="{{ $u->id }}" class="select-item"></td>
                        <td>{{ $u->id }}</td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->first_name }}</td>
                        <td>{{ $u->last_name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->contact_number }}</td>
                        <td>{{ $u->created }}</td>
                        <td align="center">
                            @if((int)$u->status === 1)
                                <a href="/admin/admin_staffs/status/{{ base64_encode((string)$u->id) }}/0" onclick="return confirm('Deactivate this user?');">
                                    <img src="/img/green2.jpg" alt="Status" title="Status">
                                </a>
                            @else
                                <a href="/admin/admin_staffs/status/{{ base64_encode((string)$u->id) }}/1" onclick="return confirm('Activate this user?');">
                                    <img src="/img/red3.jpg" alt="Status" title="Status">
                                </a>
                            @endif
                        </td>
                        <td>{{ $u->role_name ?? '--' }}</td>
                        <td>
                            <a href="/admin/admin_staffs/add/{{ base64_encode((string)$u->id) }}">
                                <i class='glyphicon glyphicon-edit'></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" align="center">No record found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

@include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit])
