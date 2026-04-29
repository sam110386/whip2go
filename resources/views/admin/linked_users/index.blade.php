@extends('admin.layouts.app')

@section('title', 'Manage Lead Dealers')

@php
    $keyword ??= '';
    $searchin ??= 'All';
    $type ??= '';
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> Lead Dealers
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('cloud/linked_users/edit') }}" class="btn btn-success">
                    {{ 'Add New' }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('cloud/linked_users/index') }}">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            {{ 'Keyword :' }}
                            <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" maxlength="50">
                        </div>
                        <div class="col-md-2">
                            {{ 'Search In :' }}
                            <select name="Search[searchin]" class="form-control">
                                <option value="All" @selected($searchin === 'All')>All</option>
                                <option value="first_name" @selected($searchin === 'first_name')>First name</option>
                                <option value="email" @selected($searchin === 'email')>Email</option>
                                <option value="username" @selected($searchin === 'username')>Username</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{ 'Type :' }}
                            <select name="Search[type]" class="form-control">
                                <option value="" @selected($type === '')>All</option>
                                <option value="1" @selected((string) $type === '1')>Driver</option>
                                <option value="2" @selected((string) $type === '2')>Dealer</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{ 'Rows :' }}
                            <select name="Record[limit]" class="form-control">
                                @foreach ([25, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" @selected((int) $limit === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary">
                                {{ 'Search' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
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
                                    <td>{{ (int) $u->status }}</td>
                                    <td>
                                        <form method="post" action="{{ url('cloud/linked_users/view') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="userid" value="{{ base64_encode((string) $u->id) }}">
                                            <button type="submit" class="btn btn-default btn-xs">View</button>
                                        </form>
                                        <a href="{{ url('cloud/linked_users/edit/' . base64_encode((string) $u->id)) }}" class="btn btn-default btn-xs">Edit</a>
                                        <a href="{{ url('cloud/linked_users/ccindex/' . base64_encode((string) $u->id)) }}" class="btn btn-default btn-xs">Cards</a>
                                        <a href="{{ url('cloud/linked_users/dynamicfares/' . base64_encode((string) $u->id)) }}" class="btn btn-default btn-xs">Dynamic fares</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" align="center">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit])
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection

@push('styles')
    <style type="text/css">
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
