@extends('layouts.admin')

@section('title', 'Linked Rental Orders')

@section('content')
    <h1>Linked rental orders</h1>
    <p style="font-size:13px;color:#555;">
        Dealer-linked active bookings (cloud scope).
    </p>
    <div id="update_log" style="overflow:auto;">
        @include('admin.linked_bookings.booking_table', ['trips' => $trips])
    </div>
@endsection

