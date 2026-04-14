<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<style>
    .form-horizontal .editable {
        padding-top: 0px;
    }
</style>
<form action="#" method="POST" name="frmadmin" class="form-horizontal">
    @csrf
    <input type="hidden" name="AxleStatus[id]" value="{{ $axleStatusArr['id'] }}">
    <div class="modal-body">
        <div class="panel-body">
            <legend class="text-size-large text-bold">Policy Status Checklist:</legend>
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        <th align="center" style="text-align:center;">#</th>
                        <th align="center" style="text-align:center;">Policy Value</th>
                        <th align="center" style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = 1; @endphp
                    @foreach ($checklist as $key => $check)
                        @php $isInsurance = strpos($key, 'insurance') !== false; @endphp
                        <tr class="{{ (isset($policychecks[$key]['accepted']) && ($policychecks[$key]['accepted'] === 'No' || $policychecks[$key]['accepted'] === '' || $policychecks[$key]['accepted'] == '0')) ? 'bg-warning' : '' }}">
                            <td class="text-bold">
                                <strong>{{ $i++ }} : {{ $check['label'] }}</strong>
                                <input type="hidden" name="AxleStatus[extra][{{ $key }}][label]" value="{{ $check['label'] }}">
                            </td>
                            <td class="control-label">
                                @if (!$isInsurance)
                                    @php $policyText = $policychecks[$key]['policy_text'] ?? $check['policy_text']; @endphp
                                    {{ $policyText }}
                                    <input type="hidden" name="AxleStatus[extra][{{ $key }}][policy_text]" value="{{ $policyText }}">
                                @else
                                    @php $insuranceRate = $policychecks[$key]['insurance_rate'] ?? $check['insurance_rate']; @endphp
                                    {{ $insuranceRate }}
                                    <input type="hidden" name="AxleStatus[extra][{{ $key }}][insurance_rate]" value="{{ $insuranceRate }}">
                                @endif
                            </td>
                            <td class="control-label">
                                @if (!$isInsurance)
                                    <input type="checkbox" name="AxleStatus[extra][{{ $key }}][accepted]" {{ (isset($policychecks[$key]) && $policychecks[$key]['accepted'] && $policychecks[$key]['accepted'] !== 'No') ? 'checked' : '' }} value="1">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="mt-5">
                        <td class="text-bold text-danger">
                            <strong>Pending Calculated Insurance Penalty</strong>
                        </td>
                        <td class="control-label" colspan="2">
                            <input type="text" class="form-control" readonly name="AxleStatus[insurance_penalty]" value="{{ $calculatedInsurance }}"/>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" onclick="axlePolicyAcceptSave()" class="btn btn-primary mt-10">Accept & Save <i class="icon-pen-plus position-right"></i></button>
        <button type="button" onclick="axlePolicySave()" class="btn btn-primary mt-10">Save <i class="icon-pen-plus position-right"></i></button>
        <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
    </div>
</form>
