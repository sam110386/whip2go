@extends('layouts.main')
@section('content')
<script type="text/javascript">
    $(document).ready(function() {
        $(".reviewwrap").mouseenter(function() {
            $(this).find('.panel.panel-flat').addClass('animated pulse');
        }).mouseleave(function() {
            $(this).find('.panel.panel-flat').removeClass('animated pulse');
        });
    });
</script>

<div class="row ">
    @if(session('flash_message'))
        <div class="alert alert-success">{{ session('flash_message') }}</div>
    @endif
    @if(session('flash_error'))
        <div class="alert alert-danger">{{ session('flash_error') }}</div>
    @endif
</div>
<div class="row">
    <div class="col-lg-12">
        <img src="{{ asset('img/insurance_providers/lincoln-insurance-logo-blue.webp') }}" class="img-responsive mb-3">
    </div>
</div>
<div class="">
    <div class="insuranceprovider-wrap">
        <div class="d-grid">
            @foreach ($providers as $provider)
                <!-- block start here -->
                <div class="w-100 reviewwrap">
                    <div class="panel panel-flat">
                        <div class="panel-body">
                            <div class="w-100 mb-5">
                                <span class="w-50 mb-5 pull-left">
                                    @if(!empty($provider['InsuranceProvider']['logo']))
                                        <img src="{{ config('app.url') }}/img/insurance_providers/{{ $provider['InsuranceProvider']['logo'] }}" class="provider-logo"/>
                                    @else
                                        {{ $provider['InsuranceProvider']['name'] }}
                                    @endif
                                </span>
                                @if (!empty($provider['InsuranceQuote']['policy_doc']))
                                    <span class="w-50 mt-5 pull-right">
                                        <a href="{{ config('app.url') }}/files/insurancequote/{{ $provider['InsuranceQuote']['policy_doc'] }}" class="label bg-success" target="_blank">Policy Doc</a>
                                    </span>
                                @endif
                            </div>
                            <div class="w-100 mb-5 text-semibold pull-right">
                                <p class="w-100 text-size-base text-right text-warning">${{ $provider['InsuranceQuote']['quote_amount'] }} Paid In Full</p>
                                <p class="w-100 text-size-large text-right">Financed by DIA Partners @ ${{ sprintf('%0.2f', ($provider['InsuranceQuote']['daily_rate'] * 7)) }}/week</p>
                            </div>

                            <div class="w-100 mt-5 text-center">
                                <a href="{{ config('app.url') }}/insuprovider/quotes/finalreview/{{ $provider['InsuranceQuote']['id'] }}/{{ $orderandusers }}" class="btn btn-blank btn-danger btn-rounded">Select</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- block end here -->
            @endforeach
        </div>
    </div>
</div>
@endsection
