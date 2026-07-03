@extends('admin.layouts.app')

@php
    $title ??= 'Manage Roles';
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
                <a href="{{url('admin/roles/add')}}" class="btn btn-danger btn-lg" style="float:right;">Add New</a>
            </div>
        </div>
    </div>

    <div class="row ">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('admin.roles.elements.index')
        </div>
    </div>

@endsection