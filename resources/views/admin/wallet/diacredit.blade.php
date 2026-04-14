@extends('layouts.admin')

@section('title', 'Add DIA Credits')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="{{ url('admin/wallet/index', $userid) }}"><i class="icon-arrow-left52 position-left"></i></a>
                    <span class="text-semibold">{{ 'Add' }}</span> — {{ 'DIA Credits' }}
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('layouts.flash-messages')
    </div>

    <div class="panel">
        <div class="panel-body">
            @if(config('services.stripe.key'))
                <script src="https://js.stripe.com/v3/"></script>
                <form id="payment-form" class="form-horizontal">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Amount ($):</label>
                            <div class="col-lg-4">
                                <input type="number" id="diacredit-amount" class="form-control" step="0.01" min="1" required>
                                <input type="hidden" id="userid" value="{{ $userid }}">
                            </div>
                        </div>
                        <div id="stripe-payment-section" style="display: none;">
                            <div class="form-group">
                                <label class="col-lg-2 control-label">Card Details:</label>
                                <div class="col-lg-6">
                                    <div id="payment-element" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></div>
                                    <div id="card-errors" role="alert" style="color: red; margin-top: 10px;"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <button type="button" id="submit-payment" class="btn btn-success">Pay & Add Credit</button>
                                </div>
                            </div>
                        </div>
                        <div id="initial-action" class="form-group">
                            <div class="col-lg-offset-2 col-lg-10">
                                <button type="submit" id="proceed-button" class="btn btn-primary">Proceed</button>
                                <a href="{{ url('admin/wallet/index', $userid) }}" class="btn btn-default">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>

                <script>
                    const stripe = Stripe("{{ config('services.stripe.key') }}");
                    let elements;
                    const userid = document.getElementById('userid').value;

                    document.getElementById('payment-form').addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const amount = document.getElementById('diacredit-amount').value;
                        if (!amount || amount < 1) {
                            alert('Please enter a valid amount (minimum $1)');
                            return;
                        }

                        // Block UI
                        if (typeof jQuery.blockUI !== 'undefined') {
                            jQuery.blockUI({ message: '<h1>Initializing...</h1>' });
                        }

                        try {
                            const response = await fetch("{{ url('admin/wallet/createintent') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ amount, userid })
                            });
                            
                            const data = await response.json();
                            if (typeof jQuery.unblockUI !== 'undefined') { jQuery.unblockUI(); }

                            if (!data.client_secret) {
                                alert('Error: ' + (data.message || 'Could not create payment intent'));
                                return;
                            }

                            const appearance = { theme: 'flat' };
                            elements = stripe.elements({ clientSecret: data.client_secret, appearance });
                            const paymentElement = elements.create('payment');
                            paymentElement.mount('#payment-element');

                            document.getElementById('stripe-payment-section').style.display = 'block';
                            document.getElementById('initial-action').style.display = 'none';

                        } catch (err) {
                            console.error(err);
                            if (typeof jQuery.unblockUI !== 'undefined') { jQuery.unblockUI(); }
                            alert('An error occurred. Please try again.');
                        }
                    });

                    document.getElementById('submit-payment').addEventListener('click', async () => {
                        if (typeof jQuery.blockUI !== 'undefined') {
                            jQuery.blockUI({ message: '<h1>Processing Payment...</h1>' });
                        }

                        const { error, paymentIntent } = await stripe.confirmPayment({
                            elements,
                            confirmParams: {
                                return_url: window.location.origin + "{{ url('admin/wallet/index', $userid) }}"
                            },
                            redirect: "if_required",
                        });

                        if (error) {
                            if (typeof jQuery.unblockUI !== 'undefined') { jQuery.unblockUI(); }
                            document.getElementById('card-errors').textContent = error.message;
                        } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                            // Save to wallet
                            try {
                                const saveResponse = await fetch("{{ url('admin/wallet/diacreditprocess') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        transaction: paymentIntent.id,
                                        userid: userid,
                                        amount: paymentIntent.amount
                                    })
                                });
                                
                                const saveResult = await saveResponse.json();
                                if (typeof jQuery.unblockUI !== 'undefined') { jQuery.unblockUI(); }

                                if (saveResult.status) {
                                    alert(saveResult.message);
                                    window.location.href = "{{ url('admin/wallet/index', $userid) }}";
                                } else {
                                    alert(saveResult.message);
                                }
                            } catch (err) {
                                if (typeof jQuery.unblockUI !== 'undefined') { jQuery.unblockUI(); }
                                alert('Payment succeeded but wallet update failed. Please contact support.');
                            }
                        }
                    });
                </script>
            @else
                <div class="alert alert-warning">Stripe is not configured. Please set STRIPE_KEY in your .env file.</div>
            @endif
        </div>
    </div>
@endsection
