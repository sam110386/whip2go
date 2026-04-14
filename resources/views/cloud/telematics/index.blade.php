@extends('layouts.main')
@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> - Telematics Subscription</h4>
        </div>
        <div class="heading-elements">
            <a href="/telematics/subscriptions/buy" class="btn btn-danger btn-lg" style="float:right;">Buy New</a>
        </div>
    </div>
</div>
<div class="row">
    @include('partials.flash')
</div>
<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('cloud.telematics._index_table')
    </div>
</div>
@endsection
