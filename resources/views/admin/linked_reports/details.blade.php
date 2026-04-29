@extends('admin.layouts.app')

@section('title', 'Linked report details')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Linked Report</span> - Details #{{ $order->increment_id ?? $order->id }}
                </h4>
            </div>
            <div class="heading-elements">
                <a href="/cloud/linked_reports/index" class="btn btn-default">Back</a>
            </div>
        </div>

        <div class="breadcrumb-line">
            <ul class="breadcrumb">
                <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
                <li><a href="/cloud/linked_reports/index">Linked Reports</a></li>
                <li class="active">Details</li>
            </ul>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <div class="panel">
            <div class="panel-body" id="listing">
                <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><td><strong>Status</strong></td><td>{{ $order->status }}</td></tr>
                    <tr><td><strong>Start</strong></td><td>{{ $order->start_datetime }}</td></tr>
                    <tr><td><strong>End</strong></td><td>{{ $order->end_datetime }}</td></tr>
                </table>
                </div>
            </div>
        </div>
    </div>
@endsection
