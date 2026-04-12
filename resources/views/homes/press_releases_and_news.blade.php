@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Press Releases & In The News')

@section('content')
@include('homes.partials.driveitaway._press_releases_and_news')
@endsection
