@extends('admin.layouts.app')

@section('title', $listTitle ?? 'View Page')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Pages</span> - {{ $listTitle ?? 'View' }}
            </h4>
        </div>
        <div class="heading-elements">
            <div class="heading-btn-group">
                <a href="/admin/pages/index" class="btn btn-link btn-float has-text">
                    <i class="icon-undo text-primary"></i>
                    <span>Return</span>
                </a>
            </div>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="/admin/pages/index">Pages</a></li>
            <li class="active">View</li>
        </ul>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">{{ $listTitle ?? 'View Page' }}</h5>
        </div>

        <div class="panel-body">
            <dl class="dl-horizontal">
                <dt>ID</dt>
                <dd>{{ data_get($page, 'id') }}</dd>

                <dt>Title</dt>
                <dd>{{ data_get($page, 'title') }}</dd>

                <dt>Page Code</dt>
                <dd>{{ data_get($page, 'pagecode') }}</dd>

                <dt>Status</dt>
                <dd>
                    @if((int) data_get($page, 'status', 0) === 1)
                        <span class="label label-success">Active</span>
                    @else
                        <span class="label label-default">Inactive</span>
                    @endif
                </dd>

                <dt>Meta Title</dt>
                <dd>{{ data_get($page, 'meta_title') }}</dd>

                <dt>Meta Description</dt>
                <dd>{{ data_get($page, 'meta_description') }}</dd>

                <dt>Meta Keyword</dt>
                <dd>{{ data_get($page, 'meta_keyword') }}</dd>

                <dt>Description</dt>
                {{-- Rendered as HTML because page descriptions are WYSIWYG-authored admin content --}}
                <dd>{!! data_get($page, 'description') !!}</dd>
            </dl>

            <div class="text-right">
                <a href="/admin/pages/index" class="btn btn-default">Return</a>
            </div>
        </div>
    </div>
</div>
@endsection
