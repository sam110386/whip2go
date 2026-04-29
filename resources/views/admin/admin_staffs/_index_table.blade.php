@php
    $users ??= [];
    $limit ??= 50;
    $basePath ??= '/admin/admin_staffs';
@endphp

<form method="post" action="{{ $basePath }}/multiplAction" onsubmit="return confirm('Apply bulk action to selected staff?');">
    @csrf
    <input type="hidden" name="Search[keyword]" value="{{ request('keyword', '') }}">
    <input type="hidden" name="Search[searchin]" value="{{ request('searchin', '') }}">
    <input type="hidden" name="Search[show]" value="{{ request('show', '') }}">

    <div class="row pb-10">
        <div class="col-md-3">
            <label style="margin-bottom:0;">Rows</label>
            <select name="Record[limit]" class="form-control ajax-limit">
                @foreach ([25,50,100,200] as $opt)
                    <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label style="margin-bottom:0;">Bulk Action</label>
            <select name="User[status]" class="form-control">
                <option value="">—</option>
                <option value="active">Activate</option>
                <option value="inactive">Deactivate</option>
                <option value="del">Delete</option>
            </select>
        </div>
        <div class="col-md-2">
            <label style="margin-bottom:0;">&nbsp;</label>
            <button type="submit" class="btn btn-primary">Go</button>
        </div>
    </div>

    <div class="table-responsive">
        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['field' => 'checkbox', 'title' => '', 'sortable' => false],
                        ['field' => 'id', 'title' => '#'],
                        ['field' => 'username', 'title' => 'Username'],
                        ['field' => 'first_name', 'title' => 'First Name'],
                        ['field' => 'last_name', 'title' => 'Last Name'],
                        ['field' => 'email', 'title' => 'Email'],
                        ['field' => 'contact_number', 'title' => 'Contact#'],
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
                        <td><input type="checkbox" name="select[]" value="{{ $u->id }}"></td>
                        <td>{{ $u->id }}</td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->first_name }}</td>
                        <td>{{ $u->last_name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->contact_number }}</td>
                        <td>{{ $u->created }}</td>
                        <td align="center">
                            @if((int)$u->status === 1)
                                <a href="{{ $basePath }}/status/{{ base64_encode((string)$u->id) }}/0" onclick="return confirm('Deactivate this user?');">Active</a>
                            @else
                                <a href="{{ $basePath }}/status/{{ base64_encode((string)$u->id) }}/1" onclick="return confirm('Activate this user?');">Inactive</a>
                            @endif
                        </td>
                        <td>{{ $u->role_name ?? '--' }}</td>
                        <td>
                            <a href="{{ $basePath }}/add/{{ base64_encode((string)$u->id) }}">Edit</a>
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
