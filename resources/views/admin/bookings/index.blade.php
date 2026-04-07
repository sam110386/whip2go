@extends('admin.layouts.app')

@section('title', 'Rental Orders')

@section('content')
    <h1>Rental orders</h1>
    <p style="font-size:13px;color:#555;">Active and in-progress bookings (status other than canceled or completed). Full Cake workflow actions are not wired here yet.</p>
    <div id="update_log" style="overflow:auto;">
        @include('admin.bookings.booking_table', ['trips' => $trips])
    </div>
@endsection
