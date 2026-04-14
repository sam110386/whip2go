@extends('layouts.admin')

@section('title', $listTitle ?? 'Email template')

@section('content')
<div class="panel">
    <section class="right_content">
        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom:10px;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger" style="margin-bottom:10px;">{{ session('error') }}</div>
        @endif

        @php
            $et = $emailTemplate ?? [];
            $typeVal = (string)($et['type'] ?? '');
        @endphp

        <form method="post" action="/admin/email_templates/add{{ !empty($id) ? '/'.$id : '' }}" name="frmEmailTemplates" id="frmEmailTemplates" onsubmit="if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.EmailTemplateDescription) { CKEDITOR.instances.EmailTemplateDescription.updateElement(); }">
            @csrf
            <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
                <tr>
                    <td valign="top">
                        <table align="center" width="98%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0">
                                        <tr class="adminBoxHeading reportListingHeading heading">
                                            <td class="adminGridHeading heading"><h3 style="margin:10px 0;">{{ $listTitle ?? '' }}</h3></td>
                                            <td height="25" align="right"></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <table width="100%" border="0" cellspacing="1" cellpadding="3" class="adminBox">
                                                    <tr height="20px">
                                                        <td class="error_msg" colspan="4" align="left">Fields marked with an asterisk (*) are required.</td>
                                                    </tr>
                                                    <tr>
                                                        <td align="right" width="20%"><span class="error_msg">*</span> Title :</td>
                                                        <td>
                                                            <input type="text" name="EmailTemplate[head_title]" class="form-control textbox-m required"
                                                                   value="{{ e(old('EmailTemplate.head_title', $et['head_title'] ?? '')) }}"/>
                                                            <input type="hidden" name="EmailTemplate[title]" value="{{ e(old('EmailTemplate.title', $et['title'] ?? ($et['head_title'] ?? ''))) }}"/>
                                                            @if(!empty($errors['head_title']))
                                                                <div class="text-danger">{{ $errors['head_title'] }}</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="right" valign="top"><span class="error_msg">*</span> Template Type :</td>
                                                        <td>
                                                            <select name="EmailTemplate[type]" id="EmailTemplateType" class="form-control textbox-m required" onchange="isSelectedField()">
                                                                <option value="">Select Template Type</option>
                                                                <option value="1" @selected($typeVal === '1')>Email Template</option>
                                                                <option value="2" @selected($typeVal === '2')>Reminder</option>
                                                            </select>
                                                            @if(!empty($errors['type']))
                                                                <div class="text-danger">{{ $errors['type'] }}</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr id="EmailTemplatePIDClass">
                                                        <td align="right" width="20%">Providers :</td>
                                                        <td>
                                                            <select name="EmailTemplate[provider_id][]" multiple size="5" class="form-control textbox-m">
                                                                <option value="">Select Providers</option>
                                                                @foreach(($providers ?? []) as $pid => $plabel)
                                                                    <option value="{{ $pid }}" @selected(in_array((int) $pid, array_map('intval', (array) ($et['provider_id'] ?? [])), true))>{{ e($plabel) }}</option>
                                                                @endforeach
                                                            </select>
                                                            <p class="text-muted">Press CTRL key to select multiple providers</p>
                                                        </td>
                                                    </tr>
                                                    <tr id="EmailTemplateCIDClass">
                                                        <td align="right" width="20%">Customers :</td>
                                                        <td>
                                                            <select name="EmailTemplate[customer_id][]" multiple size="5" class="form-control textbox-m">
                                                                <option value="">Select Customers</option>
                                                                @foreach(($users ?? []) as $uid => $ulabel)
                                                                    <option value="{{ $uid }}" @selected(in_array((int) $uid, array_map('intval', (array) ($et['customer_id'] ?? [])), true))>{{ e($ulabel) }}</option>
                                                                @endforeach
                                                            </select>
                                                            <p class="text-muted">Press CTRL key to select multiple customers</p>
                                                        </td>
                                                    </tr>
                                                    <tr id="EmailTemplateIDClass">
                                                        <td align="right" width="20%"><span class="error_msg">*</span> Time :</td>
                                                        <td>
                                                            <select name="EmailTemplate[reminder_time]" class="form-control textbox-m">
                                                                <option value="">Select Time</option>
                                                                @foreach(($hours ?? []) as $hk => $hlabel)
                                                                    <option value="{{ $hk }}" @selected((string)($et['reminder_time'] ?? '') === (string)$hk)>{{ e($hlabel) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="right"><span class="error_msg">*</span> Subject :</td>
                                                        <td>
                                                            <input type="text" name="EmailTemplate[subject]" class="form-control textbox-m required"
                                                                   value="{{ e(old('EmailTemplate.subject', $et['subject'] ?? '')) }}"/>
                                                            @if(!empty($errors['subject']))
                                                                <div class="text-danger">{{ $errors['subject'] }}</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td valign="top" align="right">Description :</td>
                                                        <td align="left">
                                                            <textarea name="EmailTemplate[description]" id="EmailTemplateDescription" rows="12" class="form-control" style="width:100%;">{{ old('EmailTemplate.description', $et['description'] ?? '') }}</textarea>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>&nbsp;</td>
                                                        <td height="35">
                                                            <button type="submit" class="btn btn-primary btn_53">{{ $submit_button ?? 'Save' }}</button>
                                                            <button type="button" class="btn btn-default btn_53" onclick="window.location.href='/admin/email_templates/index'">Cancel</button>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            @if(!empty($decodedId))
                <input type="hidden" name="EmailTemplate[id]" value="{{ e($id ?? base64_encode((string)$decodedId)) }}"/>
            @endif
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ legacy_asset('js/assets/js/plugins/editors/ckeditor/ckeditor.js') }}"></script>
<script>
function isSelectedField() {
    var val1 = document.getElementById('EmailTemplateType').value;
    function setDisabled(id, disabled) {
        var el = document.getElementById(id);
        if (!el) return;
        if (disabled) {
            el.classList.add('disabled');
            el.querySelectorAll('select').forEach(function(s) { s.disabled = true; });
        } else {
            el.classList.remove('disabled');
            el.querySelectorAll('select').forEach(function(s) { s.disabled = false; });
        }
    }
    if (val1 === '2') {
        setDisabled('EmailTemplateIDClass', false);
        setDisabled('EmailTemplatePIDClass', false);
        setDisabled('EmailTemplateCIDClass', false);
    } else {
        setDisabled('EmailTemplateIDClass', true);
        setDisabled('EmailTemplatePIDClass', true);
        setDisabled('EmailTemplateCIDClass', true);
    }
}
document.addEventListener('DOMContentLoaded', function() {
    if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.replace('EmailTemplateDescription', { height: 400, width: '100%' });
    }
    isSelectedField();
});
</script>
@endpush
