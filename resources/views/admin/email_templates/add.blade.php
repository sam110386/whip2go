@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Email Template')

@section('content')
@php
    $et = $emailTemplate ?? [];
    $typeVal = (string)($et['type'] ?? '');
@endphp

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Email Templates</span> - {{ $listTitle ?? 'Add' }}
            </h4>
        </div>
        <div class="heading-elements">
            <div class="heading-btn-group">
                <button type="submit" form="frmEmailTemplates" class="btn btn-link btn-float has-text">
                    <i class="icon-database-insert text-primary"></i>
                    <span>{{ $submit_button ?? 'Save' }}</span>
                </button>
                <a href="/admin/email_templates/index" class="btn btn-link btn-float has-text">
                    <i class="icon-undo text-primary"></i>
                    <span>Return</span>
                </a>
            </div>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="/admin/email_templates/index">Email Templates</a></li>
            <li class="active">{{ $listTitle ?? 'Add' }}</li>
        </ul>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">{{ $listTitle ?? 'Email Template' }}</h5>
        </div>

        <div class="panel-body">
            <p class="text-muted"><span class="text-danger">*</span> Fields marked with an asterisk are required.</p>

            <form method="post"
                  action="/admin/email_templates/add{{ !empty($id) ? '/'.$id : '' }}"
                  name="frmEmailTemplates"
                  id="frmEmailTemplates"
                  class="form-horizontal"
                  onsubmit="if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.EmailTemplateDescription) { CKEDITOR.instances.EmailTemplateDescription.updateElement(); }">
                @csrf

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Title:<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="EmailTemplate[head_title]" class="form-control required"
                               value="{{ e(old('EmailTemplate.head_title', $et['head_title'] ?? '')) }}"/>
                        <input type="hidden" name="EmailTemplate[title]"
                               value="{{ e(old('EmailTemplate.title', $et['title'] ?? ($et['head_title'] ?? ''))) }}"/>
                        @if(!empty($errors['head_title']))
                            <div class="text-danger">{{ $errors['head_title'] }}</div>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Template Type:<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="EmailTemplate[type]" id="EmailTemplateType" class="form-control required" onchange="isSelectedField()">
                            <option value="">Select Template Type</option>
                            <option value="1" @selected($typeVal === '1')>Email Template</option>
                            <option value="2" @selected($typeVal === '2')>Reminder</option>
                        </select>
                        @if(!empty($errors['type']))
                            <div class="text-danger">{{ $errors['type'] }}</div>
                        @endif
                    </div>
                </div>

                <div class="form-group" id="EmailTemplatePIDClass">
                    <label class="col-lg-3 control-label text-semibold">Providers:</label>
                    <div class="col-lg-9">
                        <select name="EmailTemplate[provider_id][]" multiple size="5" class="form-control">
                            <option value="">Select Providers</option>
                            @foreach(($providers ?? []) as $pid => $plabel)
                                <option value="{{ $pid }}" @selected(in_array((int) $pid, array_map('intval', (array) ($et['provider_id'] ?? [])), true))>{{ e($plabel) }}</option>
                            @endforeach
                        </select>
                        <span class="help-block text-muted">Press CTRL key to select multiple providers</span>
                    </div>
                </div>

                <div class="form-group" id="EmailTemplateCIDClass">
                    <label class="col-lg-3 control-label text-semibold">Customers:</label>
                    <div class="col-lg-9">
                        <select name="EmailTemplate[customer_id][]" multiple size="5" class="form-control">
                            <option value="">Select Customers</option>
                            @foreach(($users ?? []) as $uid => $ulabel)
                                <option value="{{ $uid }}" @selected(in_array((int) $uid, array_map('intval', (array) ($et['customer_id'] ?? [])), true))>{{ e($ulabel) }}</option>
                            @endforeach
                        </select>
                        <span class="help-block text-muted">Press CTRL key to select multiple customers</span>
                    </div>
                </div>

                <div class="form-group" id="EmailTemplateIDClass">
                    <label class="col-lg-3 control-label text-semibold">Time:<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <select name="EmailTemplate[reminder_time]" class="form-control">
                            <option value="">Select Time</option>
                            @foreach(($hours ?? []) as $hk => $hlabel)
                                <option value="{{ $hk }}" @selected((string)($et['reminder_time'] ?? '') === (string)$hk)>{{ e($hlabel) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Subject:<span class="text-danger">*</span></label>
                    <div class="col-lg-9">
                        <input type="text" name="EmailTemplate[subject]" class="form-control required"
                               value="{{ e(old('EmailTemplate.subject', $et['subject'] ?? '')) }}"/>
                        @if(!empty($errors['subject']))
                            <div class="text-danger">{{ $errors['subject'] }}</div>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Description:</label>
                    <div class="col-lg-9">
                        <textarea name="EmailTemplate[description]" id="EmailTemplateDescription" rows="12" class="form-control" style="width:100%;">{{ old('EmailTemplate.description', $et['description'] ?? '') }}</textarea>
                    </div>
                </div>

                @if(!empty($decodedId))
                    <input type="hidden" name="EmailTemplate[id]" value="{{ e($id ?? base64_encode((string)$decodedId)) }}"/>
                @endif

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">{{ $submit_button ?? 'Save' }} <i class="icon-database-insert position-right"></i></button>
                    <a href="/admin/email_templates/index" class="btn btn-default">Return</a>
                </div>
            </form>
        </div>
    </div>
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
