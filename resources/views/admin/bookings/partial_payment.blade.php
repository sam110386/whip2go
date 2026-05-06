<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title">{{ $paymenttype }} Payment Retry</h4>
</div>

<div class="modal-body">
    <form id="paymentretry" class="form-horizontal" method="POST">
        @csrf
        <input type="hidden" name="Booking[id]" value="{{ $booking->id }}">
        
        <div class="row">
            <div class="col-sm-6 col-sx-12">
                <legend class="text-semibold">{{ $paymenttype }} Payment Details</legend>
                <div class="form-group">
                    <label class="col-lg-6 control-label">Uses Fee :</label>
                    <div class="col-lg-6 control-label">
                        {{ $booking->rent }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label">EMF :</label>
                    <div class="col-lg-6 control-label">
                        {{ $booking->extra_mileage_fee }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label">TAX :</label>
                    <div class="col-lg-6 control-label">
                        {{ $booking->tax }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label">Booking Fee :</label>
                    <div class="col-lg-6 control-label">
                        {{ $booking->dia_fee }}
                    </div>
                </div>
                @if ($booking->lateness_fee > 0)
                    <div class="form-group">
                        <label class="col-lg-6 control-label">Lateness Fee :</label>
                        <div class="col-lg-6 control-label">
                            {{ $booking->lateness_fee }}
                        </div>
                    </div>
                @endif
                <div class="form-group">
                    <label class="col-lg-6 control-label">Total Uses Fee :</label>
                    <div class="col-lg-6 control-label">
                        {{ $booking->carsharing_fee_total }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label">Paid Amount :</label>
                    <div class="col-lg-6 control-label">
                        {{ $booking->carsharing_fee_paid }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label">Uses Fee Due :</label>
                    <div class="col-lg-6 control-label">
                        {{ $booking->total_rental_remaining }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label">Toll/Deduction Due :</label>
                    <div class="col-lg-6 control-label">
                        {{ $booking->pending_toll }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label"><strong>Total Due Current Cycle:</strong></label>
                    <div class="col-lg-6 control-label">
                        {{ is_array($booking->total_remaining_autorenew) ? $booking->total_remaining_autorenew['amount'] : $booking->total_remaining_autorenew }}
                    </div>
                    <span class="help-block">{{ $booking->total_remaining_autorenew['hint'] ?? '' }}</span>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label"><strong>Total to Close Program:</strong></label>
                    <div class="col-lg-6 control-label">
                        {{ is_array($booking->total_remaining_close) ? $booking->total_remaining_close['amount'] : $booking->total_remaining_close }}
                    </div>
                    <span class="help-block">{{ $booking->total_remaining_close['hint'] ?? '' }}</span>
                </div>
                <div class="form-group">
                    <label class="col-lg-6 control-label"><strong>Total Due Next Cycle:</strong></label>
                    <div class="col-lg-6 control-label">
                        {{ is_array($booking->total_remaining_nextschedule) ? $booking->total_remaining_nextschedule['amount'] : $booking->total_remaining_nextschedule }}
                    </div>
                    <span class="help-block">{{ $booking->total_remaining_nextschedule['hint'] ?? '' }}</span>
                </div>
            </div>

            <div class="col-sm-6 col-sx-12">
                <div class="panel panel-white">
                    <div class="panel-heading">
                        <legend>INSURANCE</legend>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-lg-6 control-label">Insurance & Fees :</label>
                            <div class="col-lg-6 control-label">
                                {{ $booking->insurance_amt }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6 control-label">EMF Insurance :</label>
                            <div class="col-lg-6 control-label">
                                {{ $booking->dia_insu }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6 control-label">Total Insurance & Fees:</label>
                            <div class="col-lg-6 control-label">
                                {{ $booking->total_insurance_calculated }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6 control-label">Total Paid Insurance & Fees :</label>
                            <div class="col-lg-6 control-label">
                                {{ $booking->total_insurance_paid }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6 control-label">Total Due Insurance & Fees :</label>
                            <div class="col-lg-6 control-label">
                                {{ $booking->total_insurance_remaining }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel panel-white">
                    <div class="panel-heading">
                        <legend>INITIAL FEE</legend>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-lg-6 control-label">Initial Fee :</label>
                            <div class="col-lg-6 control-label">
                                {{ $booking->total_initial_fee_calculated }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6 control-label">Paid Initial Fee :</label>
                            <div class="col-lg-6 control-label">
                                {{ $booking->total_initial_fee_paid }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6 control-label">Initial Fee Due:</label>
                            <div class="col-lg-6 control-label">
                                {{ $booking->total_initial_fee_remaining }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <legend>Make Payment</legend>
                <div class="form-group">
                    <div class="col-lg-5 checkbox">
                        <label class="control-label">
                            <input type="radio" checked="checked" class="paymenttype" name="payment" value="fullpay">
                            <strong>Amount Due : {{ is_array($booking->total_remaining_autorenew) ? $booking->total_remaining_autorenew['amount'] : $booking->total_remaining_autorenew }}</strong>
                        </label>
                    </div>

                    <div class="col-lg-6 show" id="fullpay_div">
                        <button type="button" class="btn btn-primary" onclick="processPaymentRetry()">Make Full Payment</button>
                        <input type="hidden" name="Booking[famt]" value="{{ is_array($booking->total_remaining_autorenew) ? $booking->total_remaining_autorenew['amount'] : $booking->total_remaining_autorenew }}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-3 checkbox">
                        <label class="control-label">
                            <input type="radio" class="paymenttype" name="payment" value="partial">
                            <strong>Other Amount : </strong>
                        </label>
                    </div>
                    <span id="partial_div" class="hidden">
                        <div class="col-sm-2">
                            <input type="text" name="Booking[pamt]" class="form-control required" value="{{ is_array($booking->total_remaining_autorenew) ? $booking->total_remaining_autorenew['amount'] : $booking->total_remaining_autorenew }}">
                        </div>
                        <div class="col-sm-2 mt-2">
                            <label class="control-label"> I will Pay due on date </label>
                        </div>
                        <div class="col-sm-2">
                            <input type="text" name="Booking[date]" class="form-control datepicker required" autocomplete="off" value="">
                        </div>
                        <div class="col-sm-3">
                            <button type="button" class="btn btn-primary" onclick="processPaymentRetry()">Process Payment</button>
                        </div>
                    </span>
                </div>

                <div class="form-group">
                    <div class="col-sm-3 checkbox">
                        <label class="control-label">
                            <input type="radio" class="paymenttype" name="payment" value="advance">
                            <strong>Advance Amount : </strong>
                        </label>
                    </div>
                    <span id="advance_div" class="hidden">
                        <div class="col-sm-2">
                            <input type="text" name="Booking[advamt]" class="form-control required" value="{{ $booking->least_advance_payment }}">
                        </div>
                        <div class="col-sm-3">
                            <button type="button" class="btn btn-primary" onclick="processPaymentRetry()">Process Payment</button>
                        </div>
                    </span>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer"></div>

<style>
    #paymentretry .form-group {
        margin-bottom: 0px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'mm/dd/yyyy',
            startDate: '{{ $allowed_min_date }}',
            endDate: '{{ $allowed_max_date }}',
            autoclose: true
        });

        $('.paymenttype').on('change', function() {
            var val = $(this).val();
            $('#fullpay_div, #partial_div, #advance_div').addClass('hidden');
            if (val === 'fullpay') {
                $('#fullpay_div').removeClass('hidden');
            } else if (val === 'partial') {
                $('#partial_div').removeClass('hidden');
            } else if (val === 'advance') {
                $('#advance_div').removeClass('hidden');
            }
        });
    });
</script>
