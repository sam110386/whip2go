@extends('admin.layouts.app')

@section('title', 'Dynamic Fares')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Dynamic</span> Fares (user #{{ $userId }})
            </h4>
        </div>
        <div class="heading-elements">
            <a href="/cloud/linked_users/index" class="btn btn-default">Return</a>
        </div>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-body">
            <table class="table table-responsive">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Key</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->key ?? ($r->name ?? '-') }}</td>
                            <td>{{ $r->value ?? ($r->amount ?? '-') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No rows.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
