@extends('admin.layouts.app')

@section('title', 'Manage Linked Payouts')

@php
    $date_from ??= '';
    $date_to ??= '';
    $payout_id ??= '';
    $user_id ??= '';
    $listtype ??= '';
    $dealers ??= [];
    $limit ??= 50;
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> Linked Payouts
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('cloud/linked_payouts/index') }}">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-2">
                            {{ 'From :' }}
                            <input type="date" name="Search[date_from]" class="form-control" value="{{ $date_from }}">
                        </div>
                        <div class="col-md-2">
                            {{ 'To :' }}
                            <input type="date" name="Search[date_to]" class="form-control" value="{{ $date_to }}">
                        </div>
                        <div class="col-md-2">
                            {{ 'Payout# :' }}
                            <input type="text" name="Search[payout_id]" class="form-control" value="{{ $payout_id }}">
                        </div>
                        <div class="col-md-2">
                            {{ 'Dealer :' }}
                            <select name="Search[user_id]" class="form-control">
                                <option value="">All</option>
                                @foreach ($dealers as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $user_id === (string) $id)>
                                        {{ $name }} ({{ $id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{ 'Mode :' }}
                            <select name="Search[listtype]" class="form-control">
                                <option value="" @selected($listtype === '')>Batches</option>
                                <option value="all" @selected($listtype !== '')>All transactions</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" value="search" class="btn btn-primary">
                                {{ 'Search' }}
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="search" value="EXPORT" class="btn btn-warning">
                                {{ 'Export' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                <div class="table-responsive">
                    @if (empty($listtype))
                        <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                            <thead>
                                <tr>
                                    @include('partials.dispacher.sortable_header', ['columns' => [
                                        ['field' => 'id', 'title' => 'Payout#'],
                                        ['field' => 'user_id', 'title' => 'Dealer'],
                                        ['field' => 'processed_on', 'title' => 'Processed on'],
                                        ['field' => 'amount', 'title' => 'Amount'],
                                        ['field' => 'actions', 'title' => 'Actions', 'sortable' => false]
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
                                            <button type="button" class="btn btn-default btn-xs" onclick="loadTransactions({{ (int) $p->id }})">Transactions</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" align="center">No payouts.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                            <thead>
                                <tr>
                                    @include('partials.dispacher.sortable_header', ['columns' => [
                                        ['field' => 'cs_payout_id', 'title' => 'Payout#'],
                                        ['field' => 'increment_id', 'title' => 'Booking'],
                                        ['field' => 'vehicle_name', 'title' => 'Vehicle'],
                                        ['field' => 'renter_first_name', 'title' => 'Driver'],
                                        ['field' => 'type', 'title' => 'Type'],
                                        ['field' => 'amount', 'title' => 'Amount']
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
                                    <tr>
                                        <td colspan="6" align="center">No transactions.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>

                @include('partials.dispacher.paging_box', ['paginator' => $payoutlists, 'limit' => $limit])
            </div>

            <div id="payout-transactions-modal" class="panel panel-default" style="display:none; margin-top:14px;">
                <div class="panel-body"></div>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection

@push('styles')
    <style type="text/css">
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">
        function loadTransactions(id) {
            fetch('{{ url('cloud/linked_payouts/transactions') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ payoutid: id })
            }).then(r => r.text()).then(function (html) {
                var box = document.getElementById('payout-transactions-modal');
                box.querySelector('.panel-body').innerHTML = html;
                box.style.display = 'block';
            });
        }
    </script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
