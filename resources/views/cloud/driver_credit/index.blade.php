@extends('layouts.main')

@section('title', 'Credits Logs')

@push('scripts')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
        jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
    });
</script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Credit Logs</h4>
        </div>
        <div class="heading-elements">
            <div class="btn-group ">
                <button type="button" class="btn bg-brown dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Add New <i class="icon-menu7 position-right"></i> <span class="caret"></span></button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li><a href="{{ url('driver_credit/records/credit') }}">Direct To Driver</a></li>
                    <li class="divider"></li>
                    <li><a href="{{ url('driver_credit/records/creditdriver') }}">To Booking</a></li>
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
        <form method="POST" action="{{ url('driver_credit/records/index') }}" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ !empty($dateFrom) ? \Carbon\Carbon::parse($dateFrom)->format('m/d/Y') : '' }}" placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ !empty($dateTo) ? \Carbon\Carbon::parse($dateTo)->format('m/d/Y') : '' }}" placeholder="Date Range To">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="SEARCH" class="btn btn-primary">&nbsp;&nbsp;SEARCH&nbsp;&nbsp;</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        @include('cloud.driver_credit._index')
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
