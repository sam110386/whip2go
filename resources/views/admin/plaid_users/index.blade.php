@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Plaid users')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><span class="text-semibold">Plaid</span> — Connected accounts</h4>
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-body">
            <p>User ID: <strong>{{ $userid }}</strong></p>
            <div class="table-responsive">
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive table-bordered">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', ['columns' => [
                                ['field' => 'id', 'title' => 'ID'],
                                ['field' => 'user_id', 'title' => 'User ID'],
                                ['field' => 'paystub', 'title' => 'Paystub'],
                                ['field' => 'plaid_user_id', 'title' => 'Plaid user ID'],
                                ['field' => 'link_token', 'title' => 'Link token'],
                                ['field' => 'created', 'title' => 'Created']
                            ]])
                        </tr>
                    </thead>
                    <tbody>
                    @forelse(($plaids ?? []) as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->user_id }}</td>
                            <td>{{ $row->paystub ?? '' }}</td>
                            <td>{{ $row->plaid_user_id ?? '' }}</td>
                            <td>{{ $row->link_token ?? '' }}</td>
                            <td>{{ $row->created ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" align="center">No plaid_users rows for this user.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($plaids) && method_exists($plaids, 'links'))
                @include('partials.dispacher.paging_box', ['paginator' => $plaids, 'limit' => $limit ?? 50])
            @endif
        </div>
    </div>
@endsection
