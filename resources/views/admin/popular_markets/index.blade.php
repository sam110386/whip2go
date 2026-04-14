@extends('layouts.admin')

@section('title', $title_for_layout ?? 'Manage Popular Markets')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Popular</span> - Markets</h4>
            </div>
            <div class="heading-elements">
                <a href="{{ $basePath }}/add" class="btn left-margin">Add New</a>
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
        <div class="panel-body" id="listing">
            @include('admin.popular_markets.partials.listing', [
                'popularMarkets' => $popularMarkets,
                'limit' => $limit,
                'basePath' => $basePath,
            ])
        </div>
    </div>
@endsection
