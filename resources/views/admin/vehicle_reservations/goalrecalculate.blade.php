@extends('layouts.admin')

@section('header_title', 'Goal Recalculation')

@section('content')
<div class="panel panel-flat">
    <div class="panel-heading"><h5 class="panel-title">Goal Recalculation</h5></div>
    <div class="panel-body">
        <p class="text-muted">Goal recalculation form — ported from CakePHP admin_goalrecalculate.</p>
        <pre>{{ json_encode($OrderDepositRule ?? [], JSON_PRETTY_PRINT) }}</pre>
        <pre>{{ json_encode($vehicles ?? [], JSON_PRETTY_PRINT) }}</pre>
        <pre>{{ json_encode($VehicleReservationObj ?? [], JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection
