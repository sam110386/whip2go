@extends('admin.layouts.app')

@section('title', 'Manage Agreement Templates')

@php
    $useridB64 ??= '';
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="{{ url('admin/users/index') }}"><i class="icon-arrow-left52 position-left"></i></a>
                    <span class="text-semibold">Manage</span>
                    Agreement Templates
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title text-center">Templates Management</h5>
        </div>
        <div class="panel-body form-horizontal">
            <div class="form-group text-center">
                <div class="col-lg-6 mb-10">
                    <a href="{{ url('admin/agreement_templates/rental', $useridB64) }}" class="btn btn-primary btn-block btn-float">
                        <i class="icon-file-text2"></i> <span>Rent Agreement</span>
                    </a>
                </div>
                <div class="col-lg-6 mb-10">
                    <a href="{{ url('admin/agreement_templates/rent_to_own', $useridB64) }}" class="btn btn-primary btn-block btn-float">
                        <i class="icon-file-check"></i> <span>Rent To Own Agreement</span>
                    </a>
                </div>
            </div>
            <div class="form-group text-center">
                <div class="col-lg-6 mb-10">
                    <a href="{{ url('admin/agreement_templates/lease', $useridB64) }}" class="btn btn-primary btn-block btn-float">
                        <i class="icon-file-spreadsheet"></i> <span>Lease Agreement</span>
                    </a>
                </div>
                <div class="col-lg-6 mb-10">
                    <a href="{{ url('admin/agreement_templates/lease_to_own', $useridB64) }}" class="btn btn-primary btn-block btn-float">
                        <i class="icon-file-attachment"></i> <span>Lease To Own Agreement</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
