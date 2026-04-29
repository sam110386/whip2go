@extends('layouts.main')

@section('title', 'Cash Flow Report')

@push('styles')
<link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@push('scripts')
<script src="{{ legacy_asset('js/select2.js') }}"></script>
<script src="{{ legacy_asset('js/report/papaparser.js') }}"></script>
<script src="{{ legacy_asset('js/report/excellentexport.js') }}"></script>
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("body").addClass('sidebar-xs');
    });
</script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Report </span>- Cash Flow</h4>
        </div>
    </div>
</div>

<div class="row">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
</div>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ url('/cloud/report/cashflow') }}" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-4">
                    <select name="Search[user_id]" id="SearchUserId" class="form-control">
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
                    <a href="#" download="cashflow.csv" class="btn btn-primary" onclick="return ExcellentExport.csv(this, 'portfolio');">Export</a>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        @if(!empty($vehicles))
            @include('admin.report.elements._cashflow')
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

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
@endsection
