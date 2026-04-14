<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">

    <form action="{{ config('app.url') }}/admin/insurance/payers/save" method="POST" name="frmadmin" class="form-horizontal">
    @csrf
    <div class="panel-body">
        <legend class="text-size-large text-bold">BYOI Details:</legend>
        <div class="form-group">
            <label class="col-lg-2 control-label">Premium total :</label>
            <div class="col-lg-9">
                <input type="text" name="data[InsurancePayer][premium_total]" value="{{ old('InsurancePayer.premium_total', $data['InsurancePayer']['premium_total'] ?? '') }}" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">Premium finance total :</label>
            <div class="col-lg-9">
                <input type="text" name="data[InsurancePayer][premium_finance_total]" value="{{ old('InsurancePayer.premium_finance_total', $data['InsurancePayer']['premium_finance_total'] ?? '') }}" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">Policy # :</label>
            <div class="col-lg-9">
                <input type="text" name="data[InsurancePayer][policy_number]" value="{{ old('InsurancePayer.policy_number', $data['InsurancePayer']['policy_number'] ?? '') }}" class="form-control">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">Begin date :</label>
            <div class="col-lg-9">
                <input type="text" name="data[InsurancePayer][begin_date]" value="{{ old('InsurancePayer.begin_date', $data['InsurancePayer']['begin_date'] ?? '') }}" class="form-control date">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">Exp date :</label>
            <div class="col-lg-9">
                <input type="text" name="data[InsurancePayer][exp_date]" value="{{ old('InsurancePayer.exp_date', $data['InsurancePayer']['exp_date'] ?? '') }}" class="form-control date">
            </div>
        </div>
        <div class="form-group">
            <div class="col-lg-12 mt-2 text-center">
                <button type="button" class="btn btn-primary pl-3 pr-3" onclick="SaveInsurancePayerUploadPopUp('{{ $myModal }}')">Save <i class="icon-arrow-right14 position-right"></i></button>
            </div>
        </div>
        <legend class="text-size-large text-bold">Declaration Doc :</legend>
        <div class="form-group">
            <label class="col-lg-2 control-label">Upload :</label>
            <div class="col-lg-8">
                <input type="file" name="declaration_doc" class="file-input" id="InsurancePayerDeclarationDoc" data-show-preview="false" data-id="{{ $recordid }}" data-type="declaration_doc" />
            </div>
            <div class="col-lg-2">
                @if (!empty($data['InsurancePayer']['declaration_doc']))
                    <a href="{{ config('app.url') }}/files/reservation/{{ $data['InsurancePayer']['declaration_doc'] }}" title="Driver License" class="fancybox"><i class="icon-magazine"></i></a>
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
                @if (!empty($data['InsurancePayer']['insurance_card']))
                    <a href="{{ config('app.url') }}/files/reservation/{{ $data['InsurancePayer']['insurance_card'] }}" title="Driver License" class="fancybox"><i class="icon-magazine"></i></a>
                @endif
            </div>
        </div>
        
    </div>
    <input type="hidden" name="data[InsurancePayer][id]" value="{{ $data['InsurancePayer']['id'] ?? '' }}">
    <input type="hidden" name="data[InsurancePayer][order_deposit_rule_id]" value="{{ $recordid }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
