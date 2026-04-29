@extends('layouts.main')

@section('title', 'Hitch Reports')

@push('scripts')
<script src="{{ legacy_asset('Hitch/js/hitch.js') }}"></script>
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery('#SearchDateTo').datepicker({dateFormat: 'mm/dd/yy'});
    });
</script>
@endpush

@section('content')
@php
$status_opt = ['complete' => 'Complete', 'cancel' => 'Cancel', 'incomplete' => 'InComplete'];
$search_in = [1 => 'Pickup Address', 2 => 'Vehicle#', 3 => 'Order#'];
@endphp
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Reports</h4>
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <form action="{{ url('/cloud/hitch/hitch_reports/index') }}" method="POST" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                </div>
                <div class="col-md-2">
                    <select name="Search[searchin]" class="form-control">
                        <option value="">Select In</option>
                        @foreach($search_in as $k => $v)
                            <option value="{{ $k }}" {{ $fieldname == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="Search[status_type]" class="form-control">
                        <option value="">Select Type</option>
                        @foreach($status_opt as $k => $v)
                            <option value="{{ $k }}" {{ $status_type == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ !empty($date_from) ? \Carbon\Carbon::parse($date_from)->format('m/d/Y') : '' }}" placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ !empty($date_to) ? \Carbon\Carbon::parse($date_to)->format('m/d/Y') : '' }}" placeholder="Date Range To">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                    <button type="submit" name="search" value="EXPORT" class="btn btn-primary"><i class="icon-file-excel"></i> EXPORT</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        @include('cloud.hitch.reports._table')
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
