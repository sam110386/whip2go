@extends('admin.layouts.app')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Savvy</span> - Dealers</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('admin/savvy/dealers/add') }}" class="btn btn-success left-margin">New Dealer</a>
        </div>
    </div>
</div>

<div class="row">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
</div>

<div class="panel">
    <div class="panel-body">
        <div class="row">&nbsp;</div>
        
        <div class="table-responsive">
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', ['columns' => [
                            ['field' => 'id', 'title' => '#', 'style' => 'width: 5%;'],
                            ['field' => 'first_name', 'title' => 'Name'],
                            ['field' => 'status', 'title' => 'Status'],
                            ['field' => 'actions', 'title' => 'Actions', 'sortable' => false, 'style' => 'width: 15%;']
                        ]])
                    </tr>
                </thead>
                <tbody>
                    @foreach($dealers as $dealer)
                    <tr>
                        <td valign="top">{{ $dealer->id }}</td>
                        <td valign="top">{{ $dealer->first_name }} {{ $dealer->last_name }}</td>
                        <td valign="top">
                            @if($dealer->status == '0')
                                <a href="{{ url('admin/savvy/dealers/status/' . base64_encode($dealer->id) . '/1') }}">Inactive</a>
                            @else
                                <a href="{{ url('admin/savvy/dealers/status/' . base64_encode($dealer->id) . '/0') }}">Active</a>
                            @endif
                        </td>
                        <td class="action">
                            <a href="{{ url('admin/savvy/dealers/add/' . base64_encode($dealer->id)) }}"><i class="glyphicon glyphicon-edit"></i></a>
                            <a href="{{ url('admin/savvy/dealers/delete/' . base64_encode($dealer->id)) }}" onclick="return confirm('Are you sure?')"><i class="glyphicon glyphicon-trash"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    @if($dealers->isEmpty())
                        <tr><td colspan="4" align="center">No record found</td></tr>
                    @endif
                </tbody>
            </table>
        </div>

        @include('partials.dispacher.paging_box', ['paginator' => $dealers, 'limit' => $limit ?? 25])
    </div>
</div>
@endsection
