@extends('admin.layouts.app')

@section('title', $listTitle ?? 'View User')

@section('content')
    <h1>{{ $listTitle ?? 'View User' }}</h1>

    @if(empty($user))
        <p>No user found.</p>
    @else
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <tbody>
                <tr><th align="left">ID</th><td>{{ $user->id }}</td></tr>
                <tr><th align="left">First Name</th><td>{{ $user->first_name }}</td></tr>
                <tr><th align="left">Last Name</th><td>{{ $user->last_name }}</td></tr>
                <tr><th align="left">Email</th><td>{{ $user->email }}</td></tr>
                <tr><th align="left">Username</th><td>{{ $user->username }}</td></tr>
                <tr><th align="left">Contact</th><td>{{ $user->contact_number }}</td></tr>
                <tr><th align="left">Status</th><td>{{ (int)($user->status ?? 0) === 1 ? 'Active' : 'Inactive' }}</td></tr>
                <tr><th align="left">Verified</th><td>{{ (int)($user->is_verified ?? 0) === 1 ? 'Yes' : 'No' }}</td></tr>
                <tr><th align="left">Driver</th><td>{{ (int)($user->is_driver ?? 0) === 1 ? 'Yes' : 'No' }}</td></tr>
                <tr><th align="left">Dealer</th><td>{{ (int)($user->is_dealer ?? 0) }}</td></tr>
            </tbody>
        </table>
    @endif

    <div style="margin-top:12px;">
        <a href="/admin/users/index">Back</a>
    </div>
@endsection

