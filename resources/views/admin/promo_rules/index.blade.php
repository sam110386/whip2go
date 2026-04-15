@extends('admin.layouts.app')
@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> - Promo Rules</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('/admin/promo/rules/add') }}" class="btn btn-danger btn-lg" style="float:right;">Add New</a>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.promo_rules._index')
    </div>
</div>
@endsection
