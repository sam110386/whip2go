@extends('layouts.admin')
@section('title', 'Vehicle Revenue - Report')
@section('content')
<script src="{{ asset('js/select2.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/select2.css') }}">
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        jQuery("#SearchVehicleid").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}/admin/bookings/getVehicle",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params,dealer_id:jQuery("#SearchDealerid").val()}
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {tag: item.tag,id: item.id}
                        })
                    };
                }
            },
            initSelection: function (element, callback) {
                var vehicleid = "{{ $vehicleid ?? '' }}";
                if (vehicleid.length > 0) {
                    jQuery.ajax({
                        url: "{{ config('app.url') }}/admin/bookings/getVehicle",
                        dataType: "json",
                        type: "GET",
                        data: {"id": vehicleid}
                    }).done(function (data) {
                        callback(data[0]);
                    });
                }
            }
        });
        jQuery("#SearchDealerid").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}/admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params,"is_dealer":true}
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {tag: item.tag,id: item.id}
                        })
                    };
                }
            },
            initSelection: function (element, callback) {
                var dealer_id = "{{ $dealerid ?? '' }}";
                if (dealer_id.length > 0) {
                    jQuery.ajax({
                        url: "{{ config('app.url') }}/admin/bookings/customerautocomplete",
                        dataType: "json",
                        type: "GET",
                        data: {"id": dealer_id}
                    }).done(function (data) {
                        callback(data[0]);
                    });
                }
            }
        });
    });
</script>
<script type="text/javascript">
    jQuery(document).ready(function() {
        $('#SearchDatefrom').datetimepicker({format: 'MM/YYYY'});
        $('#SearchDateto').datetimepicker({
            useCurrent: false,
            format: 'MM/YYYY'
        });
    });
</script>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Vehicle Revenue</span> - Report</h4>
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
    <form method="POST" action="{{ url('/admin/report/revenue-reports') }}" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-2">
                <input type="text" name="Search[datefrom]" id="SearchDatefrom" class="date form-control" value="{{ old('Search.datefrom', $datefrom ?? '') }}" placeholder="Date from">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[dateto]" id="SearchDateto" class="date form-control" value="{{ old('Search.dateto', $dateto ?? '') }}" placeholder="Date to">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[dealerid]" id="SearchDealerid" style="width:100%;" value="{{ old('Search.dealerid', $dealerid ?? '') }}" placeholder="Dealers">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[vehicleid]" id="SearchVehicleid" style="width:100%;" value="{{ old('Search.vehicleid', $vehicleid ?? '') }}" placeholder="Vehicle">
            </div>
            <div class="col-md-4">
                <button type="submit" name="refresh" value="refresh" class="btn btn-warning pull-right">Refresh Report</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.report.elements._revenue_report')
    </div>
</div>
@endsection
