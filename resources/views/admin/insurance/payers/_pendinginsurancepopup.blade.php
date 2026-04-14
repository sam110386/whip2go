<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">

    <div class="panel-body">
        <div class="row">
            <div class="col-lg-6">
                <form action="{{ config('app.url') }}/admin/insurance/payers/charge" method="POST" name="frmadmin" class="form-horizontal">
                @csrf

                <legend class="text-size-large text-bold">BYOI Details:</legend>
                <div class="form-group">
                    <label class="col-lg-5 control-label">Daily Rate:</label>
                    <div class="col-lg-7">
                        ${{ $data['InsurancePayer']['daily_rate'] }}/day
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-lg-5 control-label">Total Due Till Date:</label>
                    <div class="col-lg-7">
                        <input type="text" name="data[InsurancePayer][insurance]" value="{{ $calculatedAmount }}" class="form-control required">
                        <span class="hint">Please enter days factor amount</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-5 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="button" class="btn btn-primary pl-3 pr-3" onclick="saveBOIPendingInsurance()">Charge <i class="icon-arrow-right14 position-right"></i></button>
                    </div>
                </div>
                <input type="hidden" name="data[InsurancePayer][calculatedinsu]" value="{{ $calculatedAmount }}">
                <input type="hidden" name="data[InsurancePayer][orderid]" value="{{ $orderid }}">
                <input type="hidden" name="data[InsurancePayer][id]" value="{{ $data['InsurancePayer']['id'] }}">
                <input type="hidden" name="data[InsurancePayer][order_deposit_rule_id]" value="{{ $data['InsurancePayer']['order_deposit_rule_id'] }}">
                </form>
            </div>
            <div class="col-lg-6">
                <legend class="text-size-large text-bold">Payment Logs :</legend>
                <div id="transsactionlisting">

                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
