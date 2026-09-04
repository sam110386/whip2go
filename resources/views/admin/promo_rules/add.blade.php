@extends('admin.layouts.app')

@php
    $listTitle ??= 'Add';
    $data ??= [];
    $title = "{$listTitle} - Promo";
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $listTitle }}</span> - Promo
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <legend class="text-danger">Discount will be aplplied on per day rental</legend>

            <div class="row">
                <form action="{{ url('/admin/promo_rules/add') }}" method="POST" class="form-horizontal" id="PromoAddForm"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="col-lg-12">

                        <div class="row form-group">
                            <label class="col-lg-4 control-label">
                                Promo Title
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="PromotionRule[title]" class="form-control required"
                                    value="{{ data_get($data, 'title', old('PromotionRule.title')) }}" />
                            </div>
                        </div>

                        <div class="row form-group">
                            <label class="col-lg-4 control-label">
                                Promo Code
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="PromotionRule[promo]" class="form-control required"
                                    value="{{ data_get($data, 'promo', old('PromotionRule.promo')) }}" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Rental Discount Type :<font class="requiredField">*</font>
                            </label>
                            <div class="col-lg-7">
                                <select name="PromotionRule[type]" class="form-control required">
                                    <option value="flat" @selected(data_get($data, 'type', old('PromotionRule.type')) === 'flat')>Flat</option>
                                    <option value="percent" @selected(data_get($data, 'type', old('PromotionRule.type')) === 'percent')>
                                        Percent of fare</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Discount value:<font class="requiredField">*</font>
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="PromotionRule[discount]" maxlength="16"
                                    class="required form-control number"
                                    value="{{ data_get($data, 'discount', old('PromotionRule.discount')) }}" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Initial Fee Discount:<font class="requiredField">*</font>
                            </label>
                            <div class="col-lg-7">
                                <select name="PromotionRule[initial_discount_type]" class="form-control required">
                                    <option value="flat" @selected(data_get($data, 'initial_discount_type', old('PromotionRule.initial_discount_type')) === 'flat')>Flat</option>
                                    <option value="percent" @selected(data_get($data, 'initial_discount_type', old('PromotionRule.initial_discount_type')) === 'percent')>Percent of fare</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Discount value:<font class="requiredField">*</font>
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="PromotionRule[initial_discount]" maxlength="16"
                                    class="required form-control number"
                                    value="{{ data_get($data, 'initial_discount', old('PromotionRule.initial_discount')) }}" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Uses per Passenger:<font class="requiredField">*</font>
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="PromotionRule[uses_count]" maxlength="5"
                                    class="required form-control"
                                    value="{{ data_get($data, 'uses_count', old('PromotionRule.uses_count')) }}" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Status:
                            </label>
                            <div class="col-lg-7">
                                <select name="PromotionRule[status]" class="required form-control">
                                    <option value="1" @selected((string) data_get($data, 'status', old('PromotionRule.status', 1)) === '1')>
                                        Active
                                    </option>
                                    <option value="0" @selected((string) data_get($data, 'status', old('PromotionRule.status', 1)) === '0')>
                                        Inactive
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Conditions:<font class="requiredField">*</font>
                            </label>
                            <div class="col-lg-7">
                                @php
                                    $hav = !empty(data_get($data, 'conditions.con1', old('PromotionRule.conditions.con1'))) ? 'display:inline' : 'display:none';
                                @endphp
                                <div class="col-lg-4 no-padding">
                                    <select name="PromotionRule[conditions][con1]" id="PromotionRuleConditionsCon1"
                                        style="width:100%;" class="form-control">
                                        <option value=""> Select </option>
                                        @foreach($promoconditions as $k => $v)
                                            <option value="{{ $k }}" @selected(data_get($data, 'conditions.con1', old('PromotionRule.conditions.con1')) === $k)>
                                                {{ $v }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4">
                                    <select name="PromotionRule[conditions][rule1]" id="PromotionRuleConditionsRule1"
                                        style="width:100%;{{ $hav }}" class="form-control">
                                        @foreach($rules as $k => $v)
                                            <option value="{{ $k }}" @selected(data_get($data, 'conditions.rule1', old('PromotionRule.conditions.rule1')) === $k)>
                                                {{ $v }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 no-padding">
                                    <input type="text" name="PromotionRule[conditions][discount1]"
                                        id="PromotionRuleConditionsDiscount1" style="width:100%;{{ $hav }}" maxlength="5"
                                        class="form-control"
                                        value="{{ data_get($data, 'conditions.discount1', old('PromotionRule.conditions.discount1')) }}" />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Available for Listing:
                            </label>
                            <div class="col-lg-7">
                                <select name="PromotionRule[list]" class="required form-control">
                                    <option value="1" @selected((string) data_get($data, 'list', old('PromotionRule.list', 1)) === '1')>
                                        Active
                                    </option>
                                    <option value="0" @selected((string) data_get($data, 'list', old('PromotionRule.list', 1)) === '0')>
                                        Inactive
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Logo:
                            </label>
                            <div class="col-lg-7">
                                <input type="file" name="PromotionRule[logo]" class="form-control" />
                            </div>
                        </div>

                        @if(!empty(data_get($data, 'logo')))
                            <div class="form-group">
                                <label class="col-lg-4 control-label"></label>
                                <div class="col-lg-7">
                                    <img src="{{ legacy_asset('img/promo/' . data_get($data, 'logo')) }}" width="100" />
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Terms:
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="PromotionRule[terms]" class="required form-control"
                                    value="{{ data_get($data, 'terms', old('PromotionRule.terms')) }}" />
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="submit" class="btn btn-primary">
                                    {{ !empty(data_get($data, 'id')) ? 'Update' : 'Save' }}
                                </button>
                                <button type="button" class="btn btn-primary" onclick="goBack('/admin/promo_rules/index')">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="PromotionRule[id]" value="{{ data_get($data, 'id') }}" />
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#PromoAddForm").validate();
            jQuery("#PromotionRuleConditionsCon1").change(function () {
                if (jQuery(this).val() != '') {
                    jQuery("#PromotionRuleConditionsRule1").show();
                    jQuery("#PromotionRuleConditionsDiscount1").show();
                } else {
                    jQuery("#PromotionRuleConditionsRule1").hide();
                    jQuery("#PromotionRuleConditionsDiscount1").hide();
                }
            });
        });
    </script>
@endpush