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
<div class="panel">
    <div class="panel-body">
        <div class="form-group">
            @if(!empty($cardObj))
                <legend>Use following Credit Card, to purchase insurance</legend>
                <form action="" class="form-horizontal">
                    <div class="form-group">
                        <div class="col-lg-12">
                            <label>Card Number</label>
                            <a href="javascript:;" onclick="copyToClipboard('{{ $cardObj['card_number'] }}')" class="control-label text-bold">{{ $cardObj['card_number'] }} <i class="icon-copy4"></i></a>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-6">
                            <label>Exp Date</label>
                            <a href="javascript:;" onclick="copyToClipboard('{{ $cardObj['exp_date'] }}')" class="control-label text-bold">{{ $cardObj['exp_date'] }} <i class="icon-copy4"></i></a>
                        </div>
                        <div class="col-xs-6">
                            <label>CVV</label>
                            <a href="javascript:;" onclick="copyToClipboard('{{ $cardObj['cvv'] }}')" class="control-label text-bold">{{ $cardObj['cvv'] }} <i class="icon-copy4"></i></a>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-12">
                            <label>Card Holder</label>
                            <a href="javascript:;" onclick="copyToClipboard('{{ $cardObj['card_holder'] }}')" class="control-label text-bold">{{ $cardObj['card_holder'] }} <i class="icon-copy4"></i></a>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-12">
                            <label>Postal Code</label>
                            <a href="javascript:;" onclick="copyToClipboard('{{ $cardObj['postal_code'] }}')" class="control-label text-bold">{{ $cardObj['postal_code'] }} <i class="icon-copy4"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-12 text-center">
                        <p class="text-danger"><em>Once you've purchased the policy, we will connect to the carrier to make sure the policy was set up correctly and remains in place.</em></p>
                    </div>
                    <div class="col-lg-6 col-sm-12 text-center">
                        <a href="{{ url('insurance/roi/diafinancedsaveinsuranceaccount/' . $orderandusers) }}" class="btn btn-primary w-100">Proceed <i class="icon-arrow-right8 position-right"></i></a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
