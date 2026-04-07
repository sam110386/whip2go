@extends('admin.layouts.app')

@section('title', $listTitle ?? 'View Page')

@section('content')
    <h1>{{ $listTitle ?? 'View Page' }}</h1>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <tr><th width="180">ID</th><td>{{ data_get($page, 'id') }}</td></tr>
        <tr><th>Title</th><td>{{ data_get($page, 'title') }}</td></tr>
        <tr><th>Page Code</th><td>{{ data_get($page, 'pagecode') }}</td></tr>
        <tr><th>Status</th><td>{{ (int)data_get($page, 'status', 0) === 1 ? 'Active' : 'Inactive' }}</td></tr>
        <tr><th>Meta Title</th><td>{{ data_get($page, 'meta_title') }}</td></tr>
        <tr><th>Meta Description</th><td>{{ data_get($page, 'meta_description') }}</td></tr>
        <tr><th>Meta Keyword</th><td>{{ data_get($page, 'meta_keyword') }}</td></tr>
        <tr><th>Description</th><td>{!! data_get($page, 'description') !!}</td></tr>
    </table>
    <div style="margin-top:10px;">
        <a href="/admin/pages/index">Back</a>
    </div>
@endsection

