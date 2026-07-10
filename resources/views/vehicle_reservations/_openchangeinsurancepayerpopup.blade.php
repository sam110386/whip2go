<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">

    <form action="#" method="POST" name="frmadmin" class="form-horizontal"
        id="OrderDepositRuleAdminChangeinsurancetypepopupForm">
        @csrf

        <div class="panel-body">
            <legend class="text-size-large text-bold">Insurance Details:</legend>

            <div class="form-group">
                <label class="col-lg-3 control-label">Insurance By :</label>
                <div class="col-lg-9">
                    <select name="OrderDepositRule[insurance_payer]" id="OrderDepositRuleInsurancePayer"
                        class="form-control required">
                        @foreach($types as $key => $value)
                            <option value="{{ $key }}" @selected(data_get($trip, 'insurance_payer') == $key)>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label">&nbsp;</label>
                <div class="col-lg-6">
                    <button type="button" class="btn btn-primary pl-3 pr-3" onclick="SaveInsurancePayerChanges()">
                        Save <i class="icon-arrow-right14 position-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <input type="hidden" name="OrderDepositRule[id]" value="{{ data_get($trip, 'id') }}">

    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>