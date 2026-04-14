@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Error')

@section('content')
    <section class="container" style="padding: 3rem 1rem;">
        <h1>Error</h1>
        <p>A fatal error occurred. Please try again later.</p>
    </section>
@endsection
