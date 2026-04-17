{{-- Stub: Cake `edit_lease_details.ctp` / layout `dispacher` — extend dispatcher shell. --}}
@extends('layouts.dispacher')

@section('title', $title_for_layout ?? 'Update Lease Detail')

@section('content')
@php
    $L = $triplog['Lease'] ?? [];
    $U = $triplog['User'] ?? [];
    $leaseIdEnc = base64_encode((string)($L['id'] ?? ''));
@endphp
<div class="panel panel-flat">
    <div class="panel-heading">
        <h5 class="panel-title">{{ $title_for_layout ?? 'Update Lease Detail' }}</h5>
    </div>
    <div class="panel-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="post" action="/lease_histories/edit_lease_details/{{ $leaseIdEnc }}" class="form-horizontal">
            @csrf
            <input type="hidden" name="Lease[id]" value="{{ $L['id'] ?? '' }}">

            <div class="form-group">
                <label class="control-label col-sm-3">User</label>
                <div class="col-sm-6"><p class="form-control-static">{{ $U['unique_code'] ?? '' }}</p></div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">Vehicle#</label>
                <div class="col-sm-6"><p class="form-control-static">{{ $L['car_no'] ?? $L['vehicle_unique_id'] ?? '' }}</p></div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">Job#</label>
                <div class="col-sm-6"><p class="form-control-static">{{ $L['id'] ?? '' }}</p></div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">Start date</label>
                <div class="col-sm-6">
                    @php
                        $pickupVal = '';
                        if (!empty($L['pickup_date'])) {
                            try {
                                $pickupVal = \Illuminate\Support\Carbon::parse($L['pickup_date'])->format('m/d/Y');
                            } catch (\Throwable $e) {
                                $pickupVal = (string) $L['pickup_date'];
                            }
                        }
                    @endphp
                    <input type="text" name="Lease[pickup_date]" class="form-control" value="{{ $pickupVal }}" placeholder="m/d/Y">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">Location</label>
                <div class="col-sm-6">
                    <input type="text" name="Lease[pickup_address]" class="form-control" maxlength="200" value="{{ $L['pickup_address'] ?? '' }}">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-3">Details</label>
                <div class="col-sm-6">
                    <textarea name="Lease[details]" class="form-control" rows="3" maxlength="200">{{ $L['details'] ?? '' }}</textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="/lease_histories/index" class="btn btn-default">Back to list</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
