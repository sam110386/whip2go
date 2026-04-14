{{-- Stub: port from `app/View/Leases/create_vehicle_lease.ctp` — form posts to `/leases/createVehicleLease/{{ $vehicleIdEncoded }}` with `data[CsLease][...]` fields. --}}
@extends('layouts.dispacher')

@section('title', $title_for_layout ?? 'Vehicle Lease')

@section('content')
    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif

    @php
        $v = $data['Vehicle'] ?? [];
        $lease = $data['CsLease'] ?? [];
    @endphp

    <form method="post" action="{{ url('/leases/create_vehicle_lease/' . ($vehicleIdEncoded ?? '')) }}" name="triplogForm" id="triplogForm" class="form-horizontal">
        @csrf
        <div class="row">
            <div class="col-sm-5">
                <div class="panel panel-flat">
                    <div class="panel-heading">
                        <h5 class="panel-title">Define Vehicle Lease</h5>
                    </div>
                    <div class="panel-body">
                        <fieldset>
                            <legend class="text-semibold">Enter All Information</legend>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Vehicle :</label>
                                <div class="col-lg-8">
                                    {{ $v['vehicle_unique_id'] ?? '' }}
                                    <input type="hidden" name="data[CsLease][vehicle_id]" value="{{ $v['id'] ?? '' }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">Address :</label>
                                <div class="col-lg-8">
                                    <input type="text" class="required textfield form-control" name="data[CsLease][pickup_address]" id="CsLeasePickupAddress" placeholder="Vehicle Address" value="{{ old('data.CsLease.pickup_address', $lease['pickup_address'] ?? ($v['address'] ?? '')) }}">
                                    <input type="hidden" name="data[CsLease][lat]" id="CsLeaseLat" value="{{ old('data.CsLease.lat', $lease['lat'] ?? ($v['lat'] ?? '')) }}">
                                    <input type="hidden" name="data[CsLease][lng]" id="CsLeaseLng" value="{{ old('data.CsLease.lng', $lease['lng'] ?? ($v['lng'] ?? '')) }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">&nbsp;</label>
                                <div class="col-lg-8">
                                    <button type="submit" class="focus_text btn no-margin" id="dispatchBtn">Create</button>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
            </div>
            <div class="col-sm-7">
                <div class="panel panel-flat">
                    <div class="panel-heading">
                        <h5 class="panel-title">Schedule Information</h5>
                    </div>
                    <div class="panel-body">
                        <legend class="text-semibold">&nbsp;</legend>
                        <div class="row form-group">
                            <div class="col-md-2">Date From</div>
                            <div class="col-md-3">
                                <input type="text" name="data[CsLease][start_date]" id="daterangefrom" class="form-control required date" value="{{ old('data.CsLease.start_date', $lease['start_date'] ?? '') }}">
                            </div>
                            <div class="col-md-1">To</div>
                            <div class="col-md-3">
                                <input type="text" name="data[CsLease][end_date]" id="daterangeto" class="form-control required date" value="{{ old('data.CsLease.end_date', $lease['end_date'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Any Details</label>
                            <div class="col-lg-8">
                                <textarea class="form-control" name="data[CsLease][details]" cols="3" rows="3">{{ old('data.CsLease.details', $lease['details'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="data[Lease][id]" value="{{ $lease['id'] ?? '' }}">
    </form>
@endsection

@push('scripts')
    @if(!empty($googleMapsKey))
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=places"></script>
    @endif
@endpush
