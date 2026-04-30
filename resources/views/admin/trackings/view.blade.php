@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Vehicle Views')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Vehicle</span> Views</h4>
            </div>
            <div class="heading-elements">
                <a href="{{ $basePath }}/index" class="btn btn-success">Tracking Data</a>
            </div>
        </div>
    </div>
    <div class="row">
        @include('partials.flash')
    </div>
    <div class="panel">
        <div class="panel-body">
            <div id="listing">
                @include('admin.trackings.partials.view_listing', [
                    'trackings' => $trackings,
                    'limit' => $limit,
                    'basePath' => $basePath,
                ])
            </div>
        </div>
    </div>
@endsection
