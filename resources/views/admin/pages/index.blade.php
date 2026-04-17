@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Manage Static Pages')

@section('content')
    <h1>{{ $listTitle ?? 'Manage Static Pages' }}</h1>
    <div style="margin: 10px 0;">
        <a href="/admin/pages/add" class="btn btn-primary">Add New</a>
    </div>

    <form method="GET" action="/admin/pages/index" style="display:flex; gap:10px; align-items:center;">
        <select name="searchin" class="form-control" style="width: auto;">
            <option value="">All</option>
            @foreach(($options ?? []) as $key => $label)
                <option value="{{ $key }}" @selected(($fieldname ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="keyword" value="{{ $keyword ?? '' }}" class="form-control" style="width: auto;" placeholder="keyword">
        <button type="submit" class="btn btn-default">Search</button>
    </form>

    <div class="panel panel-flat" style="margin-top:20px;">
        <div class="table-responsive">
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', ['columns' => [
                            ['field' => 'id', 'title' => 'ID'],
                            ['field' => 'title', 'title' => 'Title'],
                            ['field' => 'pagecode', 'title' => 'Code'],
                            ['field' => 'status', 'title' => 'Status'],
                            ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                        ]])
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
                                <a href="/admin/pages/view/{{ $page->id }}" title="View"><i class="icon-clipboard3"></i></a>
                                <a href="/admin/pages/add/{{ $page->id }}" title="Edit"><i class="icon-pencil"></i></a>
                                <a href="/admin/pages/status/{{ $page->id }}/{{ (int)($page->status ?? 0) === 1 ? 0 : 1 }}" title="Toggle Status"><i class="icon-sync"></i></a>
                                <a href="/admin/pages/delete/{{ $page->id }}" onclick="return confirm('Delete page?')" title="Delete"><i class="icon-trash"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" align="center">No record found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.dispacher.paging_box', ['paginator' => $pages, 'limit' => $limit ?? 50])
@endsection
