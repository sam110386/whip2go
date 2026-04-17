@extends('admin.layouts.app')

@section('title', 'Credit/Debit Payment Details')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Customer Balance</span> - Credit/Debit Payment Details
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('admin/users/index') }}">Users</a></li>
            <li><a href="{{ url('admin/customer_balances/index') }}">Customer Balances</a></li>
            <li class="active">Payment Details</li>
        </ul>
    </div>
</div>

<div class="content">
    @include('partials.flash')

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-flat border-top-info">
                <div class="panel-heading">
                    <h5 class="panel-title">Charge History</h5>
                </div>

                <div class="panel-body">
                    <div class="list-feed">
                        @forelse ($balances as $bal)
                            <div class="list-feed-item border-slate">
                                <div class="text-muted text-size-small mb-5">{{ $bal->created }}</div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="text-semibold">Amt: ${{ number_format($bal->credit, 2) }} / ${{ number_format($bal->debit, 2) }}</div>
                                        <div class="text-size-small">Balance: <span class="text-primary text-semibold">${{ (int)$bal->balance === 2 ? '0.00' : number_format($bal->balance, 2) }}</span></div>
                                    </div>
                                    <div class="col-sm-6 text-right">
                                        <span class="label label-flat border-slate text-slate-800">{{ ucfirst((string)$bal->chargetype) }}</span>
                                    </div>
                                </div>
                                <div class="mt-5 text-size-small bg-light p-5 border-radius-small">
                                    <strong>Schedule:</strong> {{ ucfirst((string)$bal->installment_type) }} ({{ number_format($bal->installment, 2) }}/cycle)
                                </div>
                                @if($bal->last_processed)
                                    <div class="text-size-mini text-muted mt-5">
                                        <i class="icon-checkmark-circle2 text-size-mini"></i> Last Processed: {{ $formatDt($bal->last_processed) }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-muted p-20">No balance history found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-flat border-top-success">
                <div class="panel-heading">
                    <h5 class="panel-title">Booking Payments (Type 6)</h5>
                </div>

                <div class="panel-body">
                    <div class="list-feed">
                        @forelse ($payments as $pay)
                            <div class="list-feed-item border-success">
                                <div class="text-muted text-size-small mb-5">{{ $formatDt(isset($pay->created) ? (string)$pay->created : null) }}</div>
                                <div class="row">
                                    <div class="col-sm-8">
                                        <div class="text-semibold text-success">Amount: ${{ number_format($pay->amount, 2) }}</div>
                                        <div class="text-muted text-size-small">Booking: <a href="{{ url('admin/bookings/view', base64_encode((string)$pay->cs_order_id ?? '')) }}" class="text-semibold">{{ $pay->increment_id }}</a></div>
                                    </div>
                                    <div class="col-sm-4 text-right">
                                        <span class="label label-success">Paid</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted p-20">No matching payments found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-right">
        <a href="{{ url('admin/customer_balances/index') }}" class="btn btn-default"><i class="icon-arrow-left8 position-left"></i> Back to List</a>
    </div>
</div>
@endsection
