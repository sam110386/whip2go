@extends('layouts.main')

@section('title', $title_for_layout)

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">{{ $title_for_layout }}</span></h4>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="row"><div class="col-md-12"><div class="alert alert-success">{{ session('success') }}</div></div></div>
    @endif
    @if (session('error'))
        <div class="row"><div class="col-md-12"><div class="alert alert-danger">{{ session('error') }}</div></div></div>
    @endif

    <div class="panel">
        <div class="panel-body">
            <form method="post" action="/payment_logs/all/{{ base64_encode((string)$orderid) }}" id="frmSearchadmin" name="frmSearchadmin">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            <input id="SearchDateFrom" type="text" class="form-control" maxlength="16" name="Search[date_from]" value="{{ $date_from }}" placeholder="Date From">
                        </div>
                        <div class="col-md-3">
                            <input id="SearchDateTo" type="text" class="form-control" maxlength="16" name="Search[date_to]" value="{{ $date_to }}" placeholder="Date To">
                        </div>
                        <div class="col-md-3">
                            <select name="Search[status]" class="form-control">
                                <option value="">- Status -</option>
                                <option value="1" @selected((string)$status === '1')>Success</option>
                                <option value="2" @selected((string)$status === '2')>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary" value="search">Search</button>
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-top:8px;">
                    <div class="col-md-3">
                        <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                            @foreach ([25, 50, 100, 200] as $opt)
                                <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }} / page</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        <th align="center" style="text-align:center;">#</th>
                        <th align="center" style="text-align:center;">Start</th>
                        <th align="center" style="text-align:center;">End</th>
                        <th align="center" style="text-align:center;">Event</th>
                        <th align="center" style="text-align:center;">Amount</th>
                        <th align="center" style="text-align:center;">Status</th>
                        <th align="center" style="text-align:center;">Transaction#</th>
                        <th align="center" style="text-align:center;">Old Transaction#</th>
                        <th align="center" style="text-align:center;">Note</th>
                        <th align="center" style="text-align:center;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $sib = $allSiblings->get((int)$log->cs_order_id);
                        @endphp
                        <tr>
                            <td>{{ $sib->increment_id ?? '' }}</td>
                            <td>{{ !empty($sib?->start_datetime) ? \Carbon\Carbon::parse($sib->start_datetime)->timezone($sib->timezone ?: 'UTC')->format('Y-m-d h:i A') : '' }}</td>
                            <td>{{ !empty($sib?->end_datetime) ? \Carbon\Carbon::parse($sib->end_datetime)->timezone($sib->timezone ?: 'UTC')->format('Y-m-d h:i A') : '' }}</td>
                            <td>{{ $paymentTypeValue[(int)$log->type] ?? 'Unknown Payment Type' }}</td>
                            <td>{{ $log->amount }}</td>
                            <td>{{ (int)$log->status === 2 ? 'Declined' : 'Success' }}</td>
                            <td>{{ $log->transaction_id }}</td>
                            <td>{{ $log->old_transaction_id }}</td>
                            <td>{{ $log->note }}</td>
                            <td>{{ !empty($log->created) ? \Carbon\Carbon::parse($log->created)->format('Y-m-d h:i A') : '' }}</td>
                        </tr>
                    @empty
                        <tr id="set_hide">
                            <th colspan="10">No Payment Log Available!</th>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($logs instanceof \Illuminate\Contracts\Pagination\Paginator && $logs->hasPages())
                <div style="margin-top:12px;">
                    Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }} ({{ $logs->total() }} total)
                    @if (!$logs->onFirstPage())
                        <a href="{{ $logs->previousPageUrl() }}">Previous</a>
                    @endif
                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}">Next</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom,#SearchDateTo').datepicker({
            dateFormat: 'mm/dd/yy',
            changeMonth: true,
            changeYear: true
        });
    });
</script>
@endpush
