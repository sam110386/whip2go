@extends('admin.layouts.app')

@section('title', 'Initial booking review')

@section('content')
    <h1>Initial damage review</h1>
    @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif

    <p>Order #{{ $orderid }} · Vehicle {{ $CsOrder['CsOrder']['vehicle_id'] ?? '' }}</p>

    <form method="post" action="{{ $basePath }}/initial/{{ base64_encode((string)$orderid) }}">
        <input type="hidden" name="CsOrderReview[id]" value="{{ $CsOrderReview['CsOrderReview']['id'] ?? '' }}">
        <p><label>Condition report<br>
            <textarea name="CsOrderReview[details]" rows="5" style="width:100%;">{{ $CsOrderReview['CsOrderReview']['details'] ?? '' }}</textarea>
        </label></p>
        <p><label>Odometer<br>
            <input type="text" name="CsOrderReview[mileage]" value="{{ $CsOrderReview['CsOrderReview']['mileage'] ?? 0 }}">
        </label></p>
        <p><button type="submit">Save</button>
            <a href="{{ $basePath }}/nonreview">Cancel</a></p>
    </form>

    <h3>Pickup checklist (reference)</h3>
    @if(!empty($pickup_data))
        <pre style="background:#f5f5f5; padding:10px;">{{ json_encode($pickup_data, JSON_PRETTY_PRINT) }}</pre>
    @else
        <p>No pickup snapshot on deposit rule.</p>
    @endif
@endsection
