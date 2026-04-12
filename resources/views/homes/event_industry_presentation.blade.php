@extends('layouts.driveitaway')

@section('title', $title_for_layout ?? 'Come See Us at Our Next Event or Industry Presentation')

@section('content')
@include('homes.partials.driveitaway._event_industry_presentation')
@endsection
