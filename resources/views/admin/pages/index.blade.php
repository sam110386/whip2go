@extends('layouts.admin')

@section('title', $listTitle ?? 'Manage Static Pages')

@section('content')
    <h1>{{ $listTitle ?? 'Manage Static Pages' }}</h1>
    <div style="margin: 10px 0;">
        <a href="/admin/pages/add">Add New</a>
    </div>

    <form method="GET" action="/admin/pages/index" style="display:flex; gap:10px; align-items:center;">
        <select name="searchin">
            <option value="">All</option>
            @foreach(($options ?? []) as $key => $label)
                <option value="{{ $key }}" @selected(($fieldname ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="keyword" value="{{ $keyword ?? '' }}" placeholder="keyword">
        <button type="submit">Search</button>
    </form>

    <table border="1" cellpadding="6" cellspacing="0" width="100%" style="margin-top:12px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Code</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($pages ?? []) as $page)
                <tr>
                    <td>{{ $page->id }}</td>
                    <td>{{ $page->title }}</td>
                    <td>{{ $page->pagecode }}</td>
                    <td>{{ (int)($page->status ?? 0) === 1 ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <a href="/admin/pages/view/{{ $page->id }}">View</a> |
                        <a href="/admin/pages/add/{{ $page->id }}">Edit</a> |
                        <a href="/admin/pages/status/{{ $page->id }}/{{ (int)($page->status ?? 0) === 1 ? 1 : 0 }}">Toggle</a> |
                        <a href="/admin/pages/delete/{{ $page->id }}" onclick="return confirm('Delete page?')">Delete</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" align="center">No record found</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

