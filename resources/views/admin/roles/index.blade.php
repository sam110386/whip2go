@extends('admin.layouts.app')

@section('title', 'Manage Roles')

@section('content')

    @php
        $url = ['keyword' => $keyword];
        $optionspaging = ['url' => $url, 'update' => 'listing'];
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> - Roles</h4>
            </div>
            <div class="heading-elements">
                <a href="/admin/roles/add" class="btn btn-danger btn-lg" style="float:right;">Add New</a>
            </div>
        </div>
    </div>
    <div class="row ">
        @include('partials.flash')
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Search</h5>
        </div>
        <div class="panel-body">
            <form action="{{ url('/admin/roles/index') }}" method="get" class="form-inline">
                <div class="form-group mb-2">
                    <input type="text" name="keyword" class="form-control" placeholder="Search by name or slug" value="{{ $keyword ?? '' }}">
                </div>
                <button type="submit" class="btn btn-primary mb-2">Search</button>
                <a href="{{ url('/admin/roles/index') }}" class="btn btn-default mb-2">Reset</a>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('admin.roles.elements.index')
        </div>
    </div>
@endsection