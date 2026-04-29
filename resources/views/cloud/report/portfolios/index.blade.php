@extends('layouts.admin')
@section('title', 'Fleet - P&L')
@section('content')
<script src="{{ asset('js/report/papaparser.js') }}"></script>
<script src="{{ asset('js/report/excellentexport.js') }}"></script>
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("body").addClass('sidebar-xs');
    });
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Fleet </span> - P&L</h4>
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
    <div class="panel-body">
        <form method="POST" action="{{ url('/cloud/report/portfolios') }}" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <div class="row pb-10">
                <div class="col-md-12">
                    <div class="col-md-4">
                        <select name="Search[user_id]" id="SearchUserId" class="form-control" style="width:100%;">
                            <option value="">Select Dealer..</option>
                            @foreach ($dealers ?? [] as $id => $label)
                                <option value="{{ $id }}" @selected(old('Search.user_id', $user_id ?? '') == $id)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                    </div>
                    <div class="col-md-2">
                        <a href="#" download="portfolio.csv" class="btn btn-primary" onclick="return ExcellentExport.csv(this, 'portfolio');">Export</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        @if(!empty($vehicles))
            @include('admin.report.elements._portfolio')
            <style type="text/css">
                .table.panel{border-color:unset;border: none;}
            </style>
        @else
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <td colspan="7" class="text-center">No record found</td>
                    </tr>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
