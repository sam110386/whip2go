@extends('layouts.admin')
@section('title', 'Summary - Reports')
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
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Summary</span> - Reports</h4>
        </div>
    </div>
</div>
<div class="row ">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
</div>

<div class="panel">
    <form method="POST" action="{{ url('/cloud/report/summary') }}" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-2">
                <select name="Search[dealerid]" class="form-control md-form">
                    <option value="">Dealer</option>
                    @foreach ($dealers ?? [] as $id => $label)
                        <option value="{{ $id }}" @selected(old('Search.dealerid', $dealerid ?? '') == $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" name="search" value="search" class="btn btn-primary">SEARCH</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('cloud.report.elements.cloud_summary')
    </div>
</div>
@endsection
