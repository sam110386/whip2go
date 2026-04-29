@extends('layouts.main')

@section('title', 'Reports')

@php
    $dealers ??= [];
    $dealer_id ??= '';
    $dealerid ??= $dealer_id;
    $keyword ??= '';
    $fieldname ??= '';
    $status_type ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $renter_id ??= '';
    $renterid ??= $renter_id;
    $limit ??= 50;
    $rollups ??= [];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
<style type="text/css">
    tbody tr{cursor: pointer;}
    .table>thead>tr>th,
    .table>tbody>tr>th,
    .table>tfoot>tr>th,
    .table>thead>tr>td,
    .table>tbody>tr>td,
    .table>tfoot>tr>td { padding: 5px; }
</style>
@endpush

@push('scripts')
<script src="{{ legacy_asset('js/select2.js') }}"></script>
<script src="{{ legacy_asset('js/cloud_booking.js') }}"></script>
<script type="text/javascript">
    function format(item) { return item.tag; }
    jQuery(document).ready(function() {
        jQuery('#SearchDateFrom').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery('#SearchDateTo').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery("#SearchRenterId").select2({
            data: {results: {}, text: 'tag'},
            formatSelection: format, formatResult: format,
            placeholder: "Select Customer ",
            minimumInputLength: 1,
            ajax: {
                url: "{{ config('app.url') }}/cloud/linked_reports/customerautocomplete",
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
                        url: "{{ config('app.url') }}/cloud/linked_reports/customerautocomplete",
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
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Reports</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('cloud/linked_reports/vehicle') }}" class="btn btn-primary">Fleet productivity</a>
            <a href="{{ url('cloud/linked_reports/productivity') }}" class="btn btn-primary">Portfolio productivity</a>
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ url('cloud/linked_reports/index') }}" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <select name="Search[dealer_id]" class="form-control md-form">
                        <option value="">Dealers</option>
                        @foreach ($dealers as $did => $dname)
                            <option value="{{ $did }}" @selected((string) $dealerid === (string) $did)>{{ $dname }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                </div>
                <div class="col-md-2">
                    <select name="Search[searchin]" class="form-control">
                        <option value="">Search By</option>
                        <option value="1" @selected((string) $fieldname === '1')>Pickup Address</option>
                        <option value="2" @selected((string) $fieldname === '2')>Vehicle#</option>
                        <option value="3" @selected((string) $fieldname === '3')>Order#</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="Search[status_type]" class="form-control">
                        <option value="">Status</option>
                        <option value="complete" @selected($status_type === 'complete')>Complete</option>
                        <option value="cancel" @selected($status_type === 'cancel')>Cancel</option>
                        <option value="incomplete" @selected($status_type === 'incomplete')>InComplete</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ $date_from }}" placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ $date_to }}" placeholder="Date Range To">
                </div>
            </fieldset>
            <fieldset class="content-group">
                <div class="col-md-2">
                    <input type="text" name="Search[renter_id]" id="SearchRenterId" style="width:100%;" value="{{ $renterid }}" placeholder="Customer..">
                </div>
                <div class="col-md-2">
                    <select name="Record[limit]" class="form-control">
                        @foreach ([25, 50, 100, 200] as $opt)
                            <option value="{{ $opt }}" @selected((int) $limit === $opt)>{{ $opt }} / page</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="SEARCH" class="btn btn-primary">&nbsp;&nbsp;SEARCH&nbsp;&nbsp;</button>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="EXPORT" class="btn btn-success"><i class="icon-file-excel"></i> EXPORT</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        @include('admin.linked_reports._listing', [
            'reportlists' => $reportlists,
            'rollups' => $rollups,
        ])
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
