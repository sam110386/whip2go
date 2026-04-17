@extends('admin.layouts.app')

@section('content')
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Insurance</span> - Connections</h4>
        </div>
    </div>
</div>
<div class="row">
    @if (session('flash_message'))
        <div class="alert alert-info">{{ session('flash_message') }}</div>
    @endif
</div>
<div class="panel">
    <div class="panel-body">
        <div style="width:100%; overflow: visible;" id="listing">
            @include('admin.axle._index')
        </div>
    </div>
</div>
<script src="{{ asset('Axle/js/axle.js') }}"></script>
<link rel="stylesheet" href="{{ asset('Axle/css/axle.css') }}">
@endsection
