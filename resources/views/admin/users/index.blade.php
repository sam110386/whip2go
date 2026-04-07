@extends('admin.layouts.app')

@section('title', 'Manage Users')

@section('content')
    <h1>Manage Users</h1>

    <div style="margin: 10px 0;">
        <a href="/admin/users/add">Add User</a>
    </div>

    <form method="GET" action="/admin/users/index" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <label>
            Keyword
            <input type="text" name="keyword" value="{{ $keyword ?? '' }}">
        </label>
        <label>
            Status
            <select name="show">
                <option value="" @if(empty($show)) selected @endif>Any</option>
                <option value="Active" @if(($show ?? '') === 'Active') selected @endif>Active</option>
                <option value="Deactive" @if(($show ?? '') === 'Deactive') selected @endif>Inactive</option>
            </select>
        </label>
        <label>
            Type
            <select name="type">
                <option value="" @if(empty($type)) selected @endif>Any</option>
                <option value="1" @if(($type ?? '') === '1') selected @endif>Verified</option>
                <option value="2" @if(($type ?? '') === '2') selected @endif>Unverified</option>
                <option value="3" @if(($type ?? '') === '3') selected @endif>Renter</option>
                <option value="4" @if(($type ?? '') === '4') selected @endif>Driver</option>
                <option value="5" @if(($type ?? '') === '5') selected @endif>Dealer</option>
                <option value="6" @if(($type ?? '') === '6') selected @endif>Pending Dealer</option>
            </select>
        </label>
        <button type="submit">Apply</button>
    </form>

    <hr>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Verified</th>
                <th>Trash</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($users ?? []) as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->username }}</td>
                    <td>{{ $u->contact_number }}</td>
                    <td>{{ (int)($u->status ?? 0) === 1 ? 'Active' : 'Inactive' }}</td>
                    <td>{{ (int)($u->is_verified ?? 0) === 1 ? 'Yes' : 'No' }}</td>
                    <td>{{ (int)($u->trash ?? 0) === 1 ? 'Yes' : 'No' }}</td>
                    <td>
                        @php $encoded = base64_encode((string)$u->id); @endphp
                        <a href="/admin/users/view/{{ $encoded }}">View</a> |
                        <a href="/admin/users/add/{{ $encoded }}">Edit</a> |
                        <a href="/admin/users/status/{{ $encoded }}/{{ (int)($u->status ?? 0) === 1 ? 0 : 1 }}">Toggle Status</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" align="center">No record found</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

