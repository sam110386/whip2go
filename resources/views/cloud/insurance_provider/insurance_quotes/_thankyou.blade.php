@extends('layouts.main')
@section('content')
<div class="row ">
    @if(session('flash_message'))
        <div class="alert alert-success">{{ session('flash_message') }}</div>
    @endif
    @if(session('flash_error'))
        <div class="alert alert-danger">{{ session('flash_error') }}</div>
    @endif
</div>
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
<div class="panel">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-12">
                <img src="{{ asset('img/insurance_providers/lincoln-insurance-logo-blue.webp') }}" class="img-responsive mb-3">
            </div>
        </div>
        <div class="form-group"></div>
        <div class="form-group">
            <div class="col-lg-6 col-sm-12 text-center">
                <strong>{{ $title_for_layout }}</strong>
            </div>
        </div>
    </div>
</div>
@endsection
