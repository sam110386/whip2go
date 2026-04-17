@extends('admin.layouts.app')

@section('title', 'Vehicle Offer Details')

@section('content')
    <p><a href="{{ $basePath }}/index">← Back</a></p>
    <h1>Vehicle offer #{{ $offer->id }}</h1>
    <table style="border-collapse:collapse; font-size:13px;">
        <tr><td style="padding:6px 12px 6px 0;"><strong>Vehicle</strong></td><td>{{ $offer->vehicle_unique_id }} - {{ $offer->vehicle_name }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>Dealer</strong></td><td>{{ trim(($offer->owner_first_name ?? '') . ' ' . ($offer->owner_last_name ?? '')) }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>Renter</strong></td><td>{{ trim(($offer->renter_first_name ?? '') . ' ' . ($offer->renter_last_name ?? '')) }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>Offer price</strong></td><td>{{ $offer->offer_price }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>Finance type</strong></td><td>{{ $offer->finance_type }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>Term</strong></td><td>{{ $offer->term }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>Status</strong></td><td>{{ $offer->status }}</td></tr>
        <tr><td style="padding:6px 12px 6px 0;"><strong>Note</strong></td><td>{{ $offer->note }}</td></tr>
    </table>
@endsection

