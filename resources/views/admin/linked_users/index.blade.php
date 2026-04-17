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

    <div class="panel panel-flat">
        <div class="table-responsive">
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', ['columns' => [
                            ['field' => 'id', 'title' => 'ID'],
                            ['field' => 'first_name', 'title' => 'Name'],
                            ['field' => 'email', 'title' => 'Email'],
                            ['field' => 'contact_number', 'title' => 'Phone'],
                            ['field' => 'is_dealer', 'title' => 'Type', 'sortable' => false],
                            ['field' => 'status', 'title' => 'Status'],
                            ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                        ]])
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td>{{ $u->id }}</td>
                            <td>{{ trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->contact_number }}</td>
                            <td>{{ !empty($u->is_dealer) ? 'Dealer' : (!empty($u->is_driver) ? 'Driver' : 'User') }}</td>
                            <td>{{ (int)$u->status }}</td>
                            <td>
                                <form method="post" action="/cloud/linked_users/view" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="userid" value="{{ base64_encode((string)$u->id) }}">
                                    <button type="submit" class="btn btn-default btn-xs">View</button>
                                </form>
                                · <a href="/cloud/linked_users/edit/{{ base64_encode((string)$u->id) }}">Edit</a>
                                · <a href="/cloud/linked_users/ccindex/{{ base64_encode((string)$u->id) }}">Cards</a>
                                · <a href="/cloud/linked_users/dynamicfares/{{ base64_encode((string)$u->id) }}">Dynamic fares</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" align="center">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit ?? 50])
@endsection

