@extends('layouts.without_header_footer')

@section('title', $title_for_layout ?? 'Privacy Policy')

@push('css')
    <link rel="stylesheet" href="{{ legacy_asset('stylenew.css') }}">
@endpush

@section('content')
    @include('homes.partials.privacy_html')
@endsection