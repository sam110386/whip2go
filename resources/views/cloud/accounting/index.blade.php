@extends('layouts.main')

@section('title', 'Accounting Reports')

@push('scripts')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery('#SearchDateTo').datepicker({dateFormat: 'mm/dd/yy'});
    });
</script>
@endpush

@section('content')
@php
    $keyword ??= '';
    $dateFrom ??= '';
    $dateTo ??= '';
@endphp
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Accounting Reports</h4>
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <form action="{{ url('accounting/reports/index') }}" method="POST" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
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
                    <button type="submit" name="search" value="APPLY" class="btn btn-primary">&nbsp;&nbsp;APPLY&nbsp;&nbsp;</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        <div style="width:100%; overflow: visible;">
            @if($reportlists->count())
                <div class="text-center">{{ $reportlists->appends(request()->query())->links() }}</div>
                <table class="table table-responsive">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllChildCheckboxs" value="1"></th>
                            <th>Booking#</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Type</th>
                            <th>Transaction</th>
                            <th>Note</th>
                            <th>Time</th>
                            <th style="width:80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $reportlib = new \App\Services\Legacy\Reportlib(); @endphp
                        @foreach($reportlists as $trip)
                            <tr>
                                <td><input type="checkbox" name="select[{{ $trip->id }}]" value="{{ $trip->id }}"></td>
                                <td>{{ $trip->increment_id }}</td>
                                <td>{{ $trip->amt }}</td>
                                <td>{{ $trip->rtype == 'D' ? 'Debit' : 'Credit' }}</td>
                                <td>{{ $reportlib->getPaymentType(false, $trip->type) }}</td>
                                <td>{{ $trip->transaction_id }}</td>
                                <td>{{ $trip->note }}</td>
                                <td>{{ \Carbon\Carbon::parse($trip->created)->timezone($trip->timezone ?? config('app.timezone'))->format('m/d/Y h:i A') }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                        <tr><td height="6" colspan="17"></td></tr>
                    </tbody>
                </table>
                <div class="text-center">{{ $reportlists->appends(request()->query())->links() }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <td colspan="6" class="text-center">No record found</td>
                        </tr>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
@endsection
