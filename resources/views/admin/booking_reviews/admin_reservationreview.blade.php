@extends('admin.layouts.app')

@section('title', 'Reservation pickup review')

@section('content')
    <h1>Reservation pickup review</h1>
    @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif

    @php $cr = $CsOrderReview['CsOrderReview']; @endphp

    <form method="post" action="{{ $basePath }}/admin_reservationreview/{{ base64_encode((string)$orderid) }}">
        <input type="hidden" name="CsOrderReview[id]" value="{{ $cr['id'] ?? '' }}">
        <p><label>Condition report<br>
            <textarea name="CsOrderReview[details]" rows="5" style="width:100%;">{{ $cr['details'] ?? '' }}</textarea>
        </label></p>
        <p><label>Odometer<br>
            <input type="text" name="CsOrderReview[mileage]" value="{{ $cr['mileage'] ?? 0 }}">
        </label></p>
        <p><button type="submit">Save</button>
            <a href="/admin/vehicle_reservations/index">Cancel</a></p>
    </form>

    <h3>Pickup data</h3>
    @if(!empty($pickup_data))
        <pre style="background:#f5f5f5; padding:10px;">{{ json_encode($pickup_data, JSON_PRETTY_PRINT) }}</pre>
    @else
        <p>No pickup snapshot.</p>
    @endif
@endsection
