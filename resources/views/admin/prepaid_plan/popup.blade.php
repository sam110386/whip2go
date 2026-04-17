<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="{{ url('admin/insurance/payers/save') }}" method="POST" name="frmadmin" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <legend class="text-size-large text-bold">BYOI Details:</legend>
            <legend class="text-size-large text-bold">Declaration Doc :</legend>
            <div class="form-group">
                <label class="col-lg-2 control-label">Upload :</label>
                <div class="col-lg-8">
                    <input type="file" name="declaration_doc" class="file-input" id="InsurancePayerDeclarationDoc" data-show-preview="false" data-id="{{ $recordid }}" data-type="declaration_doc" />
                </div>
                <div class="col-lg-2">
                    @if(!empty($insurancePayer->declaration_doc))
                        <a href="{{ config('app.url') }}files/reservation/{{ $insurancePayer->declaration_doc }}" title="Driver License" class="fancybox"><i class="icon-magazine"></i></a>
                    @endif
                </div>
            </div>
            <legend class="text-size-large text-bold">Insurance Card :</legend>
            <div class="form-group">
                <label class="col-lg-2 control-label">Upload :</label>
                <div class="col-lg-8">
                    <input type="file" name="insurance_card" class="file-input" id="InsurancePayerInsuranceCard" data-show-preview="false" data-id="{{ $recordid }}" data-type="insurance_card" />
                </div>
                <div class="col-lg-2">
                    @if(!empty($insurancePayer->insurance_card))
                        <a href="{{ config('app.url') }}files/reservation/{{ $insurancePayer->insurance_card }}" title="Driver License" class="fancybox"><i class="icon-magazine"></i></a>
                    @endif
                </div>
            </div>
            <div class="col-lg-12">
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="button" class="btn btn-primary pl-3 pr-3" onclick="SaveInsurancePayerUploadPopUp()">Save <i class="icon-arrow-right14 position-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="InsurancePayer[id]" value="">
        <input type="hidden" name="InsurancePayer[order_deposit_rule_id]" value="{{ $recordid }}">
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
