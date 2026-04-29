@extends('admin.layouts.app')

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
                    <table class="table table-responsive">
                        <thead>
                            <tr>
                                @include('partials.dispacher.sortable_header', ['columns' => [
                                    ['field' => 'id', 'title' => '#'],
                                    ['field' => 'name', 'title' => 'Dealer Name'],
                                    ['field' => 'phone', 'title' => 'Phone #'],
                                    ['field' => 'address', 'title' => 'Address'],
                                    ['field' => 'created', 'title' => 'Created'],
                                    ['field' => 'status', 'title' => 'Status'],
                                    ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
                                ]])
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dealers as $row)
                                <tr>
                                    <td valign="top">{{ $row->id }}</td>
                                    <td valign="top">{{ $row->name }}</td>
                                    <td valign="top">{{ $row->phone }}</td>
                                    <td valign="top">{{ $row->address }}</td>
                                    <td valign="top">{{ $row->created }}</td>
                                    <td class="text-center">
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
                                    <td class="text-center">
                                        <a href="{{ $basePath }}/delete/{{ base64_encode((string)$row->id) }}" onclick="return confirm('Delete this record?')">
                                            <i class="glyphicon glyphicon-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            <tr><td style="height:6px;" colspan="7"></td></tr>
                        </tbody>
                    </table>
                </div>
                
                @include('partials.dispacher.paging_box', ['paginator' => $dealers, 'limit' => $limit ?? 50])
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <td colspan="7" class="text-center">
                                @if($dealers === null)
                                    Marketplace pending dealers table is not available.
                                @else
                                    No record found
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
