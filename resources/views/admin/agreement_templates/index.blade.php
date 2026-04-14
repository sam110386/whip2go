@extends('layouts.admin')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold"></span> {{ $listTitle }}</h4>
        </div>
    </div>
</div>
<div class="row ">
    @include('partials.flash')
</div>
<div class="panel">
    <div class="panel-body form-horizontal">
        <div class="form-group">
            <div class="col-lg-6">
                <a href="{{ url('admin/agreement_templates/rental/' . base64_encode($userid)) }}" class="btn btn-primary">Rent Agreement</a>
            </div>
            <div class="col-lg-6">
                <a href="{{ url('admin/agreement_templates/rent_to_own/' . base64_encode($userid)) }}" class="btn btn-primary">Rent To Own Agreement</a>
            </div>
        </div>
        <div class="form-group">
            <div class="col-lg-6">
                <a href="{{ url('admin/agreement_templates/lease/' . base64_encode($userid)) }}" class="btn btn-primary">Lease Agreement</a>
            </div>
            <div class="col-lg-6">
                <a href="{{ url('admin/agreement_templates/lease_to_own/' . base64_encode($userid)) }}" class="btn btn-primary">Lease To Own Agreement</a>
            </div>
        </div>
    </div>
</div>
@endsection
