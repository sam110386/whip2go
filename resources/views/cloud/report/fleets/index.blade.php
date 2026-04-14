@extends('layouts.admin')
@section('title', 'Vehicle - Reports')
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
                url: "{{ config('app.url') }}/cloud/linked_bookings/getVehicle",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params}
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
                        url: "{{ config('app.url') }}/cloud/linked_bookings/getVehicle",
                        dataType: "json",
                        type: "GET",
                        data: {"id": vehicleid}
                    }).done(function (data) {
                        callback(data[0]);
                    });
                }
            }
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
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Vehicle</span> - Reports</h4>
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
    <form method="POST" action="{{ url('/cloud/report/fleets') }}" class="form-horizontal">
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
                <input type="text" name="Search[vehicleid]" id="SearchVehicleid" style="width:100%;" value="{{ old('Search.vehicleid', $vehicleid ?? '') }}" placeholder="Vehicle">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ old('Search.keyword', $keyword ?? '') }}" placeholder="Booking#">
            </div>
            <div class="col-md-2">
                <button type="submit" name="search" value="search" class="btn btn-primary">SEARCH</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('cloud.report.elements.cloud_fleet')
    </div>
</div>
<script src="{{ asset('js/report/report.js') }}"></script>
@endsection
