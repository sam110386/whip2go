@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Page not found')

@section('content')
    <section class="container" style="padding: 3rem 1rem;">
        <h1>404</h1>
        <p>The page you requested could not be found.</p>
    </section>
@endsection
