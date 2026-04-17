@extends('layouts.admin')
@section('content')
<script src="/assets/js/select2.js"></script>
<link rel="stylesheet" href="/css/select2.css">
<script type="text/javascript">
    function format(item) { return item.tag; }
    jQuery(document).ready(function() {
        jQuery("#SearchRenterid").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format, formatResult: format,
            placeholder: "Select Customer",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}/cloud/hitch/customer_reports/customerautocomplete",
                dataType: "json", type: "GET",
                data: function (params) { return {term: params} },
                processResults: function (data) {
                    return { results: $.map(data, function (item) { return {tag: item.tag, id: item.id} }) };
                }
            },
            initSelection: function (element, callback) {
                var renter_id = "{{ $renterid }}";
                if (renter_id.length > 0) {
                    jQuery.ajax({
                        url: "{{ config('app.url') }}/cloud/hitch/customer_reports/customerautocomplete",
                        dataType: "json", type: "GET",
                        data: {"renter_id": renter_id}
                    }).done(function (data) { callback(data[0]); });
                }
            }
        });
    });
</script>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Customer</span> - Reports</h4>
        </div>
    </div>
</div>
<div class="row">
    @include('partials.flash')
</div>
<div class="panel">
    <form action="/cloud/hitch/customer_reports/index" method="POST" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-2">
                <input type="text" name="Search[renterid]" id="SearchRenterid" style="width:100%;" value="{{ $renterid }}" placeholder="Driver">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Booking#">
            </div>
            <div class="col-md-2">
                <button type="submit" name="search" value="search" class="btn btn-primary">SEARCH</button>
            </div>
        </div>
    </form>
</div>
<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('cloud.hitch.customer_reports._table')
    </div>
</div>
<script src="/Hitch/js/hitch.js"></script>
@endsection
