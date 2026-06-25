@extends('admin.layouts.app')

@php
    $title = 'Tracking Data';
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Tracking</span> Data
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('admin/trackings/view')}}" class="btn btn-success">
                    {{ 'Vehicle Views' }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div id="listing">
                @include('admin.trackings.elements.index')
            </div>
        </div>
    </div>

@endsection