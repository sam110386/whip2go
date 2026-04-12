@extends('admin.layouts.app')

@section('title', 'Manage Lead Dealers')

@section('content')
    <h1>Manage Lead Dealers</h1>
    <p><a href="/cloud/linked_users/edit">Add User</a></p>
    <form method="get" action="/cloud/linked_users/index" style="margin-bottom:10px;">
        <label>Search
            <input type="text" name="Search[keyword]" value="{{ $keyword ?? '' }}">
        </label>
        <label>In
            <select name="Search[searchin]">
                <option value="All" @selected(($searchin ?? 'All') === 'All')>All</option>
                <option value="first_name" @selected(($searchin ?? '') === 'first_name')>First name</option>
                <option value="email" @selected(($searchin ?? '') === 'email')>Email</option>
                <option value="username" @selected(($searchin ?? '') === 'username')>Username</option>
            </select>
        </label>
        <label>Type
            <select name="Search[type]">
                <option value="" @selected(($type ?? '') === '')>All</option>
                <option value="1" @selected(($type ?? '') === '1')>Driver</option>
                <option value="2" @selected(($type ?? '') === '2')>Dealer</option>
            </select>
        </label>
        <label>Rows
            <select name="Record[limit]" onchange="this.form.submit()">
                @foreach ([25,50,100,200] as $opt)
                    <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Search</button>
    </form>

    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">ID</th>
                <th style="padding:6px;">Name</th>
                <th style="padding:6px;">Email</th>
                <th style="padding:6px;">Phone</th>
                <th style="padding:6px;">Type</th>
                <th style="padding:6px;">Status</th>
                <th style="padding:6px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $u)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $u->id }}</td>
                    <td style="padding:6px;">{{ trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) }}</td>
                    <td style="padding:6px;">{{ $u->email }}</td>
                    <td style="padding:6px;">{{ $u->contact_number }}</td>
                    <td style="padding:6px;">{{ !empty($u->is_dealer) ? 'Dealer' : (!empty($u->is_driver) ? 'Driver' : 'User') }}</td>
                    <td style="padding:6px;">{{ (int)$u->status }}</td>
                    <td style="padding:6px;">
                        <form method="post" action="/cloud/linked_users/view" style="display:inline;">
                            @csrf
                            <input type="hidden" name="userid" value="{{ base64_encode((string)$u->id) }}">
                            <button type="submit">View</button>
                        </form>
                        · <a href="/cloud/linked_users/edit/{{ base64_encode((string)$u->id) }}">Edit</a>
                        · <a href="/cloud/linked_users/ccindex/{{ base64_encode((string)$u->id) }}">Cards</a>
                        · <a href="/cloud/linked_users/dynamicfares/{{ base64_encode((string)$u->id) }}">Dynamic fares</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="padding:10px;">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $users->links() }}
@endsection

