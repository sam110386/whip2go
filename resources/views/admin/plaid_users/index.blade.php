@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Plaid Users')

@php
    $plaids ??= [];
    $userid ??= '';
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="{{ url('admin/users/index') }}">
                        <i class="icon-arrow-left52 position-left"></i>
                    </a>
                    <span class="text-semibold">Connected</span> - Bank Accounts
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <p>User ID: <strong>{{ $userid }}</strong></p>

            <div id="listing">
                <div class="table-responsive">
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive table-bordered">
                        <thead>
                            <tr>
                                @include('partials.dispacher.sortable_header', ['columns' => [
                                    ['title' => 'ID', 'field' => 'id'],
                                    ['title' => 'User ID', 'field' => 'user_id'],
                                    ['title' => 'Paystub', 'field' => 'paystub'],
                                    ['title' => 'Plaid user ID', 'field' => 'plaid_user_id'],
                                    ['title' => 'Link token', 'field' => 'link_token'],
                                    ['title' => 'Created', 'field' => 'created']
                                ]])
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
                                    <td colspan="6" align="center">No plaid_users rows for this user.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (isset($plaids) && method_exists($plaids, 'links'))
                    @include('partials.dispacher.paging_box', ['paginator' => $plaids, 'limit' => $limit])
                @endif
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
