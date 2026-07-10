<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">

    <form action="{{ url('/admin/insurance/quotes/save') }}" method="POST" name="frmadmin" class="form-horizontal"
        enctype="multipart/form-data" id="InsuranceQuoteAdminPopupForm">
        @csrf

        <div class="panel-body">
            <legend class="text-size-large text-bold">BYOI By DIA:</legend>

            <div class="form-group">
                <label class="col-lg-3 control-label">
                    Insurance Provider :
                </label>
                <div class="col-lg-9">
                    <select name="InsuranceQuote[provider_id]" class="form-control required">
                        @foreach($providers as $id => $name)
                            <option value="{{ $id }}" @selected(old('InsuranceQuote.provider_id', data_get($record, 'provider_id', '')) == $id)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label">
                    Paid In Full Amount :
                </label>
                <div class="col-lg-9">
                    <input type="text" name="InsuranceQuote[quote_amount]" class="form-control required"
                        value="{{ old('InsuranceQuote.quote_amount', data_get($record, 'quote_amount', '')) }}">
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label">
                    Daily Rate :
                </label>
                <div class="col-lg-9">
                    <input type="text" name="InsuranceQuote[daily_rate]" class="form-control required"
                        value="{{ old('InsuranceQuote.daily_rate', data_get($record, 'daily_rate', '')) }}">
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label">
                    Limit:
                </label>
                <div class="col-lg-9">
                    <select name="InsuranceQuote[total_limit]" class="form-control required">
                        <option value="50/100/50" @selected(old('InsuranceQuote.total_limit', data_get($record, 'total_limit', '')) == '50/100/50')>
                            50/100/50
                        </option>
                        <option value="100/300/100" @selected(old('InsuranceQuote.total_limit', data_get($record, 'total_limit', '')) == '100/300/100')>
                            100/300/100
                        </option>
                        <option value="25/50/25" @selected(old('InsuranceQuote.total_limit', data_get($record, 'total_limit', '')) == '25/50/25')>
                            25/50/25
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label">
                    Policy Doc:
                </label>
                <div class="col-lg-8">
                    <input type="file" name="InsuranceQuote[policy_doc]" class="form-control">
                </div>
                <div class="col-lg-1 control-label">
                    @if(!empty(data_get($record, 'policy_doc')))
                        <a href="{{ legacy_asset('files/insurancequote/' . data_get($record, 'policy_doc')) }}"
                            title="Policy Doc" class="fancybox">
                            <i class="icon-magazine"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label">
                    Notify To Driver :
                </label>
                <div class="col-lg-9">
                    <input type="checkbox" name="InsuranceQuote[notify]" class="checkbox" value="1" {{ !empty(data_get($record, 'notify')) ? 'checked' : '' }}>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="form-group">
                    <label class="col-lg-3 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="button" class="btn btn-primary pl-3 pr-3"
                            onclick="SaveInsuranceProviderQuotePopUp()">
                            Save <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="InsuranceQuote[id]" value="{{ data_get($record, 'id', '') }}">
        <input type="hidden" name="InsuranceQuote[order_id]" value="{{ $bookingid }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>