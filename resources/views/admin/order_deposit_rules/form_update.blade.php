@extends('admin.layouts.app')

@section('title', 'Payment Setting')

@section('content')
    @php
        $r = $rule;
        $rentalOpt = !empty($r['rental_opt']) && is_array($r['rental_opt']) ? array_values($r['rental_opt']) : [['after_day_date' => '', 'after_day' => '', 'amount' => '']];
        $depositOpt = !empty($r['deposit_opt']) && is_array($r['deposit_opt']) ? array_values($r['deposit_opt']) : [['after_day_date' => '', 'after_day' => '', 'amount' => '']];
        $initialFeeOpt = !empty($r['initial_fee_opt']) && is_array($r['initial_fee_opt']) ? array_values($r['initial_fee_opt']) : [['after_day_date' => '', 'after_day' => '', 'amount' => '']];
        $durationOpt = !empty($r['duration_opt']) && is_array($r['duration_opt']) ? array_values($r['duration_opt']) : [['after_date' => '', 'after_day_date' => '', 'duration' => '']];
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Update</span> - Payment Setting
                </h4>
                <div class="heading-elements">
                    <button type="submit" form="addForm" class="btn btn-primary heading-btn">
                        Update <i class="icon-database-insert position-right"></i>
                    </button>
                    <a href="{{ $cancelUrl }}" class="btn btn-default heading-btn">
                        <i class="icon-arrow-left8 position-left"></i> Return
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <form method="post" action="{{ $formAction }}" class="form-horizontal" id="addForm">
            @csrf

            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">Payment Details</h5>
                    <p class="text-danger no-margin-top">
                        <em>*Please note all past date including today, scheduled payments will not be processed</em>
                    </p>
                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <legend class="text-semibold">Rates &amp; Insurance</legend>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Insurance :</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="OrderDepositRule[insurance]" value="{{ $r['insurance'] ?? '' }}" maxlength="20">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">EMF Rate :</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="OrderDepositRule[emf_rate]" value="{{ $r['emf_rate'] ?? '' }}" maxlength="20">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">EMF Insurance Rate :</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="OrderDepositRule[emf_insu_rate]" value="{{ $r['emf_insu_rate'] ?? '' }}" maxlength="20">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Modified Minimum Payment :</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="OrderDepositRule[minimum_payment]" value="{{ $r['minimum_payment'] ?? '' }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Minimum Payment Exp Date :</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="OrderDepositRule[minimum_payment_exp_date]" value="{{ $r['minimum_payment_exp_date'] ?? '' }}" maxlength="10">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Insurance Lender :</label>
                                <div class="col-lg-9">
                                    <select class="form-control" name="OrderDepositRule[insurance_payer]">
                                        @foreach($insurancePayers as $pid => $label)
                                            <option value="{{ $pid }}" @selected((int)($r['insurance_payer'] ?? 0) === (int)$pid)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <em class="help-block text-muted">This setting will be applied to Insurance. Insurance will be charged according to this setting</em>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Insurance Lender Id :</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="OrderDepositRule[insurance_lender]" value="{{ $r['insurance_lender'] ?? '' }}">
                                    <em class="help-block text-muted">This setting will be applied to Insurance transfer. Charged Insurance will be transferred according to this setting</em>
                                </div>
                            </div>

                            @if(!empty($promo))
                                <div class="form-group">
                                    <label class="col-lg-3 control-label">Attached Promo :</label>
                                    <div class="col-lg-9">
                                        <strong>Promo attached</strong>
                                        <em class="help-block text-muted">Remove in legacy until Promo is ported.</em>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-lg-6" id="panelbody"
                             data-rel-rental="{{ count($rentalOpt) }}"
                             data-rel-deposit="{{ count($depositOpt) }}"
                             data-rel-initialfee="{{ count($initialFeeOpt) }}"
                             data-rel-duration="{{ count($durationOpt) }}">
                            <legend class="text-semibold">Rental &amp; Deposits</legend>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Day Rent : <span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" required name="OrderDepositRule[rental]" value="{{ $r['rental'] ?? '' }}" maxlength="5">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Day EMF : <span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" required name="OrderDepositRule[emf]" value="{{ $r['emf'] ?? '' }}" maxlength="5">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Day Allowed Miles : <span class="text-danger">*</span></label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" required name="OrderDepositRule[miles]" value="{{ $r['miles'] ?? '' }}" maxlength="5">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Duration Options :</label>
                                <div class="col-lg-9">
                                    <div id="duration_opt">
                                        @foreach($durationOpt as $idx => $val)
                                            @php $y = $idx + 1; @endphp
                                            <div class="form-group">
                                                <label class="col-lg-4 control-label">After date</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[duration_opt][{{ $y }}][after_date]" value="{{ $val['after_date'] ?? $val['after_day_date'] ?? '' }}">
                                                </div>
                                                <label class="col-lg-4 control-label">Duration (days)</label>
                                                <div class="col-lg-8">
                                                    <select class="form-control" name="OrderDepositRule[duration_opt][{{ $y }}][duration]">
                                                        <option value="">—</option>
                                                        @foreach([1,2,3,4,5,6,7,14,30] as $d)
                                                            <option value="{{ $d }}" @selected((string)($val['duration'] ?? '') === (string)$d)>{{ $d }} days</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Rental Schedule :</label>
                                <div class="col-lg-9">
                                    <div id="rent_opt">
                                        @foreach($rentalOpt as $idx => $val)
                                            @php $i = $idx + 1; @endphp
                                            <div class="form-group" style="border-bottom:1px solid #eee; padding-bottom:8px;">
                                                <label class="col-lg-4 control-label">After day date</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[rental_opt][{{ $i }}][after_day_date]" value="{{ $val['after_day_date'] ?? '' }}">
                                                </div>
                                                <label class="col-lg-4 control-label">After day (numeric)</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[rental_opt][{{ $i }}][after_day]" value="{{ $val['after_day'] ?? '' }}">
                                                </div>
                                                <label class="col-lg-4 control-label">Amount</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[rental_opt][{{ $i }}][amount]" value="{{ $val['amount'] ?? '' }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Deposit Amount :</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="OrderDepositRule[deposit_amt]" value="{{ $r['deposit_amt'] ?? '' }}" placeholder="Deposit">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Deposit Schedule :</label>
                                <div class="col-lg-9">
                                    <div id="deposit_opt">
                                        @foreach($depositOpt as $idx => $val)
                                            @php $i = $idx + 1; @endphp
                                            <div class="form-group" style="border-bottom:1px solid #eee; padding-bottom:8px;">
                                                <label class="col-lg-4 control-label">After day date</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[deposit_opt][{{ $i }}][after_day_date]" value="{{ $val['after_day_date'] ?? '' }}">
                                                </div>
                                                <label class="col-lg-4 control-label">After day</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[deposit_opt][{{ $i }}][after_day]" value="{{ $val['after_day'] ?? '' }}">
                                                </div>
                                                <label class="col-lg-4 control-label">Amount</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[deposit_opt][{{ $i }}][amount]" value="{{ $val['amount'] ?? '' }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Initial Fee :</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control" name="OrderDepositRule[initial_fee]" value="{{ $r['initial_fee'] ?? '' }}" placeholder="Initial Fee">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Initial Fee Schedule :</label>
                                <div class="col-lg-9">
                                    <div id="initialfee_opt">
                                        @foreach($initialFeeOpt as $idx => $val)
                                            @php $i = $idx + 1; @endphp
                                            <div class="form-group" style="border-bottom:1px solid #eee; padding-bottom:8px;">
                                                <label class="col-lg-4 control-label">After day date</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[initial_fee_opt][{{ $i }}][after_day_date]" value="{{ $val['after_day_date'] ?? '' }}">
                                                </div>
                                                <label class="col-lg-4 control-label">After day</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[initial_fee_opt][{{ $i }}][after_day]" value="{{ $val['after_day'] ?? '' }}">
                                                </div>
                                                <label class="col-lg-4 control-label">Amount</label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" name="OrderDepositRule[initial_fee_opt][{{ $i }}][amount]" value="{{ $val['amount'] ?? '' }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-10">
                                    <button type="submit" class="btn btn-primary">Update <i class="icon-database-insert position-right"></i></button>
                                    <button type="button" class="btn btn-default left-margin btn-cancel" onclick="window.location='{{ $cancelUrl }}'">Return</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="OrderDepositRule[id]" value="{{ $r['id'] ?? '' }}">
            <input type="hidden" name="OrderDepositRule[start_datetime]" value="{{ $r['start_datetime'] ?? '' }}">
        </form>
    </div>
@endsection
