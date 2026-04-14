@extends('layouts.admin')

@section('title', $title_for_layout ?? 'Tracking Data')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Tracking</span> Data</h4>
            </div>
            <div class="heading-elements">
                <a href="{{ $basePath }}/view" class="btn btn-success">Vehicle Views</a>
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
            <div id="listing">
                @include('admin.trackings.partials.index_listing', [
                    'trackings' => $trackings,
                    'limit' => $limit,
                    'basePath' => $basePath,
                ])
            </div>
        </div>
    </div>
@endsection
