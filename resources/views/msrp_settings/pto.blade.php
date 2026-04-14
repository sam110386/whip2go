@extends('layouts.main')

@section('title', $title_for_layout ?? 'PTO Setting')
@section('header_title', $title_for_layout ?? 'PTO Setting')

@section('content')
{{-- Stub: full port from `app/View/MsrpSettings/pto.ctp` pending layout/forms migration. --}}
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-flat">
            <div class="panel-heading">
                <h6 class="panel-title">PTO (Path To Ownership) goal bands</h6>
            </div>
            <div class="panel-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <p class="text-muted">
                    Legacy form posts to <code>/msrp_settings/pto</code> with <code>data[n][PtoSetting][…]</code> fields.
                    Loaded rows: {{ count($ptoRequestData ?? []) }}.
                </p>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-flat">
            <div class="panel-heading">
                <h6 class="panel-title">Sync dynamic day rent</h6>
            </div>
            <div class="panel-body">
                <p class="text-muted">AJAX endpoint: <code>POST /msrp_settings/syncDayRentalToVehicle</code> (expects X-Requested-With for parity with Cake <code>isAjax()</code>).</p>
            </div>
        </div>
    </div>
</div>
@endsection
