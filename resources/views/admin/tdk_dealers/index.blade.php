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

            @if($users->total() > 0)
                <div class="table-responsive">
                    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                        <tr>
                            <th valign="top">#</th>
                            <th valign="top">Name</th>
                            <th valign="top">Metro City</th>
                            <th valign="top">Metro State</th>
                            <th valign="top">Status</th>
                            <th valign="top">Actions</th>
                        </tr>
                        @foreach($users as $user)
                            <tr>
                                <td valign="top">{{ $user->id }}</td>
                                <td valign="top">{{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) }}</td>
                                <td valign="top">{{ $user->metro_city }}</td>
                                <td valign="top">{{ $user->metro_state }}</td>
                                <td align="center" valign="bottom">
                                    @if((int)($user->status ?? 0) === 1)
                                        <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status">
                                    @else
                                        <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status">
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
                        @endforeach
                        <tr><td style="height:6px;" colspan="7"></td></tr>
                    </table>
                </div>

                <section class="pagging" style="margin-top:12px; overflow:hidden;">
                    <div style="width:40%; float:left;">
                        <form name="frmRecordsPages" action="{{ $basePath }}/index" method="get">
                            @if($keyword !== '')
                                <input type="hidden" name="Search[keyword]" value="{{ $keyword }}">
                            @endif
                            @if($show !== '')
                                <input type="hidden" name="Search[show]" value="{{ $show }}">
                            @endif
                            <label class="text-semibold">Show</label>
                            <select name="Record[limit]" class="textbox pagingcls form-control" style="display:inline-block; width:auto; min-width:70px;" onchange="this.form.submit()">
                                @foreach ([25,50,100,200] as $opt)
                                    <option value="{{ $opt }}" @selected((int)($limit ?? 25) === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                            <span>&nbsp;Records per page</span>
                        </form>
                    </div>
                    <div class="pull-right" style="margin-top:4px;">
                        {{ $users->links() }}
                    </div>
                </section>
            @else
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="borderTable">
                    <tr>
                        <td colspan="4" align="center">No record found</td>
                    </tr>
                </table>
            @endif
        </div>
    </div>
@endsection
