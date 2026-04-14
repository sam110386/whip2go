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
<div class="row">
<div class="">
    <div class="insuranceprovider-wrap">
        <div class="d-grid">
            <!-- block start here -->
            <div class="w-100 reviewwrap">
                <div class="panel panel-flat">
                    <div class="panel-body">
                        <h2>{{ $thankyou }}</h2>
                    </div>
                </div>
            </div>
            <!-- block end here -->
        </div>
    </div>
</div>
</div>
@endsection
