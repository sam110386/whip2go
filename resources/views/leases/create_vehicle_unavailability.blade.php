{{-- Stub: port from `app/View/Leases/create_vehicle_unavailability.ctp` (FullCalendar + AJAX to `/leases/load`, `/leases/addunavailability`, `/leases/remove/{id}`). --}}
@extends('layouts.main')

@section('title', 'Vehicle unavailability')

@section('content')
    <div class="page-header" style="margin-bottom:16px;">
        <h1 class="text-semibold" style="margin:0;">Vehicle unavailability</h1>
    </div>
    <p class="text-muted">Vehicle ID: {{ (int) ($vehicleid ?? 0) }}</p>
    <div id="calendar"></div>
@endsection
