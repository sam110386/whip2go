@extends('admin.layouts.app')
@section('content')
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#PromoAddForm").validate();
        jQuery("#PromotionRuleConditionsCon1").change(function(){
            if(jQuery(this).val()!=''){
                jQuery("#PromotionRuleConditionsRule1").show();
                jQuery("#PromotionRuleConditionsDiscount1").show();
            }else{
               jQuery("#PromotionRuleConditionsRule1").hide();
               jQuery("#PromotionRuleConditionsDiscount1").hide();
            }
        });
    });
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">{{ $listTitle }}</span> - Promo</h4>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{!! session('error') !!}</div>
    @endif
</div>
<div class="panel">
    <div class="panel-body">
        <legend class="text-danger">Discount will be aplplied on per day rental</legend>
        <div class="row">
            <form action="{{ url('/admin/promo_rules/add') }}" method="POST" class="form-horizontal" id="PromoAddForm" enctype="multipart/form-data">
                @csrf
                <div class="col-lg-12">
                    <div class="row form-group">
                        <label class="col-lg-4 control-label">Promo Title</label>
                        <div class="col-lg-7">
                            <input type="text" name="PromotionRule[title]" class="form-control required" value="{{ $data['title'] ?? old('PromotionRule.title') }}" />
                        </div>
                    </div>
                    <div class="row form-group">
                        <label class="col-lg-4 control-label">Promo Code</label>
                        <div class="col-lg-7">
                            <input type="text" name="PromotionRule[promo]" class="form-control required" value="{{ $data['promo'] ?? old('PromotionRule.promo') }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Rental Discount Type :<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            <select name="PromotionRule[type]" class="form-control required">
                                <option value="flat" {{ ($data['type'] ?? '') === 'flat' ? 'selected' : '' }}>Flat</option>
                                <option value="percent" {{ ($data['type'] ?? '') === 'percent' ? 'selected' : '' }}>Percent of fare</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Discount value:<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            <input type="text" name="PromotionRule[discount]" maxlength="16" class="required form-control number" value="{{ $data['discount'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Initial Fee Discount:<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            <select name="PromotionRule[initial_discount_type]" class="form-control required">
                                <option value="flat" {{ ($data['initial_discount_type'] ?? '') === 'flat' ? 'selected' : '' }}>Flat</option>
                                <option value="percent" {{ ($data['initial_discount_type'] ?? '') === 'percent' ? 'selected' : '' }}>Percent of fare</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Discount value:<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            <input type="text" name="PromotionRule[initial_discount]" maxlength="16" class="required form-control number" value="{{ $data['initial_discount'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Uses per Passenger:<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            <input type="text" name="PromotionRule[uses_count]" maxlength="5" class="required form-control" value="{{ $data['uses_count'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Status:</label>
                        <div class="col-lg-7">
                            <select name="PromotionRule[status]" class="required form-control">
                                <option value="1" {{ ($data['status'] ?? '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ ($data['status'] ?? '1') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Conditions:<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            @php $hav = !empty($data['conditions']['con1'] ?? null) ? 'display:inline' : 'display:none'; @endphp
                            <div class="col-lg-4 no-padding">
                                <select name="PromotionRule[conditions][con1]" id="PromotionRuleConditionsCon1" style="width:100%;" class="form-control">
                                    <option value=""> Select </option>
                                    @foreach($promoconditions as $k => $v)
                                        <option value="{{ $k }}" {{ ($data['conditions']['con1'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <select name="PromotionRule[conditions][rule1]" id="PromotionRuleConditionsRule1" style="width:100%;{{ $hav }}" class="form-control">
                                    @foreach($rules as $k => $v)
                                        <option value="{{ $k }}" {{ ($data['conditions']['rule1'] ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-4 no-padding">
                                <input type="text" name="PromotionRule[conditions][discount1]" id="PromotionRuleConditionsDiscount1" style="width:100%;{{ $hav }}" maxlength="5" class="form-control" value="{{ $data['conditions']['discount1'] ?? '' }}" />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Available for Listing:</label>
                        <div class="col-lg-7">
                            <select name="PromotionRule[list]" class="required form-control">
                                <option value="1" {{ ($data['list'] ?? '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ ($data['list'] ?? '1') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Logo:</label>
                        <div class="col-lg-7">
                            <input type="file" name="PromotionRule[logo]" class="form-control" />
                        </div>
                    </div>
                    @if(!empty($data['logo']))
                    <div class="form-group">
                        <label class="col-lg-4 control-label"></label>
                        <div class="col-lg-7">
                            <img src="{{ config('app.url') }}img/promo/{{ $data['logo'] }}" width="100" />
                        </div>
                    </div>
                    @endif
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Terms:</label>
                        <div class="col-lg-7">
                            <input type="text" name="PromotionRule[terms]" class="required form-control" value="{{ $data['terms'] ?? '' }}" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            @if(!empty($data['id']))
                                <button type="submit" class="btn btn-primary">Update</button>
                            @else
                                <button type="submit" class="btn btn-primary">Save</button>
                            @endif
                            <button type="button" class="btn btn-primary" onclick="goBack('/admin/promo_rules/index')">Cancel</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="PromotionRule[id]" value="{{ $data['id'] ?? '' }}" />
            </form>
        </div>
    </div>
</div>
@endsection
