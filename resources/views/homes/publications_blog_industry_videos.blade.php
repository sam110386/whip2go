@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Publications, Blog & Industry Videos')

@section('content')
    @include('homes.partials.driveitaway._publications_blog_industry_videos')
@endsection