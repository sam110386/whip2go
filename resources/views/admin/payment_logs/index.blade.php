@extends('admin.layouts.app')

@section('title', 'Payment Logs')

@section('content')
    <div class="page-header page-header-default">
        <div class="page-header-content">
            <div class="page-title">
                <h1>Payment logs</h1>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="panel panel-flat">
            <div class="panel-body">
                <form method="get" action="{{ str_contains(request()->path(), '/cloud/') ? '/cloud/payment_logs/index' : '/admin/payment_logs/index' }}" class="form-inline">
                    <div class="form-group mr-2">
                        <label>From</label>
                        <input type="date" name="Search[date_from]" class="form-control" value="{{ $dateFrom ?? '' }}">
                    </div>
                    <div class="form-group mr-2">
                        <label>To</label>
                        <input type="date" name="Search[date_to]" class="form-control" value="{{ $dateTo ?? '' }}">
                    </div>
                    <div class="form-group mr-2">
                        <label>Keyword</label>
                        <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword ?? '' }}">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ str_contains(request()->path(), '/cloud/') ? '/cloud/payment_logs/index' : '/admin/payment_logs/index' }}" class="btn btn-default">Reset</a>
                </form>
            </div>

            <div class="table-responsive">
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', ['columns' => [
                                ['field' => 'id', 'title' => 'ID'],
                                ['field' => 'user_id', 'title' => 'User'],
                                ['field' => 'transaction_id', 'title' => 'Txn ID'],
                                ['field' => 'reference_id', 'title' => 'Reference'],
                                ['field' => 'message', 'title' => 'Message'],
                                ['field' => 'created', 'title' => 'Created']
                            ]])
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($rows ?? []) as $r)
                            <tr>
                                <td>{{ $r->id }}</td>
                                <td>{{ $r->user_id ?? '' }}</td>
                                <td>{{ $r->transaction_id ?? '' }}</td>
                                <td>{{ $r->reference_id ?? '' }}</td>
                                <td>{{ $r->message ?? '' }}</td>
                                <td>{{ $r->created ?? '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" align="center">No logs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @include('partials.dispacher.paging_box', ['paginator' => $rows, 'limit' => $limit ?? 50])
    </div>
@endsection
