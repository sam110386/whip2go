@extends('admin.layouts.app')

@section('title', 'Vehicle Last Location')

@section('content')
    <div class="panel">
        <section class="right_content">
            <iframe width="100%" height="650" frameborder="0" style="border:0"
                src="{{ sprintf('https://www.google.com/maps/embed/v1/place?key=%s&q=%s,%s', config('legacy.GOOGLE_MAPS_API_KEY'), $vehicleLocation['lat'], $vehicleLocation['lng']) }}"
                allowfullscreen>
            </iframe>
        </section>
    </div>
@endsection