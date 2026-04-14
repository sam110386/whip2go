@extends('layouts.admin')

@section('title', ($mode ?? 'index') === 'all' ? 'All Pending Booking' : 'Pending Booking')

@section('content')
    <h1>{{ ($mode ?? 'index') === 'all' ? 'All Pending Booking' : 'Pending Booking' }}</h1>
    <p style="font-size:13px;color:#555;">
        Reservation lifecycle migration in progress. Listing and status update actions are enabled.
    </p>

    <form method="get" action="{{ ($mode ?? 'index') === 'all' ? '/admin/vehicle_reservations/all' : '/admin/vehicle_reservations/index' }}" style="margin:10px 0;">
        <label>Rows / page</label>
        <select name="Record[limit]" onchange="this.form.submit()">
            @foreach ([25, 50, 100, 200] as $opt)
                <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
            @endforeach
        </select>
        @if (($mode ?? 'index') === 'index')
            <a href="/admin/vehicle_reservations/all" style="margin-left:10px;">Show all</a>
        @else
            <a href="/admin/vehicle_reservations/index" style="margin-left:10px;">Show pending only</a>
        @endif
    </form>

    <div id="reservation_listing" style="overflow:auto;">
        @include('admin.vehicle_reservations._table', ['bookings' => $bookings, 'mode' => $mode ?? 'index'])
    </div>
@endsection

