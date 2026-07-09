<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="panel-body">
        <div class="col-md-6">
            <form action="#" method="POST" name="frmadmin" class="form-horizontal">
                @csrf

                <legend class="text-size-large text-bold">Insurance Details:</legend>

                <div class="form-group">
                    <label class="col-lg-6 control-label">Daily Rate :</label>
                    <div class="col-lg-6 control-label">
                        @if(data_get($trip, 'orderDepositRule.insurance_payer') != 3)
                            <a href="javascript:void(0)"
                                onclick="OpenChangeInsurancePopUp('{{ data_get($trip, 'orderDepositRule.id') }}', 'plaidModal')">
                                {{ data_get($trip, 'orderDepositRule.insurance') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-6 control-label">Insurance Accepted :</label>
                    <div class="col-lg-6 control-label">
                        {{ data_get($trip, 'orderDepositRule.insu_agreed') == 2 ? 'N/A' : (data_get($trip, 'orderDepositRule.insu_agreed') == 0 ? 'No' : 'Yes') }}
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-6 control-label">
                        Insurance By :
                    </label>

                    <div class="col-lg-6 control-label">
                        @if(data_get($trip, 'orderDepositRule.insurance_payer') == 3)
                            <a href="javascript:void(0)"
                                onclick="OpenInsurancePayerUploadPopUp({{ data_get($trip, 'orderDepositRule.id') }})">
                                {{ $commonService->getInsurancePayer(data_get($trip, 'orderDepositRule.insurance_payer')) }}
                            </a>
                        @elseif(data_get($trip, 'orderDepositRule.insurance_payer') == 4 || data_get($trip, 'orderDepositRule.insurance_payer') == 6)
                            <a href="javascript:void(0)" onclick="OpenBoyiByDIAListPopUp({{ data_get($trip, 'id') }})">
                                {{ $commonService->getInsurancePayer(data_get($trip, 'orderDepositRule.insurance_payer')) }}
                            </a>
                        @elseif(data_get($trip, 'orderDepositRule.insurance_payer') == 5)
                            <a href="javascript:void(0)"
                                onclick="OpenDriverFinancedInsuranceQuoteUploadPopUp({{ data_get($trip, 'id') }})">
                                {{ $commonService->getInsurancePayer(data_get($trip, 'orderDepositRule.insurance_payer')) }}
                            </a>
                        @elseif(data_get($trip, 'orderDepositRule.insurance_payer') == 7)
                            <a href="javascript:void(0)"
                                onclick="OpenDiaFleeetBackupQuoteUploadPopUp({{ data_get($trip, 'id') }})">
                                {{ $commonService->getInsurancePayer(data_get($trip, 'orderDepositRule.insurance_payer')) }}
                            </a>
                        @endif
                    </div>
                </div>

                @if(data_get($trip, 'orderDepositRule.insurance_payer') == 4 && !empty($insuranceQuote))
                    <div class="form-group">
                        <label class="col-lg-6 control-label">Selected Quote :</label>
                        <div class="col-lg-6 control-label">
                            <a href="javascript:void(0)"
                                onclick="OpenInsurancePayerUploadPopUp('{{ data_get($trip, 'orderDepositRule.id') }}', 'plaidModal')">
                                {{ data_get($insuranceQuote, 'provider.name') }} - {
                                {{ data_get($insuranceQuote, 'quote_amount') }} } - {
                                {{ data_get($insuranceQuote, 'daily_rate') }} }
                            </a>
                        </div>
                    </div>
                @endif

                @if(data_get($trip, 'orderDepositRule.insurance_payer') == 4 && !empty($insuranceQuote))
                    <div class="form-group">
                        <label class="col-lg-6 control-label">Signed Documents :</label>
                        <div class="col-lg-6 control-label">
                            <a href="javascript:void(0)"
                                onclick="OpenSignatureDocPopUp('{{ data_get($insuranceQuote, 'id')}}', '{{ data_get($trip, 'orderDepositRule.id') }}', 'plaidModal')">
                                Pull Signed Documents
                            </a>
                        </div>
                    </div>
                @endif

                <input type="hidden" name="OrderDepositRule[id]" value="{{ data_get($trip, 'orderDepositRule.id') }}">
            </form>
        </div>

        <div class="col-md-6">
            <legend class="text-size-large text-bold">
                Change Insurance Payer:
            </legend>
            <div class="form-group">
                <label class="col-lg-6 control-label">
                    Insurance By :
                </label>
                <div class="col-lg-6 control-label">
                    <a href="javascript:void(0)"
                        onclick="OpenChangeInsurancePayerPopUp('{{ data_get($trip, 'orderDepositRule.id') }}')">
                        {{ $commonService->getInsurancePayer(data_get($trip, 'orderDepositRule.insurance_payer', '')) }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>