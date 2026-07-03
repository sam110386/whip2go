@extends('admin.layouts.app')

@php
    $title ??= 'Manage Permissions';
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> - Roles
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/permissions/add') }}" class="btn btn-danger btn-lg">Add New</a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('admin.permissions.elements.index')
        </div>
    </div>

@endsection