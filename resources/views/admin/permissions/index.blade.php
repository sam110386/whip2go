@extends('admin.layouts.app')

@section('title', 'Manage Permissions')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> - Roles</h4>
            </div>
            <div class="heading-elements">
                <a href="/admin/permissions/add" class="btn btn-danger btn-lg" style="float:right;">Add New</a>
            </div>
        </div>
    </div>

    <div class="row">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="GET" action="/admin/permissions/index" class="form-horizontal" style="margin-bottom: 20px;">
                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <label class="control-label" style="margin-left: 10px;">Keyword</label>
                    <div style="width: 250px;">
                        <input type="text" name="keyword" class="form-control" value="{{ $keyword ?? '' }}">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>

            <div class="panel panel-flat">
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
                    @forelse(($permissions ?? []) as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->title }}</td>
                            <td>{{ $p->pagecode }}</td>
                            <td>{{ (int)($p->status ?? 0) === 1 ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <a href="/admin/permissions/view/{{ $p->id }}" title="View"><i class="icon-clipboard3"></i></a>
                                <a href="/admin/permissions/add/{{ $p->id }}" title="Edit"><i class="icon-pencil"></i></a>
                                <a href="/admin/permissions/status/{{ $p->id }}/{{ (int)($p->status ?? 0) === 1 ? 0 : 1 }}" title="Toggle Status"><i class="icon-sync"></i></a>
                                <a href="/admin/permissions/delete/{{ $p->id }}" onclick="return confirm('Delete permission?')" title="Delete"><i class="icon-trash"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" align="center">No record found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.dispacher.paging_box', ['paginator' => $permissions, 'limit' => $limit ?? 50])
        </div>
    </div>
@endsection
