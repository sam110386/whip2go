@extends('layouts.main')

@section('title', 'Telematics Subscription')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Telematics Subscription</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('telematics/subscriptions/buy') }}" class="btn btn-danger btn-lg" style="float:right;">Buy New</a>
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        <div style="width:100%; overflow: visible;" id="postsPaging">
            @include('cloud.telematics._index_table')
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
@endsection
