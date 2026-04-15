@extends('admin.layouts.app')

@section('title', 'Staff Users')

@section('content')
    <h1>Search — Admin Staff</h1>
    <p style="float:right;"><a href="{{ $basePath }}/add">Add New</a></p>
    <div style="clear:both;"></div>

    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p style="color:red;">{{ session('error') }}</p>
    @endif

    <form method="get" action="{{ $basePath }}/index" style="margin-bottom:12px;">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
            <label>Keyword<br>
                <input type="text" name="keyword" value="{{ $keyword ?? '' }}" maxlength="50" style="width:200px;">
            </label>
            <label>Search In<br>
                <select name="searchin" class="form-control" style="min-width:140px;">
                    <option value="">Select..</option>
                    @foreach(($options ?? []) as $k => $label)
                        <option value="{{ $k }}" @selected(($fieldname ?? '') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status<br>
                <select name="show" style="min-width:140px;">
                    <option value="">Select..</option>
                    <option value="Active" @selected(($show ?? '') === 'Active')>Active</option>
                    <option value="Deactive" @selected(($show ?? '') === 'Deactive')>Inactive</option>
                </select>
            </label>
            <button type="submit" class="btn btn-primary">Apply</button>
        </div>
    </form>

    <form method="post" action="{{ $basePath }}/multiplAction" onsubmit="return confirm('Apply bulk action to selected staff?');">
        <input type="hidden" name="Search[keyword]" value="{{ $keyword ?? '' }}">
        <input type="hidden" name="Search[searchin]" value="{{ $fieldname ?? '' }}">
        <input type="hidden" name="Search[show]" value="{{ $show ?? '' }}">

        <label style="margin-right:10px;">Rows
            <select name="Record[limit]" onchange="this.form.submit()">
                @foreach ([25,50,100,200] as $opt)
                    <option value="{{ $opt }}" @selected((int)($limit ?? 25) === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>

        <label>Bulk
            <select name="User[status]">
                <option value="">—</option>
                <option value="active">Activate</option>
                <option value="inactive">Deactivate</option>
                <option value="del">Delete</option>
            </select>
        </label>
        <button type="submit">Go</button>

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
                    @forelse(($users ?? []) as $u)
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

    @include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit ?? 50])
@endsection
