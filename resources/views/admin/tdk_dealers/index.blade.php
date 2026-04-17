@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'TDK Dealers')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> TDK Dealer</h4>
            </div>
            <div class="heading-elements">
                <a href="{{ $basePath }}/add" class="btn btn-success">Add New</a>
            </div>
        </div>
    </div>
    <div class="row">
        @if(session('success'))
            <div class="col-md-12"><div class="alert alert-success">{{ session('success') }}</div></div>
        @endif
        @if(session('error'))
            <div class="col-md-12"><div class="alert alert-danger">{{ session('error') }}</div></div>
        @endif
    </div>
    <div class="panel">
        <div class="panel-body">
            <form method="get" action="{{ $basePath }}/index" id="frmSearchadmin" name="frmSearchadmin">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            <label>Keyword :</label>
                            <input type="text" name="Search[keyword]" class="form-control" size="30" maxlength="50" value="{{ $keyword }}">
                        </div>
                        <div class="col-md-3">
                            <label>Status :</label>
                            <select name="Search[show]" class="form-control">
                                <option value="" @selected($show === '')>All..</option>
                                <option value="Active" @selected($show === 'Active')>Active</option>
                                <option value="Deactive" @selected($show === 'Deactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" class="btn btn-primary" name="search" value="search">APPLY</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div class="table-responsive">
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', ['columns' => [
                                ['field' => 'id', 'title' => '#'],
                                ['field' => 'first_name', 'title' => 'Name'],
                                ['field' => 'metro_city', 'title' => 'Metro City'],
                                ['field' => 'metro_state', 'title' => 'Metro State'],
                                ['field' => 'status', 'title' => 'Status'],
                                ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                            ]])
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td valign="top">{{ $user->id }}</td>
                                <td valign="top">{{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) }}</td>
                                <td valign="top">{{ $user->metro_city }}</td>
                                <td valign="top">{{ $user->metro_state }}</td>
                                <td align="center" valign="bottom">
                                    @if((int)($user->status ?? 0) === 1)
                                        <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Active" title="Active">
                                    @else
                                        <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Inactive" title="Inactive">
                                    @endif
                                </td>
                                <td align="center">
                                    <ul class="icons-list">
                                        <li class="dropdown">
                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                                                <i class="icon-menu9"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li>
                                                    <a href="{{ $basePath }}/add/{{ base64_encode((string)$user->id) }}">
                                                        <i class="glyphicon glyphicon-pencil"></i> Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="{{ $basePath }}/delete/{{ base64_encode((string)$user->id) }}"
                                                       onclick="return confirm('Are you sure you want to delete this record?');">
                                                        <i class="glyphicon glyphicon-trash"></i> Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" align="center">No record found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit ?? 25])
        </div>
    </div>
@endsection
