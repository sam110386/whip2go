@extends('layouts.admin')

@section('title', 'Report details')

@section('content')
    <p><a href="/admin/reports/index">← Back</a></p>
    <h1>Report details</h1>
    @include('reports._booking_details_full')
@endsection
