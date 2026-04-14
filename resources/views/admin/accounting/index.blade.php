@extends('layouts.admin')

@section('content')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery('#SearchDateTo').datepicker({dateFormat: 'mm/dd/yy'});
    });
</script>
<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Accounting</span> - Reports</h4>
        </div>
    </div>
</div>
<div class="row ">
    @include('partials.flash')
</div>
<div class="panel">
    <form action="{{ url('admin/accounting/reports/index/' . $userid) }}" method="POST" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
        @csrf
        <div class="panel-body">
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
                <select name="Search[rtype]" class="form-control">
                    <option value="">Payment</option>
                    <option value="D" {{ $rtype == 'D' ? 'selected' : '' }}>Debit</option>
                    <option value="C" {{ $rtype == 'C' ? 'selected' : '' }}>Credit</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="Search[type]" class="form-control">
                    <option value="">Type</option>
                    @foreach($reportlib->getPaymentType(true) as $k => $v)
                        <option value="{{ $k }}" {{ $type == $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="submit" name="search" value="APPLY" class="btn btn-primary">
            </div>
        </div>
    </form>
</div>
<div class="panel">
    <div class="panel-body">
        <div style="width:100%; overflow: visible;">
            @if($reportlists->count())
                <div class="text-center">{{ $reportlists->appends(request()->query())->links() }}</div>
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive table-bordered">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Running Bal.</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Action</th>
                            <th>Booking#</th>
                            <th>Transaction</th>
                            <th style="width:160px;">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBal = 0;
                            $totalDebit = $totalCredit = 0;
                            $reversedList = collect($reportlists->items())->reverse();
                            $finalRows = [];
                        @endphp
                        @foreach($reversedList as $trip)
                            @php
                                if ($trip->rtype == 'C') { $totalCredit += $trip->amt; }
                                elseif ($trip->rtype == 'D') { $totalDebit += $trip->amt; }
                                $runningBal = $trip->rtype == 'C'
                                    ? sprintf('%0.2f', ($runningBal + $trip->amt))
                                    : sprintf('%0.2f', ($runningBal - $trip->amt));
                                $finalRows[] = (object) array_merge((array) $trip, ['running_bal' => $runningBal]);
                            @endphp
                        @endforeach
                        <tr>
                            <td><strong>TOTAL</strong></td>
                            <td><strong>{{ $totalDebit }}</strong></td>
                            <td><strong>{{ $totalCredit }}</strong></td>
                            <td><strong>{{ $runningBal }}</strong></td>
                            <td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                        @foreach(array_reverse($finalRows) as $trip)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($trip->created)->timezone($timezone)->format('m/d/Y h:i A') }}</td>
                                <td>{{ $trip->rtype == 'D' ? $trip->amt : '' }}</td>
                                <td>{{ $trip->rtype == 'C' ? $trip->amt : '' }}</td>
                                <td>{{ $trip->running_bal }}</td>
                                <td>{{ $reportlib->getPaymentType(false, $trip->type) }}</td>
                                <td>{{ ucfirst($trip->source) }}</td>
                                <td>{{ $reportlib->getPaymentTypeAction($trip->type, $trip->rtype, $trip->source) }}</td>
                                <td>
                                    @if(!empty($trip->increment_id))
                                        <a href="javascript:;" onclick="bookingDetail({{ $trip->cs_order_id }})">{{ $trip->increment_id }}</a>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($trip->transaction_id))
                                        @if($trip->type == 12)
                                            <a href="javascript:;" onclick="payoutDetail('{{ $trip->transaction_id }}')">{{ $trip->transaction_id }}</a>
                                        @else
                                            <a href="javascript:;" onclick="transactionDetail('{{ $trip->transaction_id }}')">{{ $trip->transaction_id }}</a>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $trip->note }}</td>
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
    </div>
</div>
<script src="{{ asset('Accounting/js/report.js') }}"></script>
@endsection
