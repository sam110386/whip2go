@extends('admin.layouts.app')

@section('title', $title ?? 'Adjust dealer transfer')

@section('content')
    @php
        $backUrl = '/admin/transactions/updatetransaction/' . base64_encode((string) $order->id);
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $title ?? 'Adjust dealer transfer' }}</span>
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <a href="{{ $backUrl }}" class="btn btn-default">
                        <i class="icon-arrow-left16 position-left"></i> Back to order transactions
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">
                Order #{{ $order->increment_id ?? $order->id }}
                &nbsp;|&nbsp; Transfer type: {{ $type }}
                &nbsp;|&nbsp; Total: {{ number_format((float)$total, 2) }}
            </h5>
        </div>
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Amount</th>
                        <th>Transfer ID</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $p)
                        <tr>
                            <td>{{ $p->amount }}</td>
                            <td>{{ $p->transfer_id }}</td>
                            <td>{{ $p->created ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No transfer rows.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
