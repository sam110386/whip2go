{{-- Stub: port from `app/View/Leases/admin_create_vehicle_unavailability.ctp` (admin AJAX: `/admin/leases/load`, `/admin/leases/addunavailability`, `/admin/leases/remove/{id}`). --}}
@extends('layouts.admin')

@section('title', 'Vehicle unavailability (admin)')

@section('content')
    <div class="page-header" style="margin-bottom:16px;">
        <h1 class="text-semibold" style="margin:0;">Vehicle unavailability</h1>
    </div>
    <p class="text-muted">Vehicle ID: {{ (int) ($vehicleid ?? 0) }}</p>
    <div id="calendar"></div>
@endsection
