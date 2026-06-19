<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <form action="{{ url('admin/payers/saveinsurance') }}" method="POST" name="frmadmin" class="form-horizontal">
        @csrf

        <div class="panel-body">
            <legend class="text-size-large text-bold">Insurance Details:</legend>

            <div class="form-group">
                <label class="col-lg-2 control-label">Daily Rate :</label>
                <div class="col-lg-9">
                    <input type="text" name="OrderDepositRule[insurance]" id="OrderDepositRuleInsurance"
                        class="form-control required"
                        value="{{ old('OrderDepositRule.insurance', data_get($orderRule, 'insurance', '')) }}">
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label">Notify To Driver :</label>
                <div class="col-lg-9">
                    <div class="radio">
                        <label>
                            <div class="border-primary-600 text-primary-800">
                                <span>
                                    <input type="radio" name="OrderDepositRule[notify]" class="control-primary"
                                        value="1">
                                </span>
                            </div>
                            Notify As Insurance Changed
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <div class="border-primary-600 text-primary-800">
                                <span>
                                    <input type="radio" name="OrderDepositRule[notify]" class="control-primary"
                                        value="2">
                                </span>
                            </div>
                            Notify As Roi Quote
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <div class="border-primary-600 text-primary-800">
                                <span class="checked">
                                    <input type="radio" name="OrderDepositRule[notify]" class="control-primary"
                                        value="3" checked="checked">
                                </span>
                            </div>
                            Dont Notify
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="button" class="btn btn-primary pl-3 pr-3"
                            onclick="SaveInsuranceChanges('plaidModal')">
                            Save <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="OrderDepositRule[id]" value="{{ data_get($orderRule, 'id', '') }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>