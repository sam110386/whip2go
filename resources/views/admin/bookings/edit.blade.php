@extends('admin.layouts.app')

@section('title', 'Edit Booking')

@section('content')
    <h1>Edit booking #{{ $order->increment_id ?? $order->id }}</h1>
    <form method="POST" action="{{ str_contains(request()->path(), 'cloud/') ? '/cloud/linked_bookings/editsave' : '/admin/bookings/editsave' }}">
        @csrf
        <input type="hidden" name="CsOrder[id]" value="{{ $order->id }}">
        <label>Start datetime<br><input type="text" name="CsOrder[start_datetime]" value="{{ $order->start_datetime ?? '' }}"></label><br><br>
        <label>End datetime<br><input type="text" name="CsOrder[end_datetime]" value="{{ $order->end_datetime ?? '' }}"></label><br><br>
        <label>Rent<br><input type="number" step="0.01" name="CsOrder[rent]" value="{{ $order->rent ?? 0 }}"></label><br><br>
        <label>Tax<br><input type="number" step="0.01" name="CsOrder[tax]" value="{{ $order->tax ?? 0 }}"></label><br><br>
        <label>DIA fee<br><input type="number" step="0.01" name="CsOrder[dia_fee]" value="{{ $order->dia_fee ?? 0 }}"></label><br><br>
        <label>Status<br><input type="number" name="CsOrder[status]" value="{{ $order->status ?? 0 }}"></label><br><br>
        <label>Cancel note<br><textarea name="CsOrder[cancel_note]" rows="3">{{ $order->cancel_note ?? '' }}</textarea></label><br><br>
        <button type="submit">Save</button>
    </form>
@endsection

