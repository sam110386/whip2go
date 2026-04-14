@extends('layouts.admin')

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
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Paystub</th>
                        <th>Plaid user ID</th>
                        <th>Link token</th>
                        <th>Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($plaids as $row)
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
                            <td colspan="6">No plaid_users rows for this user.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
