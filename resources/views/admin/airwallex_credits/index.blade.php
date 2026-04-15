@extends('admin.layouts.app')
@section('title', 'Airwallex Credits')
@section('content')
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Airwallex</span> - Credits</h4>
        </div>
    </div>
</div>
<div class="row">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
</div>

<div class="panel">
    <form method="POST" action="" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-2">
                <input type="text" name="Search[keyword]" class="form-control" placeholder="Keyword" maxlength="50" value="{{ $keyword ?? '' }}">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[date_from]" class="form-control" placeholder="Date Range From" value="{{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('m/d/Y') : '' }}">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[date_to]" class="form-control" placeholder="Date Range To" value="{{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('m/d/Y') : '' }}">
            </div>
            <div class="col-md-2">
                <button type="submit" name="search" value="search" class="btn btn-primary">SEARCH</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.airwallex_credits._index')
    </div>
</div>
<script src="{{ asset('assets/js/plugins/media/fancybox.min.js') }}"></script>
@endsection
