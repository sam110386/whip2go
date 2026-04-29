@extends('layouts.main')

@section('title', 'Manage Lead Dealers')

@push('scripts')
<script type="text/javascript">
    function viewLinkedUser(userid) {
        jQuery.blockUI({ message: '<h1><img src="/img/select2-spinner.gif" /> Just a moment...</h1>' });
        jQuery.post('/cloud/linked_users/view/', {userid: userid}, function (data) {
            jQuery.unblockUI();
            jQuery.colorbox({width: '900px;', html: data});
        });
        jQuery.unblockUI();
        return false;
    }
</script>
@endpush

@section('content')
@php
    $keyword ??= '';
    $searchin ??= '';
    $type ??= '';
    $limit ??= 50;
@endphp
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Linked </span>- Dealers</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('cloud/linked_users/edit') }}" class="btn btn-success" style="float:right;">Add User</a>
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ url('cloud/linked_users/index') }}" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                </div>
                <div class="col-md-2">
                    <select name="Search[searchin]" class="form-control">
                        <option value="">Search In</option>
                        <option value="username" @selected($searchin === 'username')>Contact #</option>
                        <option value="first_name" @selected($searchin === 'first_name')>First Name</option>
                        <option value="last_name" @selected($searchin === 'last_name')>Last Name</option>
                        <option value="email" @selected($searchin === 'email')>Email</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="Search[type]" class="form-control">
                        <option value="">Driver/Dealer</option>
                        <option value="1" @selected((string)$type === '1')>Driver</option>
                        <option value="2" @selected((string)$type === '2')>Dealer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="SEARCH" class="btn btn-primary">&nbsp;&nbsp;SEARCH&nbsp;&nbsp;</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                        <td>{{ (int) $u->status }}</td>
                        <td>
                            <form method="post" action="{{ url('cloud/linked_users/view') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="userid" value="{{ base64_encode((string) $u->id) }}">
                                <button type="submit" class="btn btn-xs btn-default">View</button>
                            </form>
                            &middot; <a href="{{ url('cloud/linked_users/edit/' . base64_encode((string) $u->id)) }}">Edit</a>
                            &middot; <a href="{{ url('cloud/linked_users/ccindex/' . base64_encode((string) $u->id)) }}">Cards</a>
                            &middot; <a href="{{ url('cloud/linked_users/dynamicfares/' . base64_encode((string) $u->id)) }}">Dynamic fares</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        {{ $users->links() }}
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
@endsection
