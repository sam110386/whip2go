@extends('layouts.admin')

@section('title', $title_for_layout ?? 'Marketplace Pending Dealers')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Marketplace</span> Pending Dealers</h4>
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
                                <option value="">Select..</option>
                                <option value="Active" @selected($show === 'Active')>Active</option>
                                <option value="Deactive" @selected($show === 'Deactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" class="btn btn-primary" name="search" value="search">APPLY</button>
                            @if((int)($limit ?? 50) !== 50)
                                <input type="hidden" name="Record[limit]" value="{{ (int)($limit ?? 50) }}">
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            @if($dealers && $dealers->total() > 0)
                <div class="table-responsive">
                    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                        <tr>
                            <th valign="top">#</th>
                            <th valign="top">Dealer Name</th>
                            <th valign="top">Phone #</th>
                            <th valign="top">Address</th>
                            <th valign="top">Created</th>
                            <th valign="top">Status</th>
                            <th valign="top">Actions</th>
                        </tr>
                        @foreach ($dealers as $row)
                            <tr>
                                <td valign="top">{{ $row->id }}</td>
                                <td valign="top">{{ $row->name }}</td>
                                <td valign="top">{{ $row->phone }}</td>
                                <td valign="top">{{ $row->address }}</td>
                                <td valign="top">{{ $row->created }}</td>
                                <td align="center" valign="bottom">
                                    @if((int)($row->status ?? 0) === 1)
                                        <a href="{{ $basePath }}/status/{{ base64_encode((string)$row->id) }}/0" onclick="return confirm('Are you sure to update this record?')">
                                            <img src="{{ legacy_asset('img/green2.jpg') }}" alt="Status" title="Status">
                                        </a>
                                    @else
                                        <a href="{{ $basePath }}/status/{{ base64_encode((string)$row->id) }}/1" onclick="return confirm('Are you sure to update this record?')">
                                            <img src="{{ legacy_asset('img/red3.jpg') }}" alt="Status" title="Status">
                                        </a>
                                    @endif
                                </td>
                                <td align="center" valign="top">
                                    <a href="{{ $basePath }}/delete/{{ base64_encode((string)$row->id) }}" onclick="return confirm('Delete this record?')">
                                        <i class="glyphicon glyphicon-trash"></i>
                                    </a>
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
                                    <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                            <span>&nbsp;Records per page</span>
                        </form>
                    </div>
                    <div class="pull-right" style="margin-top:4px;">
                        {{ $dealers->links() }}
                    </div>
                </section>
            @else
                <table width="100%" cellpadding="2" cellspacing="1" border="0" class="borderTable">
                    <tr>
                        <td colspan="7" align="center">
                            @if($dealers === null)
                                Marketplace pending dealers table is not available.
                            @else
                                No record found
                            @endif
                        </td>
                    </tr>
                </table>
            @endif
        </div>
    </div>
@endsection
