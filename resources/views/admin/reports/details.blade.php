@extends('admin.layouts.app')

@section('title', 'Report details')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Report</span> - Details
                </h4>
            </div>
            <div class="heading-elements">
                <a href="/admin/reports/index" class="btn btn-default">Back</a>
            </div>
        </div>

        <div class="breadcrumb-line">
            <ul class="breadcrumb">
                <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
                <li><a href="/admin/reports/index">Reports</a></li>
                <li class="active">Details</li>
            </ul>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <div class="panel">
            <div class="panel-body" id="listing">
                @include('reports._booking_details_full')
            </div>
        </div>
    </div>
@endsection
