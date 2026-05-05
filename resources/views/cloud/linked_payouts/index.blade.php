@extends('layouts.main')

@section('title', 'Payouts Transactions')

@php
    $date_from ??= '';
    $date_to ??= '';
    $payout_id ??= '';
    $user_id ??= '';
    $listtype ??= '';
    $dealers ??= [];
    $limit ??= 50;
@endphp

@push('styles')
<link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
<style type="text/css">
    tbody tr{cursor: pointer;}
    .table>thead>tr>th,
    .table>tbody>tr>th,
    .table>tfoot>tr>th,
    .table>thead>tr>td,
    .table>tbody>tr>td,
    .table>tfoot>tr>td { padding: 5px; }
</style>
@endpush

@push('scripts')
<script src="{{ legacy_asset('js/select2.js') }}"></script>
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery('#SearchDateTo').datepicker({dateFormat: 'mm/dd/yy'});
    });
    function getTransactions(payoutid) {
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>',
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"cloud/linked_payouts/transactions", {payoutid:payoutid, _token: '{{ csrf_token() }}'}, function (data) {
            jQuery.unblockUI();
            $("#myModal .modal-content").html(data);
            $("#myModal").modal('show');
        });
        return false;
    }
</script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage </span>- Payouts Transactions</h4>
        </div>
        <div class="heading-elements">
            @if(empty($listtype))
                <a href="{{ url('cloud/linked_payouts/index?Search[listtype]=all') }}" class="btn btn-success">Show All</a>
            @else
                <a href="{{ url('cloud/linked_payouts/index') }}" class="btn btn-success">Show Batches</a>
            @endif
        </div>
    </div>
</div>

<div class="row">
    @includeif('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ url('cloud/linked_payouts/index') }}" id="frmSearchadmin" name="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group">
                <div class="col-md-3">
                    <select name="Search[user_id]" class="form-control" style="width:100%;">
                        <option value="">Dealer..</option>
                        @foreach ($dealers as $id => $name)
                            <option value="{{ $id }}" @selected((string) $user_id === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[payout_id]" class="form-control" value="{{ $payout_id }}" placeholder="Payout #">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ $date_from }}" placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ $date_to }}" placeholder="Date Range To">
                </div>
                <div class="col-md-3">
                    <input type="hidden" name="Search[listtype]" value="{{ $listtype }}">
                    <button type="submit" name="search" value="search" class="btn btn-primary">&nbsp;&nbsp;SEARCH&nbsp;&nbsp;</button>
                    <button type="submit" name="search" value="EXPORT" class="btn btn-primary">EXPORT</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="listing">
        <div class="table-responsive">
            @if (empty($listtype))
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', ['columns' => [
                                ['title' => 'Payout#', 'field' => 'id'],
                                ['title' => 'Dealer', 'field' => 'user_id'],
                                ['title' => 'Processed on', 'field' => 'processed_on'],
                                ['title' => 'Amount', 'field' => 'amount'],
                                ['title' => 'Actions', 'sortable' => false]
                            ]])
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payoutlists as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->user_id }}</td>
                                <td>{{ $p->processed_on }}</td>
                                <td>{{ number_format((float) ($p->amount ?? 0), 2) }}</td>
                                <td>
                                    <a href="javascript:;" onclick="return getTransactions({{ (int) $p->id }})" class="btn btn-default btn-xs">Transactions</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" align="center">No payouts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            @include('partials.dispacher.sortable_header', ['columns' => [
                                ['title' => 'Payout#', 'field' => 'cs_payout_id'],
                                ['title' => 'Booking', 'field' => 'increment_id'],
                                ['title' => 'Vehicle', 'field' => 'vehicle_name'],
                                ['title' => 'Driver', 'field' => 'renter_first_name'],
                                ['title' => 'Type', 'field' => 'type'],
                                ['title' => 'Amount', 'field' => 'amount']
                            ]])
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payoutlists as $p)
                            <tr>
                                <td>{{ $p->cs_payout_id }}</td>
                                <td>{{ $p->increment_id }}</td>
                                <td>{{ $p->vehicle_name }}</td>
                                <td>{{ trim(($p->renter_first_name ?? '') . ' ' . ($p->renter_last_name ?? '')) }}</td>
                                <td>{{ $p->type }}</td>
                                <td>{{ number_format((float) $p->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" align="center">No transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
        @include('partials.dispacher.paging_box', ['paginator' => $payoutlists, 'limit' => $limit])
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
