@extends('admin.layouts.app')
@section('content')
@php
$status_opt = ['active' => 'Active', 'inactive' => 'Inactive'];
@endphp
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Telematics Subscription Devices</h4>
        </div>
        <div class="heading-elements">
            <a href="/admin/telematics/subscriptions/index" class="btn btn-primary">Back</a>
            <a href="javascript:;" class="btn btn-danger" onclick="addDevice('{{ base64_encode($subid) }}')">Add New</a>
        </div>
    </div>
</div>
<div class="row">
    @include('partials.flash')
</div>
<div class="panel">
    <div class="panel-body">
        <form action="/admin/telematics/sub_devices/index/{{ base64_encode($subid) }}" method="POST" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <select name="Search[status_type]" class="form-control">
                        <option value="">Status</option>
                        @foreach($status_opt as $k => $v)
                            <option value="{{ $k }}" {{ $status_type == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="SEARCH" class="btn btn-primary">&nbsp;&nbsp;SEARCH&nbsp;&nbsp;</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="listing">
        @include('admin.telematics.sub_devices._table')
    </div>
</div>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content animate-bottom"></div>
    </div>
</div>
<script src="/Telematics/js/telematics.js"></script>
@endsection
