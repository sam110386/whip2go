@extends('admin.layouts.app')

@php
    $title ??= 'Manage Savvy Dealers';
    $dealers ??= [];
    $limit ??= 25;
@endphp

@section('title', 'Manage Savvy Dealers')

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Savvy</span> - Dealers
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/savvy_dealers/add') }}" class="btn btn-success left-margin">New Dealer</a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="table-responsive">
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            <th valign="top" width="5%">#</th>
                            <th valign="top">Name</th>
                            <th valign="top">Status</th>
                            <th valign="top" width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dealers as $dealer)
                            <tr>
                                <td valign="top">
                                    {{ data_get($dealer, 'id', '') }}
                                </td>

                                <td valign="top">
                                    {{ data_get($dealer, 'user.first_name', '') }} {{ data_get($dealer, 'user.last_name', '') }}
                                </td>

                                <td valign="top">
                                    @if(data_get($dealer, 'status') == '0')
                                        <a href="{{ url('admin/savvy_dealers/status/' . base64_encode(data_get($dealer, 'id')) . '/1') }}" >
                                            Inactive
                                        </a>
                                    @else
                                        <a href="{{ url('admin/savvy_dealers/status/' . base64_encode(data_get($dealer, 'id')) . '/0') }}" >
                                            Active
                                        </a>
                                    @endif
                                </td>

                                <td class="action">
                                    <a
                                        href="{{ url('admin/savvy_dealers/add/' . base64_encode(data_get($dealer, 'id', ''))) }}">
                                        <i class="glyphicon glyphicon-edit"></i>
                                    </a>
                                    <a href="{{ url('admin/savvy_dealers/delete/' . base64_encode(data_get($dealer, 'id', ''))) }}"
                                        onclick="return confirm('Are you sure?')">
                                        <i class="glyphicon glyphicon-trash"></i>
                                    </a>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="4" align="center">No record found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('partials.dispacher.paging_box', ['paginator' => $dealers, 'limit' => $limit])
        </div>
    </div>

@endsection