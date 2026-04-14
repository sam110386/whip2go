@extends('layouts.admin')
@section('title', 'Monthly Charges - Report')
@section('content')
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
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Monthly Charges</span> - Report</h4>
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
    <form method="POST" action="{{ url('/admin/report/monthcharges') }}" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-2">
                <input type="text" name="Search[datefrom]" id="SearchDatefrom" class="date form-control" value="{{ old('Search.datefrom', $datefrom ?? '') }}" placeholder="Date from">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[dateto]" id="SearchDateto" class="date form-control" value="{{ old('Search.dateto', $dateto ?? '') }}" placeholder="Date to">
            </div>
            <div class="col-md-2">
                <button type="submit" name="pull" value="search" class="btn btn-primary">Generate Report</button>
            </div>
            <div class="col-md-4">
                <button type="submit" name="export" value="export" class="btn btn-warning pull-right">Export Report</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.report.elements.admin_monthcharge')
    </div>
</div>
@endsection
