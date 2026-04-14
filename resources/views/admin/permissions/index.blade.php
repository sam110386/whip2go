@extends('layouts.admin')

@section('title', 'Manage Permissions')

@section('content')
    <h1>Manage Permissions</h1>

    <div style="margin: 10px 0;">
        <a href="/admin/permissions/add">Add Permission</a>
    </div>

    <form method="GET" action="/admin/permissions/index" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <label>
            Keyword
            <input type="text" name="keyword" value="{{ $keyword ?? '' }}">
        </label>
        <button type="submit">Apply</button>
    </form>

    <hr>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Updated</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @forelse(($permissions ?? []) as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ ucfirst($p->type ?? '') }}</td>
                <td>{{ $p->updated_at ?? '' }}</td>
                <td>
                    <a href="/admin/permissions/add/{{ $p->id }}">Edit</a>
                    |
                    <a href="/admin/permissions/delete/{{ $p->id }}" onclick="return confirm('Delete this permission?')">Delete</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" align="center">No permissions found</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection

