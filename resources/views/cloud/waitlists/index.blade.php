@extends('layouts.main')

@section('title', 'Waitlist Leads')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Waitlist
                    Leads</h4>
            </div>
        </div>
    </div>

    <div class="row">
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>@endif
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="POST" action="{{ $basePath }}/index" id="frmSearchadmin" name="frmSearchadmin"
                class="form-horizontal">
                @csrf
                <fieldset class="content-group">
                    <div class="col-md-2">
                        <input type="text" id="SearchVehicleId" name="Search[vehicle_id]" style="width:100%;"
                            value="{{ $vehicleid }}" placeholder="Vehicle">
                    </div>
                    <div class="col-md-2">
                        <select name="Search[status]" class="form-control">
                            <option value="">Status</option>
                            <option value="new" @selected($status === 'new')>New</option>
                            <option value="active" @selected($status === 'active')>Active</option>
                            <option value="cancel" @selected($status === 'cancel')>Cancel</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" id="SearchDateFrom" name="Search[date_from]" class="form-control"
                            value="{{ !empty($date_from) ? \Carbon\Carbon::parse($date_from)->format('m/d/Y') : '' }}"
                            placeholder="Date Range From">
                    </div>
                    <div class="col-md-2">
                        <input type="text" id="SearchDateTo" name="Search[date_to]" class="form-control"
                            value="{{ !empty($date_to) ? \Carbon\Carbon::parse($date_to)->format('m/d/Y') : '' }}"
                            placeholder="Date Range To">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="search" value="SEARCH"
                            class="btn btn-primary">&nbsp;&nbsp;SEARCH&nbsp;&nbsp;</button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body" id="listing">
            @include('cloud.waitlists._index')
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

@push('scripts')
    <script src="/js/select2.js"></script>
    <script type="text/javascript">
        function format(item) {
            return item.tag;
        }
        jQuery(document).ready(function () {
            jQuery("#SearchVehicleId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Customer ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ $vehicleAjaxUrl }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return { term: params, dealer_id: jQuery("#SearchDealerid").val() }
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return { tag: item.tag, id: item.id }
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var vehicleid = "{{ $vehicleid }}";
                    if (vehicleid.length > 0) {
                        jQuery.ajax({
                            url: "{{ $vehicleAjaxUrl }}",
                            dataType: "json",
                            type: "GET",
                            data: { "id": vehicleid }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });

            jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
            jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
        });
    </script>
@endpush