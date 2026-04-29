@extends('layouts.main')

@section('title', 'Payment Logs')

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
    $dateFrom ??= '';
    $dateTo ??= '';
    $keyword ??= '';
    $limit ??= 50;
@endphp
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Payment Logs</h4>
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ url('cloud/payment_logs/index') }}" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ $dateFrom }}" placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ $dateTo }}" placeholder="Date Range To">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" placeholder="Keyword">
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
        <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Txn ID</th>
                    <th>Reference</th>
                    <th>Message</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>{{ $r->user_id ?? '' }}</td>
                        <td>{{ $r->transaction_id ?? '' }}</td>
                        <td>{{ $r->reference_id ?? '' }}</td>
                        <td>{{ $r->message ?? '' }}</td>
                        <td>{{ $r->created ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        {{ $rows->links() }}
    </div>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
@endsection
