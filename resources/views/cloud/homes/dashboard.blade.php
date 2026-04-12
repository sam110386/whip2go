{{-- Port of Cake `app/View/Homes/cloud_dashboard.ctp` — cloud dealer shell welcome. --}}
@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'Dashboard')
@section('header_title', $title_for_layout ?? 'Dashboard')

@section('content')
    <div class="panel panel-flat">
        <div class="panel-body">
            <p class="text-muted">Cloud console</p>
            <h4 class="text-semibold">Welcome to Admin Panel</h4>
            <p>Use the menu to manage linked inventory, bookings, and settings.</p>
        </div>
    </div>
@endsection
