@extends('admin.layouts.app')

@php
    $title ??= 'Add New Lead';
    $lead ??= collect();
@endphp

@section('title', $title)


@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold"></span> {{ $title }}
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <form action="{{ url('/admin/leads/add/' . base64_encode(data_get($lead, 'id', ''))) }}" method="POST"
                    name="frmadmin" id="frmadmin" class="form-horizontal">
                    @csrf
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                Lead Type :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-9">
                                <select name="Lead[type]" id="LeadType" class="form-control required">
                                    <option value="1" @selected(data_get($lead, 'type', 1) == 1)>Driver</option>
                                    <option value="2" @selected(data_get($lead, 'type', 1) == 2)>Dealer</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                Phone :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[phone]" maxlength="16" class="form-control phone required"
                                    value="{{ data_get($lead, 'phone', '')}}" {{ !empty(data_get($lead, 'id', '')) ? 'readonly' : '' }} />
                            </div>
                        </div>
                        <div class="form-group dealername" {{ (data_get($lead, 'type', 1) == 1) ? "style='display:none'" : '' }}>
                            <label class="col-lg-3 control-label">
                                Dealer Name :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[dealer_name]" class="form-control required"
                                    value="{{ data_get($lead, 'dealer_name', '') }}" />
                            </div>
                        </div>
                        <div class="form-group drivername" {{ (data_get($lead, 'type') == 2) ? "style='display:none'" : ''}}>
                            <label class="col-lg-3 control-label">
                                First Name :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[first_name]" class="form-control required"
                                    value="{{ data_get($lead, 'first_name', '') }}" />
                            </div>
                        </div>
                        <div class="form-group drivername" {{ (data_get($lead, 'type') == 2) ? "style='display:none'" : '' }}>
                            <label class="col-lg-3 control-label">
                                Last Name :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[last_name]" class="form-control required"
                                    value="{{ data_get($lead, 'last_name', '') }}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                Email :
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[email]" class="form-control email"
                                    value="{{ data_get($lead, 'email', '') }}" />
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                Address :
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[address]" maxlength="60" class="form-control"
                                    value="{{ data_get($lead, 'address', '') }}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                City :</label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[city]" maxlength="30" class="form-control"
                                    value="{{ data_get($lead, 'city', '') }}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                State :
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[state]" maxlength="30" class="form-control"
                                    value="{{ data_get($lead, 'state', '') }}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">
                                Postal Code :
                            </label>
                            <div class="col-lg-9">
                                <input type="text" name="Lead[postal]" class="form-control"
                                    value="{{ data_get($lead, 'postal', '') }}" />
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                @if(empty(data_get($lead, 'id', '')))
                                    <button type="submit" class="btn">Save</button>
                                @else
                                    <button type="submit" class="btn">Update</button>
                                @endif
                                <button type="button" class="btn left-margin btn-cancel"
                                    onclick="goBack('/admin/leads/index')">
                                    Return
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="Lead[id]" value="{{ data_get($lead, 'id', '') }}" />
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
            jQuery("#LeadType").change(function () {
                if (jQuery(this).val() == 1) {
                    jQuery(".dealername").hide();
                    jQuery(".drivername").show();
                } else {
                    jQuery(".drivername").hide();
                    jQuery(".dealername").show();
                }
            });
        });
    </script>

@endpush