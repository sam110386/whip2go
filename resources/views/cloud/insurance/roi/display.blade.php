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

<div class="panel">
    <div class="panel-body">
        <div class="form-group">
            @if($insurance_payer == 3 || $insurance_payer == 4)
                <div class="col-lg-6 col-sm-12 text-center">
                    <strong>Download the Confirmation of Insurance Document to give to your broker</strong>
                </div>
                <div class="col-lg-6 col-sm-12 text-center">
                    <a href="{{ url('insurance/roi/downloadinsurance') }}" class="btn btn-primary full-width">Confirmation of Insurance</a>
                </div>
                <div class="col-lg-6 col-sm-12 text-center">
                    <a href="{{ url('insurance/roi/agreement') }}" class="btn btn-primary full-width">Vehicle Agreement</a>
                </div>
                <div class="col-lg-6 col-sm-12 text-center">
                    <a href="{{ url('insurance/roi/popup') }}" class="btn btn-primary full-width">Upload Insurance Documents</a>
                </div>
            @else
                <div class="col-lg-6 col-sm-12 text-center">
                    <strong>DIA will send insurance options soon. Stay tuned.</strong>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
