@extends('layouts.admin')

@section('title', !empty($offer) ? 'Edit Vehicle Offer' : 'Add Vehicle Offer')

@section('content')
    <h1>{{ !empty($offer) ? 'Edit Vehicle Offer' : 'Add Vehicle Offer' }}</h1>
    <form method="POST" action="{{ $basePath }}/add{{ !empty($offer->id) ? '/' . base64_encode((string)$offer->id) : '' }}">
        @csrf
        <label>Dealer user id<br><input type="number" name="VehicleOffer[user_id]" value="{{ $offer->user_id ?? '' }}" required></label><br><br>
        <label>Renter user id<br><input type="number" name="VehicleOffer[renter_id]" value="{{ $offer->renter_id ?? '' }}"></label><br><br>
        <label>Vehicle id<br><input type="number" name="VehicleOffer[vehicle_id]" value="{{ $offer->vehicle_id ?? '' }}" required></label><br><br>
        <label>Offer price<br><input type="number" step="0.01" name="VehicleOffer[offer_price]" value="{{ $offer->offer_price ?? 0 }}"></label><br><br>
        <label>Finance type<br><input type="text" name="VehicleOffer[finance_type]" value="{{ $offer->finance_type ?? '' }}"></label><br><br>
        <label>Term<br><input type="text" name="VehicleOffer[term]" value="{{ $offer->term ?? '' }}"></label><br><br>
        <label>Down payment<br><input type="number" step="0.01" name="VehicleOffer[down_payment]" value="{{ $offer->down_payment ?? 0 }}"></label><br><br>
        <label>APR<br><input type="number" step="0.01" name="VehicleOffer[apr]" value="{{ $offer->apr ?? 0 }}"></label><br><br>
        <label>Monthly payment<br><input type="number" step="0.01" name="VehicleOffer[monthly_payment]" value="{{ $offer->monthly_payment ?? 0 }}"></label><br><br>
        <label>Status<br><input type="number" name="VehicleOffer[status]" value="{{ $offer->status ?? 0 }}"></label><br><br>
        <label>Note<br><textarea name="VehicleOffer[note]" rows="3">{{ $offer->note ?? '' }}</textarea></label><br><br>
        <button type="submit">Save</button>
        <a href="{{ $basePath }}/index" style="margin-left:10px;">Cancel</a>
    </form>
@endsection

