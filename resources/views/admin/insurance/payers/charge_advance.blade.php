<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">

    <form action="{{ config('app.url') }}/admin/insurance/payers/process_charge_advance" method="POST" name="frmadmin" class="form-horizontal">
    @csrf
    <div class="panel-body">
        <legend class="text-size-large text-bold">BYOI Advance Insurance Charge:</legend>
        <div class="form-group">
            <label class="col-lg-5 control-label">Enter days for advance charges :</label>
            <div class="col-lg-7">
                <input type="text" name="data[InsurancePayer][days]" value="{{ old('InsurancePayer.days', $data['InsurancePayer']['days'] ?? '') }}" class="form-control required">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-5 control-label">Total Amount To Charge :</label>
            <label class="col-lg-5 control-label text-bold" id="amountocharge">$0</label>
        </div>

        <div class="col-lg-12">
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-6">
                    <button type="button" class="btn btn-primary pl-3 pr-3" onclick="processInsuranceInAdvanceCharge()">Charge <i class="icon-arrow-right14 position-right"></i></button>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="data[InsurancePayer][id]" value="{{ $data['InsurancePayer']['id'] ?? '' }}">
    <input type="hidden" name="data[InsurancePayer][daily_rate]" value="{{ $data['InsurancePayer']['daily_rate'] ?? '' }}">
    <input type="hidden" name="data[InsurancePayer][order_deposit_rule_id]" value="{{ $orderruleid }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
