<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="panel-body">
        <div class="col-md-6">
            <form action="#" method="POST" name="frmadmin" class="form-horizontal">
                @csrf

                <legend class="text-size-large text-bold">Insurance Details:</legend>

                @php
                    $payer = data_get($trip, 'orderDepositRule.insurance_payer');
                    $ruleId = data_get($trip, 'orderDepositRule.id');
                    $reservationId = data_get($trip, 'id');
                    $insuranceAmount = data_get($trip, 'orderDepositRule.insurance');
                    $insuAgreed = data_get($trip, 'orderDepositRule.insu_agreed');
                @endphp

                <div class="form-group">
                    <label class="col-lg-6 control-label">Daily Rate :</label>
                    <div class="col-lg-6 control-label">
                        @if($payer != 3)
                            <a href="javascript:;" onclick="OpenChangeInsurancePopUp('{{ $ruleId }}', 'plaidModal')">
                                {{ $insuranceAmount }}
                            </a>
                        @else
                            {{ $insuranceAmount }}
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-6 control-label">Insurance Accepted :</label>
                    <div class="col-lg-6 control-label">
                        {{ $insuAgreed == 2 ? 'N/A' : ($insuAgreed == 0 ? 'No' : 'Yes') }}
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-6 control-label">Insurance By :</label>
                    <div class="col-lg-6 control-label">
                        @if($payer == 3)
                            <a href="javascript:;" onclick="OpenInsurancePayerUploadPopUp({{ $ruleId }})">
                        @elseif($payer == 4 || $payer == 6)
                                <a href="javascript:;" onclick="OpenBoyiByDIAListPopUp({{ $reservationId }})">
                            @elseif($payer == 5)
                                    <a href="javascript:;"
                                        onclick="OpenDriverFinancedInsuranceQuoteUploadPopUp({{ $reservationId }})">
                                @elseif($payer == 7)
                                        <a href="javascript:;"
                                            onclick="OpenDiaFleeetBackupQuoteUploadPopUp({{ $reservationId }})">
                                    @endif

                                        {{ $commonService->getInsurancePayer($payer) }}

                                        @endif
                        </a>
                    </div>
                </div>

                @if($payer == 4 && !empty($InsuranceQuoteObj))
                    @php
                        $providerName = data_get($InsuranceQuoteObj, 'provider.name', data_get($InsuranceQuoteObj, 'InsuranceProvider.name'));
                        $quoteAmount = data_get($InsuranceQuoteObj, 'quote_amount', data_get($InsuranceQuoteObj, 'InsuranceQuote.quote_amount'));
                        $dailyRate = data_get($InsuranceQuoteObj, 'daily_rate', data_get($InsuranceQuoteObj, 'InsuranceQuote.daily_rate'));
                        $quoteId = data_get($InsuranceQuoteObj, 'id', data_get($InsuranceQuoteObj, 'InsuranceQuote.id'));
                    @endphp
                    <div class="form-group">
                        <label class="col-lg-6 control-label">Selected Quote :</label>
                        <div class="col-lg-6 control-label">
                            <a href="javascript:;" onclick="OpenInsurancePayerUploadPopUp('{{ $ruleId }}', 'plaidModal')">
                                {{ $providerName }} - { {{ $quoteAmount }} } - { {{ $dailyRate }} }
                            </a>
                        </div>
                    </div>
                @endif

                @if($payer == 4 && !empty($InsuranceQuoteObj))
                    <div class="form-group">
                        <label class="col-lg-6 control-label">Signed Documents :</label>
                        <div class="col-lg-6 control-label">
                            <a href="javascript:;"
                                onclick="OpenSignatureDocPopUp('{{ $quoteId }}', '{{ $ruleId }}', 'plaidModal')">
                                Pull Signed Documents
                            </a>
                        </div>
                    </div>
                @endif

                <input type="hidden" name="OrderDepositRule[id]" value="{{ $ruleId }}">
            </form>
        </div>

        <div class="col-md-6">
            <legend class="text-size-large text-bold">Change Insurance Payer:</legend>
            <div class="form-group">
                <label class="col-lg-6 control-label">Insurance By :</label>
                <div class="col-lg-6 control-label">
                    <a href="javascript:;" onclick="OpenChangeInsurancePayerPopUp('{{ $ruleId }}')">
                        {{ $commonService->getInsurancePayer($payer) }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>