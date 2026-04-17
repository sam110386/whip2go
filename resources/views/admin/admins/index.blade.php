@extends('admin.layouts.app')

@section('title', 'Admin Users')

@section('content')
    <h1>Manage Admin Users</h1>

    <div style="margin: 10px 0;">
        <a href="/admin/admins/add">Add New User</a>
    </div>

    <form method="GET" action="/admin/admins/index" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <label>
            Keyword
            <input type="text" name="keyword" value="{{ $keyword ?? '' }}">
        </label>
        <label>
            Search In
            <select name="searchin">
                @foreach(($options ?? []) as $k => $label)
                    <option value="{{ $k }}" @if(($searchin ?? '') === $k) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Status
            <select name="showtype">
                <option value="" @if(empty($showtype)) selected @endif>Any</option>
                <option value="Active" @if(($showtype ?? '') === 'Active') selected @endif>Active</option>
                <option value="Deactive" @if(($showtype ?? '') === 'Deactive') selected @endif>Inactive</option>
            </select>
        </label>
        <button type="submit">Apply</button>
    </form>

    <hr>

    <div class="panel panel-flat">
        <div class="table-responsive">
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', ['columns' => [
                            ['field' => 'id', 'title' => 'ID'],
                            ['field' => 'username', 'title' => 'Username'],
                            ['field' => 'first_name', 'title' => 'First Name'],
                            ['field' => 'last_name', 'title' => 'Last Name'],
                            ['field' => 'email', 'title' => 'Email'],
                            ['field' => 'contact_number', 'title' => 'Contact#'],
                            ['field' => 'created', 'title' => 'Created'],
                            ['field' => 'status', 'title' => 'Status'],
                            ['field' => 'role_id', 'title' => 'Role', 'sortable' => false]
                        ]])
                    </tr>
                </thead>
                <tbody>
                    @forelse(($users ?? []) as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td>{{ $u->username }}</td>
                            <td>{{ $u->first_name }}</td>
                            <td>{{ $u->last_name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->contact_number }}</td>
                            <td>{{ $u->created }}</td>
                            <td>{{ (int)$u->status === 1 ? 'Active' : 'Inactive' }}</td>
                            <td>{{ $u->role_name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" align="center">No record found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit ?? 50])
@endsection

