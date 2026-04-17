@extends('admin.layouts.app')

@section('title', 'Payment setting')

@section('content')
    @php
        $r = $rule;
        $rentalOpt = !empty($r['rental_opt']) && is_array($r['rental_opt']) ? array_values($r['rental_opt']) : [['after_day_date' => '', 'after_day' => '', 'amount' => '']];
        $depositOpt = !empty($r['deposit_opt']) && is_array($r['deposit_opt']) ? array_values($r['deposit_opt']) : [['after_day_date' => '', 'after_day' => '', 'amount' => '']];
        $initialFeeOpt = !empty($r['initial_fee_opt']) && is_array($r['initial_fee_opt']) ? array_values($r['initial_fee_opt']) : [['after_day_date' => '', 'after_day' => '', 'amount' => '']];
        $durationOpt = !empty($r['duration_opt']) && is_array($r['duration_opt']) ? array_values($r['duration_opt']) : [['after_date' => '', 'after_day_date' => '', 'duration' => '']];
    @endphp

    <h1>Update — payment setting</h1>
    @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif
    @if(session('error'))<p style="color:red;">{{ session('error') }}</p>@endif

    <form method="post" action="{{ $formAction }}" class="form-horizontal" id="addForm">
        <p><em class="text-danger">Past/today scheduled payments are not re-run from this screen.</em></p>

        <div style="display:flex; flex-wrap:wrap; gap:24px;">
            <div style="flex:1; min-width:280px;">
                <h3>Rates &amp; insurance</h3>
                <p><label>Insurance<br><input class="form-control" name="OrderDepositRule[insurance]" value="{{ $r['insurance'] ?? '' }}"></label></p>
                <p><label>EMF rate<br><input class="form-control" name="OrderDepositRule[emf_rate]" value="{{ $r['emf_rate'] ?? '' }}"></label></p>
                <p><label>EMF insurance rate<br><input class="form-control" name="OrderDepositRule[emf_insu_rate]" value="{{ $r['emf_insu_rate'] ?? '' }}"></label></p>
                <p><label>Modified minimum payment<br><input class="form-control" name="OrderDepositRule[minimum_payment]" value="{{ $r['minimum_payment'] ?? '' }}"></label></p>
                <p><label>Minimum payment exp. date<br><input class="form-control" name="OrderDepositRule[minimum_payment_exp_date]" value="{{ $r['minimum_payment_exp_date'] ?? '' }}"></label></p>
                <p><label>Insurance payer<br>
                    <select class="form-control" name="OrderDepositRule[insurance_payer]">
                        @foreach($insurancePayers as $pid => $label)
                            <option value="{{ $pid }}" @selected((int)($r['insurance_payer'] ?? 0) === (int)$pid)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label></p>
                <p><label>Insurance lender id<br><input class="form-control" name="OrderDepositRule[insurance_lender]" value="{{ $r['insurance_lender'] ?? '' }}"></label></p>
                @if(!empty($promo))
                    <p><strong>Promo:</strong> attached (remove in legacy until Promo is ported).</p>
                @endif
            </div>

            <div style="flex:1; min-width:320px;" id="panelbody"
                 data-rel-rental="{{ count($rentalOpt) }}"
                 data-rel-deposit="{{ count($depositOpt) }}"
                 data-rel-initialfee="{{ count($initialFeeOpt) }}"
                 data-rel-duration="{{ count($durationOpt) }}">
                <h3>Rental &amp; deposits</h3>
                <p><label>Day rent <span style="color:red">*</span><br><input class="form-control" required name="OrderDepositRule[rental]" value="{{ $r['rental'] ?? '' }}"></label></p>
                <p><label>Day EMF <span style="color:red">*</span><br><input class="form-control" required name="OrderDepositRule[emf]" value="{{ $r['emf'] ?? '' }}"></label></p>
                <p><label>Day allowed miles <span style="color:red">*</span><br><input class="form-control" required name="OrderDepositRule[miles]" value="{{ $r['miles'] ?? '' }}"></label></p>

                <h4>Duration options</h4>
                <div id="duration_opt">
                    @foreach($durationOpt as $idx => $val)
                        @php $y = $idx + 1; @endphp
                        <div class="form-group">
                            <label>After date</label>
                            <input class="form-control" name="OrderDepositRule[duration_opt][{{ $y }}][after_date]" value="{{ $val['after_date'] ?? $val['after_day_date'] ?? '' }}">
                            <label>Duration (days)</label>
                            <select class="form-control" name="OrderDepositRule[duration_opt][{{ $y }}][duration]">
                                <option value="">—</option>
                                @foreach([1,2,3,4,5,6,7,14,30] as $d)
                                    <option value="{{ $d }}" @selected((string)($val['duration'] ?? '') === (string)$d)>{{ $d }} days</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                <h4>Rental schedule (optional tiers)</h4>
                <div id="rent_opt">
                    @foreach($rentalOpt as $idx => $val)
                        @php $i = $idx + 1; @endphp
                        <div class="form-group" style="border-bottom:1px solid #eee; padding-bottom:8px;">
                            <label>After day date</label>
                            <input class="form-control" name="OrderDepositRule[rental_opt][{{ $i }}][after_day_date]" value="{{ $val['after_day_date'] ?? '' }}">
                            <label>After day (numeric)</label>
                            <input class="form-control" name="OrderDepositRule[rental_opt][{{ $i }}][after_day]" value="{{ $val['after_day'] ?? '' }}">
                            <label>Amount</label>
                            <input class="form-control" name="OrderDepositRule[rental_opt][{{ $i }}][amount]" value="{{ $val['amount'] ?? '' }}">
                        </div>
                    @endforeach
                </div>

                <p><label>Deposit amount<br><input class="form-control" name="OrderDepositRule[deposit_amt]" value="{{ $r['deposit_amt'] ?? '' }}"></label></p>
                <div id="deposit_opt">
                    @foreach($depositOpt as $idx => $val)
                        @php $i = $idx + 1; @endphp
                        <div class="form-group" style="border-bottom:1px solid #eee; padding-bottom:8px;">
                            <label>After day date</label>
                            <input class="form-control" name="OrderDepositRule[deposit_opt][{{ $i }}][after_day_date]" value="{{ $val['after_day_date'] ?? '' }}">
                            <label>After day</label>
                            <input class="form-control" name="OrderDepositRule[deposit_opt][{{ $i }}][after_day]" value="{{ $val['after_day'] ?? '' }}">
                            <label>Amount</label>
                            <input class="form-control" name="OrderDepositRule[deposit_opt][{{ $i }}][amount]" value="{{ $val['amount'] ?? '' }}">
                        </div>
                    @endforeach
                </div>

                <p><label>Initial fee<br><input class="form-control" name="OrderDepositRule[initial_fee]" value="{{ $r['initial_fee'] ?? '' }}"></label></p>
                <div id="initialfee_opt">
                    @foreach($initialFeeOpt as $idx => $val)
                        @php $i = $idx + 1; @endphp
                        <div class="form-group" style="border-bottom:1px solid #eee; padding-bottom:8px;">
                            <label>After day date</label>
                            <input class="form-control" name="OrderDepositRule[initial_fee_opt][{{ $i }}][after_day_date]" value="{{ $val['after_day_date'] ?? '' }}">
                            <label>After day</label>
                            <input class="form-control" name="OrderDepositRule[initial_fee_opt][{{ $i }}][after_day]" value="{{ $val['after_day'] ?? '' }}">
                            <label>Amount</label>
                            <input class="form-control" name="OrderDepositRule[initial_fee_opt][{{ $i }}][amount]" value="{{ $val['amount'] ?? '' }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">Update</button>
            <button type="button" class="btn" onclick="window.location='{{ $cancelUrl }}'">Cancel</button>
        </p>

        <input type="hidden" name="OrderDepositRule[id]" value="{{ $r['id'] ?? '' }}">
        <input type="hidden" name="OrderDepositRule[start_datetime]" value="{{ $r['start_datetime'] ?? '' }}">
    </form>
@endsection
