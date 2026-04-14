@extends('layouts.admin')

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
        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
            <tr>
                <th valign="top" width="5%">#</th>
                <th valign="top">Name</th>
                <th valign="top">Status</th>
                <th valign="top" width="15%">Actions</th>
            </tr>
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
                    <a href="{{ url('admin/savvy/dealers/delete/' . base64_encode($dealer->id)) }}"><i class="glyphicon glyphicon-trash"></i></a>
                </td>
            </tr>
            @endforeach
            <tr><td height="6" colspan="4"></td></tr>
        </table>
        {{ $dealers->links() }}
    </div>
</div>
@endsection
