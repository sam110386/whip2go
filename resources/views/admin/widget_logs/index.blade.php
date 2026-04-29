@extends('admin.layouts.app')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><a href="{{ url('/admin/widget_logs/index') }}"><i class="icon-arrow-left52 position-left"></i></a> <span class="text-semibold">Widget</span> - Logs</h4>
        </div>
    </div>
</div>
<div class="row">
    @if(session('flash_message'))
        <div class="alert alert-info">{{ session('flash_message') }}</div>
    @endif
</div>

<div class="breadcrumb-line">
    <ul class="text-center">
        <li><h6><span class="text-semibold">Logs </span></h6></li>
    </ul>
</div>
<div class="panel">
    <div class="panel-body" id="postsPaging">
        @include('admin.widget_logs._index')
    </div>
</div>
<script src="{{ legacy_asset('Widgets/js/ui.js') }}"></script>
{{-- Modal for log display --}}
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
{{-- Modal for IP sub-display --}}
<div id="plaidModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
@endsection
