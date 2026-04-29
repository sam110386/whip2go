@extends('admin.layouts.app')

@section('title', 'Vehicle Last Location')

@section('content')
    @php
        $returnUrl = $returnListUrl ?? '/admin/vehicles/index';
        $lat = data_get($vehicleLocation, 'lat');
        $lng = data_get($vehicleLocation, 'lng');
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Vehicle</span> - Last Location
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel panel-flat">
        @if (!empty($vehicle))
            <div class="panel-heading">
                <h5 class="panel-title">{{ data_get($vehicle, 'vehicle_name', data_get($vehicle, 'id')) }}</h5>
            </div>
        @endif
        <div class="panel-body">
            @if ($lat && $lng)
                <iframe width="100%" height="650" frameborder="0" class="border-0"
                    src="{{ sprintf('%s?key=%s&q=%s,%s', config('legacy.GOOGLE_MAPS_EMBED_URL'), config('legacy.GOOGLE_MAPS_API_KEY'), $lat, $lng) }}"
                    allowfullscreen></iframe>
            @else
                <p class="text-muted">{{ data_get($vehicleLocation, 'message', 'Location service pending migration') }}</p>
            @endif
        </div>
    </div>
@endsection