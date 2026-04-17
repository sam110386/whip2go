@extends('admin.layouts.app')

@section('title', 'Vehicle Last Location')

@section('content')
    <h1>Vehicle Last Location</h1>
    @if(!empty($vehicle))
        <div>Vehicle: {{ data_get($vehicle, 'vehicle_name', data_get($vehicle, 'id')) }}</div>
    @endif
    <p>{{ data_get($vehicleLocation, 'message', 'Location service pending migration') }}</p>
    <a href="{{ $returnListUrl ?? '/admin/vehicles/index' }}">Back</a>
@endsection

