@extends('layouts.main')
@section('content')
<style type="text/css">
    .dropdown-menu > .dropdown-submenu > .dropdown-menu{margin-left: -5px;}
    td.dropdown-menu{display: block; position: static; width: 100%; margin-top: 0; float: none;box-shadow:unset;border-left: unset;border-right: unset;border-bottom: unset;}
    .dropdown-submenu{position: relative; width: 100%;}
</style>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Outstanding</span> - Issues</h4>
        </div>
        <div class="heading-elements">
            <div class="input-group-btn">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Add New</button>
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>
                <ul class="dropdown-menu pull-right">
                    <li><a href="{{ url('/vehicle_issues/accident') }}">Accident Issue</a></li>
                    <li><a href="{{ url('/vehicle_issues/mechanical') }}">Mechanical Issue</a></li>
                    <li><a href="{{ url('/vehicle_issues/roadside') }}">Roadside Assistance</a></li>
                    <li><a href="{{ url('/vehicle_issues/roadside') }}">Roadside Assistance</a></li>
                    <li><a href="{{ url('/vehicle_issues/maintenance') }}">Maintenance</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
</div>
<div class="panel">
    <div class="panel-body">
        <form action="{{ url('/vehicle_issues') }}" method="POST" id="frmSearchadmin" name="frmSearchadmin">
            @csrf
            <div class="row pb-10">
                <div class="col-md-12">
                    <div class="col-md-3">
                        <input type="text" name="Search[vehicle_id]" id="SearchVehicleId" class="formcontrol" value="{{ $vehicle_id }}" style="width:100%;">
                    </div>
                    <div class="col-md-3">
                        <select name="Search[type]" class="form-control">
                            <option value="">Type..</option>
                            @foreach($VehicleIssueType as $k => $v)
                                <option value="{{ $k }}" {{ $type == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="Search[status]" class="form-control">
                            @foreach($issueStatus as $k => $v)
                                <option value="{{ $k }}" {{ (string)$status === (string)$k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <input type="submit" value="APPLY" class="btn btn-primary">
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="listing">
        @include('cloud.vehicle_issues._index')
    </div>
</div>
<div id="myModal" class="modal fade" role="dialog"><div class="modal-dialog"><div class="modal-content"></div></div></div>
<script type="text/javascript">
    function format(item) { return item.tag; }
    jQuery(document).ready(function () {
        jQuery("#SearchVehicleId").select2({
            data: {results: {}, text: 'tag'}, formatSelection: format, formatResult: format,
            placeholder: "Select Vehicle ", minimumInputLength: 1,
            ajax: { url: SITE_URL+"vehicle_issues/getVehicle", dataType: "json", type: "GET", data: function (params) { return {term: params}; }, processResults: function (data) { return {results: jQuery.map(data, function (item) { return {tag: item.tag, id: item.id}; })}; } },
            initSelection: function(element, callback) { var id = $(element).val(); if (id !== "") { $.ajax(SITE_URL+"vehicle_issues/getVehicle", {dataType: "json", type:'POST', data:{id:id}}).done(function(data) { callback(data[0]); }); } }
        });
    });
    function changemystatus(id, status) {
        var con = confirm("Are you sure you want to update its status?");
        if (con) {
            jQuery.blockUI({message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Just a moment...</h1>'});
            $.post(SITE_URL + 'vehicle_issues/changemystatus', {id: id, status: status, _token: '{{ csrf_token() }}'}, function (data) {
                if (data.status == 'success') { $("#td-" + data.recordid).html(data.html); } else { alert(data.message); }
            }, 'json').fail(function () { alert("error"); }).always(function () { jQuery.unblockUI(); });
        }
    }
</script>
@endsection
