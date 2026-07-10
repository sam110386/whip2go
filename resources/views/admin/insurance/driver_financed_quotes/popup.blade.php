<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="panel-body">

        @if(1)
            <div class="col-lg-12 col-sm-12">
                <a href="javascript:void(0)"
                    onclick="(function(){window.open('{{ url('roi/diafinancedreview/' . $orderandusers . '/true')}}','diawindow','directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,width=400,height=850');return false;})();"
                    class="btn btn-info">
                    Update Details By Driver
                </a>
            </div>
        @endif

        <div class="col-lg-12 col-sm-12">
            <form action="#" method="POST" name="frmadmin" class="form-horizontal"
                id="DriverFinancedInsuranceQuoteAdminPopupForm">
                @csrf

                <legend class="text-size-large text-bold">Selected Insurance Providers</legend>

                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table  table-responsive">
                    <tr>
                        <th>Provider</th>
                        <th>Quote #</th>
                        <th>Quote Doc</th>
                        <th>Approve</th>
                    </tr>

                    @foreach ($providers as $provider)
                        @if (!in_array(data_get($provider, 'id'), array_keys($quotes)))
                            @continue
                        @endif

                        <tr>
                            <td>
                                @if (!empty(data_get($provider, 'logo')))
                                    <img src="{{ legacy_asset('img/insurance_providers/' . data_get($provider, 'logo')) }}"
                                        class="provider-logo" />
                                @else
                                    {{ data_get($provider, 'name') }}
                                @endif
                            </td>
                            <td class="text-semibold">
                                {{ data_get($quotes, data_get($provider, 'id') . '.quote_number', '--') }}
                            </td>
                            <td>
                                @if (!empty(data_get($quotes, data_get($provider, 'id') . '.quote_doc')))
                                    <a href="{{ legacy_asset('files/reservation/' . data_get($quotes, data_get($provider, 'id') . '.quote_doc')) }}"
                                        title="quote doc" class="fancybox">
                                        <i class="icon-magazine icon-2x"></i>
                                    </a>
                                @endif
                            </td>
                            <td>
                                <input type="radio" value="{{ data_get($provider, 'id') }}"
                                    name="DriverFinancedInsuranceQuote[quote_approved]" class="insuranceapprove" {{ (data_get($quoteData, 'quote_approved') == data_get($provider, 'id')) ? 'checked' : '' }} />
                            </td>
                        </tr>
                    @endforeach
                </table>

                <legend class="text-size-large text-bold">BYOI Driver Financed Details:</legend>

                <div class="form-group">
                    <label class="col-lg-3 control-label">
                        First payment total:
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="DriverFinancedInsuranceQuote[premium_total]"
                            value="{{ old('DriverFinancedInsuranceQuote.premium_total', data_get($quoteData, 'premium_total', '')) }}"
                            class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">
                        First payment finance total:
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="DriverFinancedInsuranceQuote[premium_finance_total]"
                            value="{{ old('DriverFinancedInsuranceQuote.premium_finance_total', data_get($quoteData, 'premium_finance_total', '')) }}"
                            class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">
                        Total (policy cost + financed) amount :
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="DriverFinancedInsuranceQuote[total_amount]"
                            value="{{ old('DriverFinancedInsuranceQuote.total_amount', data_get($quoteData, 'total_amount', '')) }}"
                            class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">
                        Daily Fee :
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="DriverFinancedInsuranceQuote[daily_rate]"
                            value="{{ old('DriverFinancedInsuranceQuote.daily_rate', data_get($quoteData, 'daily_rate', '')) }}"
                            class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-12 mt-2 text-center mt-10">
                        <button type="button" class="btn btn-primary pl-3 pr-3"
                            onclick="SaveDriverFinancedInsuranceQuoteUploadPopUp('{{ $myModal }}')">
                            Save <i class="icon-arrow-right14 position-right"></i>
                        </button>
                        <button type="button" class="btn btn-primary pl-3 pr-3"
                            onclick="SaveDriverFinancedInsuranceQuoteUploadPopUp('{{ $myModal }}',true)">
                            Approve & Save
                            <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="DriverFinancedInsuranceQuote[id]"
                    value="{{ data_get($quoteData, 'id', '') }}">
                <input type="hidden" name="DriverFinancedInsuranceQuote[order_id]" value="{{ $recordid }}">
            </form>
        </div>

        @if(data_get($quoteData, 'docusign_status', 0) == 1 && !empty($orderDepositRuleObj))
            <div class="col-lg-12 col-sm-12">
                <legend class="text-size-large text-bold"> Signed Documents :</legend>

                <div class="form-group">
                    <div class="col-sm-12 control-label">
                        <a href="javascript:void(0)"
                            onclick="OpenSignatureDocPopUp('','{{ data_get($orderDepositRuleObj, 'id', '') }}','plaidModal')">
                            <i class="icon-image2 icon-2x"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-lg-12 col-sm-12">
            <form action="#" method="POST" name="frmadmin" class="form-horizontal"
                id="DriverFinancedCreditCardAdminPopupForm">
                @csrf

                <legend class="text-size-large text-bold"> Virtual Credit Card :</legend>

                <div class="form-group">
                    <div class="col-sm-12">
                        <img src="{{ legacy_asset('img/credit-card-icon.png') }}" class="img-responsive"
                            style="max-width: 200px;width:100%;" />
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-xs-12">
                        <label>CARD NUMBER</label>
                        <div class="input-group">
                            <input type="text" name="DriverFinancedCreditCard[card_number]"
                                value="{{ old('DriverFinancedCreditCard.card_number', data_get($creditCard, 'card_number', '')) }}"
                                class="required form-control" placeholder="Valid Card Number">
                            <span class="input-group-addon">
                                <span class="fa fa-credit-card"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-xs-7 col-md-7">
                        <label>
                            <span class="hidden-xs">EXPIRATION</span>
                            <span class="visible-xs-inline">EXP</span>
                            DATE
                        </label>
                        <input type="text" name="DriverFinancedCreditCard[exp_date]"
                            value="{{ old('DriverFinancedCreditCard.exp_date', data_get($creditCard, 'exp_date', '')) }}"
                            maxlength="5" class="required form-control" placeholder="MM / YY">
                    </div>
                    <div class="col-xs-5 col-md-5 pull-right">
                        <label>CV CODE</label>
                        <input type="text" name="DriverFinancedCreditCard[cvv]"
                            value="{{ old('DriverFinancedCreditCard.cvv', data_get($creditCard, 'cvv', '')) }}"
                            maxlength="4" class="required form-control" placeholder="CVC">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-xs-6 col-md-6">
                        <label>CARD OWNER</label>
                        <input type="text" name="DriverFinancedCreditCard[card_holder]"
                            value="{{ old('DriverFinancedCreditCard.card_holder', data_get($creditCard, 'card_holder', '')) }}"
                            maxlength="40" class="required form-control" placeholder="Card Owner Names">
                    </div>
                    <div class="col-xs-6 col-md-6 pull-right">
                        <label>Postal code</label>
                        <input type="text" name="DriverFinancedCreditCard[postal_code]"
                            value="{{ old('DriverFinancedCreditCard.postal_code', data_get($creditCard, 'postal_code', '')) }}"
                            maxlength="6" class="required form-control" placeholder="Postal code">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-12 mt-2 text-center mt-10">
                        <button type="button" class="btn btn-primary pl-3 pr-3"
                            onclick="SaveDriverFinancedVirtualCardPopUp('{{ $myModal }}')">
                            Save <i class="icon-arrow-right14 position-right"></i>
                        </button>
                        <button type="button" class="btn btn-danger pl-3 pr-3"
                            onclick="clearDriverFinancedVirtualCard('{{ $recordid }}','{{ $myModal }}',true)">
                            Clear Card
                            Details <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="DriverFinancedCreditCard[id]" value="{{ data_get($quoteData, 'id', '') }}">
                <input type="hidden" name="DriverFinancedCreditCard[order_id]" value="{{ $recordid }}">
            </form>
        </div>

        @if (isset($providerAccount) && !empty($providerAccount))
            <div class="col-lg-12 col-sm-12">
                <form class="form-horizontal">
                    <legend class="text-size-large text-bold">Insurance Provider Account Details :</legend>

                    <div class="form-group">
                        <label class="col-lg-2 control-label">Username :</label>
                        <div class="col-lg-8 control-label text-bold">
                            {{ data_get($providerAccount, 'username', '') }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label">Password :</label>
                        <div class="col-lg-8 control-label text-bold">
                            {{ data_get($providerAccount, 'password', '') }}
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-12 control-label text-center">
                            @if(empty(data_get($orderDepositRuleObj, 'AxleStatus')) || data_get($orderDepositRuleObj, 'AxleStatus.axle_status', 0) == 0)
                                <a href="{{ legacy_asset('admin/axledocs/connect/' . data_get($orderDepositRuleObj, 'id')) }}"
                                    title="Connect to Axle" class="btn btn-success" target="_blank">
                                    Connect to Axle <i class="icon-arrow-resize7 position-right"></i>
                                </a>
                            @endif
                            @if(data_get($orderDepositRuleObj, 'AxleStatus.axle_status', 0) != 0)
                                <a href="javascript:void(0)" class="btn btn-success"
                                    onclick="getAxlePolicyDetails({{ data_get($orderDepositRuleObj, 'id') }},'statementModal')">
                                    Connected
                                    <i class="icon-connection position-right"></i>
                                </a>
                                <a href="javascript:void(0)" class="btn btn-warning"
                                    onclick="axlePolicyDetailsPopup({{ data_get($orderDepositRuleObj, 'id') }},'statementModal')">
                                    Policy Checklist <i class="icon-pencil7 position-right"></i>
                                </a>
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
                    <label class="col-lg-2 control-label">
                        Provider Name:
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="DriverFinancedInsuranceQuote[provider_name]"
                            value="{{ old('DriverFinancedInsuranceQuote.provider_name', data_get($quoteData, 'provider_name', '')) }}"
                            class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Policy #:
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="DriverFinancedInsuranceQuote[policy_number]"
                            value="{{ old('DriverFinancedInsuranceQuote.policy_number', data_get($quoteData, 'policy_number', '')) }}"
                            class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Begin Date:
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="DriverFinancedInsuranceQuote[begin_date]"
                            value="{{ old('DriverFinancedInsuranceQuote.begin_date', data_get($quoteData, 'begin_date', '')) }}"
                            class="date form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        End Date:
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="DriverFinancedInsuranceQuote[end_date]"
                            value="{{ old('DriverFinancedInsuranceQuote.end_date', data_get($quoteData, 'end_date', '')) }}"
                            class="date form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Declaration Doc :
                    </label>
                    <div class="col-lg-8">
                        <input type="file" name="declaration_doc" class="file-input"
                            id="DriverFinancedInsuranceQuoteDeclarationDoc" data-show-preview="false"
                            data-id="{{ $recordid }}" data-type="declaration_doc" />
                    </div>
                    <div class="col-lg-2">
                        @if (!empty(data_get($quoteData, 'declaration_doc')))
                            <a href="{{ legacy_asset('files/reservation/' . data_get($quoteData, 'declaration_doc'))}}"
                                title="Driver License" class="fancybox">
                                <i class="icon-magazine icon-2x"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Insurance Card :
                    </label>
                    <div class="col-lg-8">
                        <input type="file" name="insurance_card" class="file-input"
                            id="DriverFinancedInsuranceQuoteCard" data-show-preview="false" data-id="{{ $recordid }}"
                            data-type="insurance_card" />
                    </div>
                    <div class="col-lg-2">
                        @if (!empty(data_get($quoteData, 'insurance_card')))
                            <a href="{{ legacy_asset('files/reservation/' . data_get($quoteData, 'insurance_card')) }}"
                                title="Driver License" class="fancybox">
                                <i class="icon-magazine icon-2x"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-12 mt-2 text-center mt-10">
                        <button type="button" class="btn btn-primary pl-3 pr-3"
                            onclick="SaveDriverFinancedPolicyDetails('{{ $myModal }}')">
                            Save <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="DriverFinancedInsuranceQuote[id]"
                    value="{{ data_get($quoteData, 'id', '') }}">
                <input type="hidden" name="DriverFinancedInsuranceQuote[order_id]" value="{{ $recordid }}">
            </form>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>