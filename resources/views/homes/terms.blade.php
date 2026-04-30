@extends('layouts.without_header_footer')

@section('title', $title_for_layout ?? 'Terms of Service')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('stylenew.css') }}">
@endpush

@section('content')
    @include('homes.partials.terms_html')
@endsection