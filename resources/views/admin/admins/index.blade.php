@extends('layouts.admin')

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

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Contact#</th>
                <th>Created</th>
                <th>Status</th>
                <th>Role</th>
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
@endsection

