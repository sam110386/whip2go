@extends('layouts.admin')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Vehicle</span> - Scan</h4>
        </div>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="vehiclescans">
        @include('admin.inspekt._index')
    </div>
</div>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
<script src="{{ asset('Inspekt/js/inspektscan.js') }}"></script>
<link rel="stylesheet" href="{{ asset('Inspekt/css/inspekt.css') }}">
@endsection
