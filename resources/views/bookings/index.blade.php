@extends('layouts.dispacher')

@section('content')
<div class="page-header">
    <h4><i class="icon-list"></i> {{ $title_for_layout ?? 'My Bookings' }}</h4>
</div>

<div class="panel panel-flat">
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vehicle</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr id="order-row-{{ $order->id }}">
                        <td>{{ $order->id }}</td>
                        <td>
                            {{ $order->vehicle_year ?? '' }}
                            {{ $order->vehicle_make ?? '' }}
                            {{ $order->vehicle_model ?? '' }}
                        </td>
                        <td>{{ $order->start_datetime ?? '' }}</td>
                        <td>{{ $order->end_datetime ?? '' }}</td>
                        <td>
                            @php
                                $statusMap = [0 => 'Pending', 1 => 'Active', 2 => 'Cancelled', 3 => 'Completed'];
                            @endphp
                            {{ $statusMap[(int)($order->status ?? 0)] ?? 'Unknown' }}
                        </td>
                        <td>
                            <a href="/bookings/edit/{{ base64_encode($order->id) }}" class="btn btn-xs btn-primary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No bookings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($orders, 'links'))
        <div class="text-center">
            {!! $orders->links() !!}
        </div>
    @endif
</div>
@endsection
