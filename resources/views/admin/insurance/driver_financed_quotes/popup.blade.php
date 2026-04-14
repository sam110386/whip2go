<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <div class="panel-body">
        @if(1)
        <div class="col-lg-12 col-sm-12">
            <a href="javascript:;" onclick="(function(){window.open('{{ config('app.url') }}/insurance/roi/diafinancedreview/{{ $orderandusers }}/true','diawindow','directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,width=400,height=850');return false;})();" class="btn btn-info">Update Details By Driver</a>
        </div>
        @endif
        <div class="col-lg-12 col-sm-12">
        <form action="#" method="POST" name="frmadmin" class="form-horizontal">
            @csrf
            <legend class="text-size-large text-bold">Selected Insurance Providers</legend>
            <table width="100%" cellpadding="1" cellspacing="1"  border="0"  class="table  table-responsive">
                <tr>
                    <th>Provider</th>
                    <th>Quote #</th>
                    <th>Quote Doc</th>
                    <th>Approve</th>
                </tr>
            @foreach ($providers as $provider)
                @if (!in_array($provider['InsuranceProvider']['id'], array_keys($quotes)))
                    @continue
                @endif
                <tr>
                    <td>
                        @if (!empty($provider['InsuranceProvider']['logo']))
                            <img src="{{ config('app.url') }}/img/insurance_providers/{{ $provider['InsuranceProvider']['logo'] }}" class="provider-logo"/>
                        @else
                            {{ $provider['InsuranceProvider']['name'] }}
                        @endif
                    </td>
                    <td class="text-semibold">
                        {{ isset($quotes[$provider['InsuranceProvider']['id']]['quote_number']) ? $quotes[$provider['InsuranceProvider']['id']]['quote_number'] : '--' }}
                    </td>
                    <td>
                        @if (isset($quotes[$provider['InsuranceProvider']['id']]['quote_doc']) && !empty($quotes[$provider['InsuranceProvider']['id']]['quote_doc']))
                            <a href="{{ config('app.url') }}/files/reservation/{{ $quotes[$provider['InsuranceProvider']['id']]['quote_doc'] }}" title="quote doc" class="fancybox"><i class="icon-magazine icon-2x"></i></a>
                        @endif
                    </td>
                    <td>
                        <input type="radio" value="{{ $provider['InsuranceProvider']['id'] }}" name="data[DriverFinancedInsuranceQuote][quote_approved]" class="insuranceapprove" {{ ($quoteData['quote_approved'] == $provider['InsuranceProvider']['id']) ? 'checked' : '' }}/>
                    </td>
                </tr>
            @endforeach
            </table>
        
            <legend class="text-size-large text-bold">BYOI Driver Financed Details:</legend>
            <div class="form-group">
                <label class="col-lg-3 control-label">First payment total:</label>
                <div class="col-lg-8">
                    <input type="text" name="data[DriverFinancedInsuranceQuote][premium_total]" value="{{ old('DriverFinancedInsuranceQuote.premium_total', $quoteData['premium_total'] ?? '') }}" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">First payment finance total:</label>
                <div class="col-lg-8">
                    <input type="text" name="data[DriverFinancedInsuranceQuote][premium_finance_total]" value="{{ old('DriverFinancedInsuranceQuote.premium_finance_total', $quoteData['premium_finance_total'] ?? '') }}" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Total (policy cost + financed) amount :</label>
                <div class="col-lg-8">
                    <input type="text" name="data[DriverFinancedInsuranceQuote][total_amount]" value="{{ old('DriverFinancedInsuranceQuote.total_amount', $quoteData['total_amount'] ?? '') }}" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Daily Fee :</label>
                <div class="col-lg-8">
                    <input type="text" name="data[DriverFinancedInsuranceQuote][daily_rate]" value="{{ old('DriverFinancedInsuranceQuote.daily_rate', $quoteData['daily_rate'] ?? '') }}" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-12 mt-2 text-center mt-10">
                    <button type="button" class="btn btn-primary pl-3 pr-3" onclick="SaveDriverFinancedInsuranceQuoteUploadPopUp('{{ $myModal }}')">Save <i class="icon-arrow-right14 position-right"></i></button>
                    <button type="button" class="btn btn-primary pl-3 pr-3" onclick="SaveDriverFinancedInsuranceQuoteUploadPopUp('{{ $myModal }}',true)">Approve & Save <i class="icon-arrow-right14 position-right"></i></button>
                </div>
            </div>
            <input type="hidden" name="data[DriverFinancedInsuranceQuote][id]" value="{{ $quoteData['id'] ?? '' }}">
            <input type="hidden" name="data[DriverFinancedInsuranceQuote][order_id]" value="{{ $recordid }}">
        </form>
        </div>
        @if(($quoteData['docusign_status'] ?? 0) == 1 && !empty($orderDepositRuleObj))
        <div class="col-lg-12 col-sm-12">
            <legend class="text-size-large text-bold"> Signed Documents :</legend>
            <div class="form-group">
                <div class="col-sm-12 control-label">
                    <a href="javascript:;" onclick="OpenSignatureDocPopUp('','{{ $orderDepositRuleObj['OrderDepositRule']['id'] }}','plaidModal')"><i class="icon-image2 icon-2x"></i></a>
                </div>
            </div>
        </div>
        @endif
        <div class="col-lg-12 col-sm-12">
            <form action="#" method="POST" name="frmadmin" class="form-horizontal">
            @csrf
            <legend class="text-size-large text-bold"> Virtual Credit Card :</legend>
            <div class="form-group">
                <div class="col-sm-12">
                    <img src="{{ config('app.url') }}/img/credit-card-icon.png" class="img-responsive" style="max-width: 200px;width:100%;" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <label>CARD NUMBER</label>
                    <div class="input-group">
                        <input type="text" name="data[DriverFinancedCreditCard][card_number]" value="{{ old('DriverFinancedCreditCard.card_number', $creditCard['card_number'] ?? '') }}" class="required form-control" placeholder="Valid Card Number">
                        <span class="input-group-addon"><span class="fa fa-credit-card"></span></span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-7 col-md-7">
                    <label><span class="hidden-xs">EXPIRATION</span><span class="visible-xs-inline">EXP</span> DATE</label>
                    <input type="text" name="data[DriverFinancedCreditCard][exp_date]" value="{{ old('DriverFinancedCreditCard.exp_date', $creditCard['exp_date'] ?? '') }}" maxlength="5" class="required form-control" placeholder="MM / YY">
                </div>
                <div class="col-xs-5 col-md-5 pull-right">
                    <label>CV CODE</label>
                    <input type="text" name="data[DriverFinancedCreditCard][cvv]" value="{{ old('DriverFinancedCreditCard.cvv', $creditCard['cvv'] ?? '') }}" maxlength="4" class="required form-control" placeholder="CVC">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-6 col-md-6">
                    <label>CARD OWNER</label>
                    <input type="text" name="data[DriverFinancedCreditCard][card_holder]" value="{{ old('DriverFinancedCreditCard.card_holder', $creditCard['card_holder'] ?? '') }}" maxlength="40" class="required form-control" placeholder="Card Owner Names">
                </div>
                <div class="col-xs-6 col-md-6 pull-right">
                    <label>Postal code</label>
                    <input type="text" name="data[DriverFinancedCreditCard][postal_code]" value="{{ old('DriverFinancedCreditCard.postal_code', $creditCard['postal_code'] ?? '') }}" maxlength="6" class="required form-control" placeholder="Postal code">
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-12 mt-2 text-center mt-10">
                    <button type="button" class="btn btn-primary pl-3 pr-3" onclick="SaveDriverFinancedVirtualCardPopUp('{{ $myModal }}')">Save <i class="icon-arrow-right14 position-right"></i></button>
                    <button type="button" class="btn btn-danger pl-3 pr-3" onclick="clearDriverFinancedVirtualCard('{{ $recordid }}','{{ $myModal }}',true)">Clear Card Details <i class="icon-arrow-right14 position-right"></i></button>
                </div>
            </div>
            <input type="hidden" name="data[DriverFinancedCreditCard][id]" value="{{ $quoteData['id'] ?? '' }}">
            <input type="hidden" name="data[DriverFinancedCreditCard][order_id]" value="{{ $recordid }}">
            </form>
        </div>
        @if (isset($providerAccount) && !empty($providerAccount))
            <div class="col-lg-12 col-sm-12">
                <form class="form-horizontal">
                    <legend class="text-size-large text-bold">Insurance Provider Account Details :</legend>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Username :</label>
                        <div class="col-lg-8 control-label text-bold">
                            {{ $providerAccount['username'] ?? '' }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Password :</label>
                        <div class="col-lg-8 control-label text-bold">
                            {{ $providerAccount['password'] ?? '' }}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-lg-12 control-label text-center">
                        @if(empty($orderDepositRuleObj['AxleStatus']) || ($orderDepositRuleObj['AxleStatus']['axle_status'] ?? 0) == 0)
                            <a href="{{ config('app.url') }}/admin/axle/axledocs/connect/{{ $orderDepositRuleObj['OrderDepositRule']['id'] }}" title="Connect to Axle" class="btn btn-success" target="_blank">Connect to Axle <i class="icon-arrow-resize7 position-right"></i></a>
                        @endif
                        @if(($orderDepositRuleObj['AxleStatus']['axle_status'] ?? 0) != 0)
                            <a href="javascript:;" class="btn btn-success" onclick="getAxlePolicyDetails({{ $orderDepositRuleObj['OrderDepositRule']['id'] }},'statementModal')">Connected <i class="icon-connection position-right"></i></a>
                            <a href="javascript:;" class="btn btn-warning" onclick="axlePolicyDetailsPopup({{ $orderDepositRuleObj['OrderDepositRule']['id'] }},'statementModal')">Policy Checklist <i class="icon-pencil7 position-right"></i></a>
                        @endif
                        </div>
                    </div>
                    
                </form>
            </div>
        @endif
        <div class="col-lg-12 col-sm-12">
        <form action="#" method="POST" name="frmadmin" class="form-horizontal">
            @csrf
                <legend class="text-size-large text-bold">Policy Details :</legend>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Provider Name:</label>
                    <div class="col-lg-8">
                        <input type="text" name="data[DriverFinancedInsuranceQuote][provider_name]" value="{{ old('DriverFinancedInsuranceQuote.provider_name', $quoteData['provider_name'] ?? '') }}" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Policy #:</label>
                    <div class="col-lg-8">
                        <input type="text" name="data[DriverFinancedInsuranceQuote][policy_number]" value="{{ old('DriverFinancedInsuranceQuote.policy_number', $quoteData['policy_number'] ?? '') }}" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Begin Date:</label>
                    <div class="col-lg-8">
                        <input type="text" name="data[DriverFinancedInsuranceQuote][begin_date]" value="{{ old('DriverFinancedInsuranceQuote.begin_date', $quoteData['begin_date'] ?? '') }}" class="date form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">End Date:</label>
                    <div class="col-lg-8">
                        <input type="text" name="data[DriverFinancedInsuranceQuote][end_date]" value="{{ old('DriverFinancedInsuranceQuote.end_date', $quoteData['end_date'] ?? '') }}" class="date form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Declaration Doc :</label>
                    <div class="col-lg-8">
                        <input type="file" name="declaration_doc" class="file-input" id="DriverFinancedInsuranceQuoteDeclarationDoc" data-show-preview="false" data-id="{{ $recordid }}" data-type="declaration_doc" />
                    </div>
                    <div class="col-lg-2">
                        @if (!empty($quoteData['declaration_doc']))
                            <a href="{{ config('app.url') }}/files/reservation/{{ $quoteData['declaration_doc'] }}" title="Driver License" class="fancybox"><i class="icon-magazine icon-2x"></i></a>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">Insurance Card :</label>
                    <div class="col-lg-8">
                        <input type="file" name="insurance_card" class="file-input" id="DriverFinancedInsuranceQuoteCard" data-show-preview="false" data-id="{{ $recordid }}" data-type="insurance_card" />
                    </div>
                    <div class="col-lg-2">
                        @if (!empty($quoteData['insurance_card']))
                            <a href="{{ config('app.url') }}/files/reservation/{{ $quoteData['insurance_card'] }}" title="Driver License" class="fancybox"><i class="icon-magazine icon-2x"></i></a>
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-12 mt-2 text-center mt-10">
                        <button type="button" class="btn btn-primary pl-3 pr-3" onclick="SaveDriverFinancedPolicyDetails('{{ $myModal }}')">Save <i class="icon-arrow-right14 position-right"></i></button>
                    </div>
                </div>
            <input type="hidden" name="data[DriverFinancedInsuranceQuote][id]" value="{{ $quoteData['id'] ?? '' }}">
            <input type="hidden" name="data[DriverFinancedInsuranceQuote][order_id]" value="{{ $recordid }}">
        </form>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
