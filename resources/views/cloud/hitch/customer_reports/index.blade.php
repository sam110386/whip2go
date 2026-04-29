@extends('layouts.main')

@section('title', 'Customer Reports')

@push('styles')
<link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@push('scripts')
<script src="{{ legacy_asset('js/select2.js') }}"></script>
<script src="{{ legacy_asset('Hitch/js/hitch.js') }}"></script>
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
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Customer Reports</h4>
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <form action="{{ url('/cloud/hitch/customer_reports/index') }}" method="POST" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <input type="text" name="Search[renterid]" id="SearchRenterid" style="width:100%;" value="{{ $renterid }}" placeholder="Driver">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Booking#">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="search" class="btn btn-primary">&nbsp;&nbsp;SEARCH&nbsp;&nbsp;</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        @include('cloud.hitch.customer_reports._table')
    </div>
</div>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
@endsection
