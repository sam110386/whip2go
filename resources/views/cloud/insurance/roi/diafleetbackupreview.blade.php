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
    function copyToClipboard(textToCopy) {
        // Navigator clipboard api needs a secure context (https)
        if (navigator.clipboard && window.isSecureContext) {
            window.clipboard.writeText(textToCopy);
        } else {
            // Use the 'out of viewport hidden text area' trick
            const textArea = document.createElement("input");
            textArea.value = textToCopy;
                
            // Move textarea out of the viewport so it's not visible
            textArea.style.position = "absolute";
            textArea.style.left = "-999999px";
                
            document.body.prepend(textArea);
            textArea.select();

            try {
                document.execCommand('copy');
            } catch (error) {
                console.error(error);
            } finally {
                textArea.remove();
            }
        }
    }
</script>
<style>
    .provider-logo {
        max-width: 90px;
        width: 100%;
    }

    .provider-list {
        display: inline-flex;
        justify-content: space-between;
        width: 100%;
    }
</style>
<div class="panel">
    <div class="panel-body">
        <div class="form-group">
            @if($booking['OrderDepositRule']['insurance_payer'] == 7)
                <legend>Choose An Authorized Insurance Provider and Get A Quote!</legend>
                <div class="col-lg-6 col-sm-12 text-center">
                    <p><em>Please select one of the buttons below to get an insurance quote. Here is your vehicle information:</em></p>
                </div>
                <div class="col-lg-6 col-sm-12 text-center text-bold">
                    Vehicle : {{ $booking['Vehicle']['year'] }} {{ $booking['Vehicle']['make'] }} {{ $booking['Vehicle']['model'] }}
                </div>
                <div class="col-lg-6 col-sm-12 text-center text-bold">
                    VIN : <a href="javascript:;" onclick="copyToClipboard('{{ $booking['Vehicle']['vin_no'] }}')">{{ $booking['Vehicle']['vin_no'] }} <i class="icon-copy4"></i></a>
                </div>
                <div class="col-lg-6 col-sm-12 text-center">
                    <p><em>Once you've gotten a quote, please copy the quote number and submit a screenshot of the final cost of the policy in the section below.</em></p>
                    <p><label class="text-danger"> DO NOT BUY THE POLICY YET. OUR TEAM NEEDS TO VERIFY THAT THE POLICY INFORMATION IS CORRECT FIRST</label></p>
                </div>
                <legend>Allowed Insurance Providers</legend>
                <div class="col-lg-6 col-sm-12 text-left">
                    @foreach($providers as $provider)
                        <div class="provider-list mt-20">
                            <span class="w-25">
                                @if(!empty($provider['InsuranceProvider']['logo']))
                                    <img src="{{ config('app.url') }}/img/insurance_providers/{{ $provider['InsuranceProvider']['logo'] }}" class="provider-logo"/>
                                @else
                                    {{ $provider['InsuranceProvider']['name'] }}
                                @endif
                            </span>
                            <span class="w-25">
                                {{ $provider['InsuranceProvider']['name'] }}
                            </span>
                            <div class="w-50">
                                <a href="{{ $provider['InsuranceProvider']['link'] }}" target="_blank" class="btn btn-blank btn-danger btn-rounded">Get Quote</a>
                            </div>
                        </div>
                        <div class="provider-list mt-20">
                            <a href="{{ url('insurance/roi/diafleetbackuppopup/' . $orderandusers . '/' . $provider['InsuranceProvider']['id']) }}" class="btn btn-primary w-100">Upload screenshot and quote #</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
