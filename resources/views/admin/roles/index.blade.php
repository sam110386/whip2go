@extends('admin.layouts.app')

@section('title', 'Manage Roles')

@section('content')
    <h1>Manage Roles</h1>

    <div style="margin: 10px 0;">
        <a href="/admin/roles/add">Add New</a>
    </div>

    <form method="GET" action="/admin/roles/index" style="display:flex; gap:10px; align-items:center; margin-bottom: 14px;">
        <label>
            Keyword
            <input type="text" name="keyword" value="{{ $keyword ?? '' }}">
        </label>
        <button type="submit">Search</button>
    </form>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Slug</th>
                <th>Name</th>
                <th>Permissions</th>
                <th>Parent</th>
                <th>Created</th>
                <th>Updated</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($roles ?? []) as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->slug }}</td>
                    <td>{{ $r->name }}</td>
                    <td>{{ $r->permission_names ?? '-' }}</td>
                    <td>{{ (int)($r->parent_id ?? 0) }}</td>
                    <td>{{ $r->created_at ?? '' }}</td>
                    <td>{{ $r->updated_at ?? '' }}</td>
                    <td>
                        <a href="/admin/roles/admin_add/{{ $r->id }}">Edit</a>
                        &nbsp;|&nbsp;
                        <a href="/admin/roles/admin_delete/{{ $r->id }}" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" align="center">No roles found</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

