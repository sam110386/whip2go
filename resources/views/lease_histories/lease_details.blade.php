{{-- Cake `lease_details.ctp` / layout `ajax` — HTML fragment for modal/AJAX. --}}
@extends('layouts.ajax')

@section('content')
@php
    $L = $triplog['Lease'] ?? [];
    $U = $triplog['User'] ?? [];
@endphp
<div class="row">
    <fieldset class="col-lg-12">
        <div class="panel-body">
            <div class="form-group">
                <h3>Lease Details</h3>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">User</label>
                <div class="col-lg-6">{{ $U['unique_code'] ?? '' }}</div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Vehicle#</label>
                <div class="col-lg-6">{{ $L['car_no'] ?? $L['vehicle_unique_id'] ?? '' }}</div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Job#</label>
                <div class="col-lg-6">{{ $L['id'] ?? '' }}</div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Start date</label>
                <div class="col-lg-6">
                    @if(!empty($L['pickup_date']))
                        @php
                            try {
                                $pdLabel = \Illuminate\Support\Carbon::parse($L['pickup_date'])->format('m/d/Y');
                            } catch (\Throwable $e) {
                                $pdLabel = (string) $L['pickup_date'];
                            }
                        @endphp
                        {{ $pdLabel }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Start time</label>
                <div class="col-lg-6">{{ $L['pickup_time'] ?? 'N/A' }}</div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Location</label>
                <div class="col-lg-6">{{ $L['pickup_address'] ?? 'N/A' }}</div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Details</label>
                <div class="col-lg-6">{{ $L['details'] ?? 'N/A' }}</div>
            </div>
            @if(isset($L['black_car_fund']))
                <div class="form-group">
                    <label class="col-lg-3 control-label">Black car fund (est.)</label>
                    <div class="col-lg-6">{{ $L['black_car_fund'] }}</div>
                </div>
            @endif
        </div>
    </fieldset>
</div>
@endsection
