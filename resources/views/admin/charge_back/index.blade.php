@extends('admin.layouts.app')

@section('content')
<style type="text/css">
    tbody tr{cursor: pointer;}
</style>
<script src="{{ asset('js/select2.js') }}"></script>
<link rel="stylesheet" href="{{ asset('css/select2.css') }}">
<script type="text/javascript">
    function format(item) {
        return item.tag;
    }
    jQuery(document).ready(function() {
        jQuery("#SearchDealerId").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format,
            formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}admin/bookings/customerautocomplete",
                dataType: "json",
                type: "GET",
                data: function (params) {
                    return {term: params, "is_dealer": true}
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {tag: item.tag, id: item.id}
                        })
                    };
                }
            },
            initSelection: function (element, callback) {
                var dealer_id = "{{ $dealerid }}";
                if (dealer_id.length > 0) {
                    jQuery.ajax({
                        url: "{{ config('app.url') }}admin/bookings/customerautocomplete",
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
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery('#SearchDateTo').datepicker({dateFormat: 'mm/dd/yy'});
    });
</script>

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Dealer </span>- Charge Back</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('admin/charge_back/dealer_chargebacks/payment') }}" class="btn left-margin">New Charge</a>
        </div>
    </div>
</div>
<div class="row">
    @include('partials.flash')
</div>
<div class="panel">
    <div class="panel-body">
        <form action="{{ url('admin/charge_back/dealer_chargebacks/index') }}" method="POST" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <input type="text" name="Search[dealer_id]" id="SearchDealerId" style="width:100%;" value="{{ $dealerid }}" placeholder="Dealers">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('m/d/Y') : '' }}" placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('m/d/Y') : '' }}" placeholder="Date Range To">
                </div>
                <div class="col-md-2">
                    <input type="submit" name="search" value="SEARCH" class="btn btn-primary">
                </div>
            </fieldset>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="listing">
        @include('admin.charge_back._admin_index')
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
