@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Server error')

@section('content')
    <section class="container" style="padding: 3rem 1rem;">
        <h1>500</h1>
        <p>Something went wrong on our side. Please try again later.</p>
    </section>
@endsection
