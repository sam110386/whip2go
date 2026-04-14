@extends('layouts.admin')
@section('title', 'User Notes')
@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><a href="{{ url('admin/users/index') }}"><i class="icon-arrow-left52 position-left"></i></a> <span class="text-semibold">User</span> - Notes</h4>
        </div>
        <div class="heading-elements">
            <a href="javascript:;" class="btn left-margin" onclick="AddNewNote({{ $userid }})">Add New Note</a>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
</div>
<div class="breadcrumb-line">
    <ul class="text-center pt-20 pb-10">
        <li><h4><span class="text-semibold">User : </span>{{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}</h4></li>
    </ul>
</div>
<div class="breadcrumb-line">
    <ul class="text-center">
        <li><h6><span class="text-semibold">Notes History </span></h6></li>
    </ul>
</div>
<div class="panel">
    <div class="panel-body" id="postsPaging">
        @include('admin.user_note._admin_index')
    </div>
</div>
<script src="{{ asset('UserNote/js/usernote.js') }}"></script>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
@endsection
