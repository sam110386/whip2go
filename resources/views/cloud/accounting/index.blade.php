@extends('layouts.main')

@section('content')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery('#SearchDateTo').datepicker({dateFormat: 'mm/dd/yy'});
    });
</script>
<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;">
            <h3 style="width: 40%; float: left; padding: 10px;">Accounting Reports</h3>
        </section>
        @include('partials.flash')
        <form action="{{ url('accounting/reports/index') }}" method="POST" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group" style="padding:0.35em 0.625em 0.75em">
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
                    <input type="submit" name="search" value="APPLY" class="btn btn-primary">
                </div>
            </fieldset>
        </form>

        <div style="width:100%; overflow: visible;">
            @if($reportlists->count())
                <div class="text-center">{{ $reportlists->appends(request()->query())->links() }}</div>
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
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
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="borderTable">
                    <tr>
                        <td colspan="6" align="center">No record found</td>
                    </tr>
                </table>
            @endif
        </div>
    </section>
</div>
@endsection
