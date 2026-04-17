@extends('admin.layouts.app')

@section('content')
<style type="text/css">
    tbody tr{cursor: pointer;}
</style>
<script src="{{ config('app.url') }}/js/select2.js"></script>
<link rel="stylesheet" href="{{ config('app.url') }}/css/select2.css">
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        jQuery("#SearchVehicleId").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Vehicle ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}/admin/vehicle_offers/vehicleautocomplete",
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
                var dealer_id = "{{ $vehicleid }}";
                if (dealer_id.length > 0) {
                    jQuery.ajax({
                        url: "{{ config('app.url') }}/admin/vehicle_offers/vehicleautocomplete",
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

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Vehicle Alerts </h4>
        </div>
    </div>
</div>
<div class="row">
    @if(session('flash'))
        <div class="alert alert-info">{{ session('flash') }}</div>
    @endif
</div>
<div class="panel">
    <div class="panel-body">
        <form action="{{ config('app.url') }}/admin/vehicle_alert/vehicle_alerts/index" method="POST" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <input type="text" name="Search[vehicle_id]" id="SearchVehicleId" style="width:100%;" value="{{ $vehicleid }}" placeholder="Vehicle">
                </div>
                <div class="col-md-2">
                    <input type="submit" name="search" value="&nbsp;&nbsp;SEARCH&nbsp;&nbsp;" class="btn btn-primary">
                </div>
            </fieldset>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="listing">
        @include('admin.vehicle_alerts._index_table')
    </div>
</div>
<script src="{{ config('app.url') }}/VehicleAlert/js/vehiclealert.js"></script>
@endsection
