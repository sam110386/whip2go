{{-- Full booking sheet: parity with Cake `Reports/_details.ctp` and `_autorenewddetails.ctp`. --}}
<div class="panel">
    <div class="panel-body">
        <div class="row">
            @if(empty($csorder))
                <p>Sorry no order found.</p>
            @elseif(!empty($isAutorenew))
                <div class="col-lg-6">
                    <legend>Program Usage Cycle</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Customer :</label>
                        <div class="col-lg-6">{{ $csorder['User']['first_name'] }} {{ $csorder['User']['last_name'] }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Vehicle :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['vehicle_name'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Booking# :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['increment_id'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Start Date Time :</label>
                        <div class="col-lg-6">{{ \App\Support\BookingReportDetailPresenter::formatDateTime($csorder['CsOrder']['start_datetime'] ?? null, $csorder['CsOrder']['timezone'] ?? null) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">End Date Time :</label>
                        <div class="col-lg-6">{{ \App\Support\BookingReportDetailPresenter::formatDateTime($lastOrder['CsOrder']['end_datetime'] ?? null, $lastOrder['CsOrder']['timezone'] ?? null) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Actual End Time :</label>
                        <div class="col-lg-6">@if(!empty($lastOrder['CsOrder']['end_timing'])){{ \App\Support\BookingReportDetailPresenter::formatDateTime($lastOrder['CsOrder']['end_timing'], $lastOrder['CsOrder']['timezone'] ?? null) }}@else N/A @endif</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Renew Time :</label>
                        <div class="col-lg-6">{{ \App\Support\BookingReportDetailPresenter::formatDateTime($lastOrder['CsOrder']['created'] ?? null, $lastOrder['CsOrder']['timezone'] ?? null) }}</div>
                    </div>
                    <legend>Fees</legend>
                    @php $s0 = $subOrders[0] ?? []; @endphp
                    <div class="form-group">
                        <label class="col-lg-6">Initial Fee :</label>
                        <div class="col-lg-6">{{ $s0['initial_fee'] ?? 0 }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Initial Fee Tax :</label>
                        <div class="col-lg-6">{{ $s0['initial_fee_tax'] ?? 0 }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Initial Fee Discount :</label>
                        <div class="col-lg-6">{{ $s0['initial_discount'] ?? 0 }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Initial Fee:</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', (float)($s0['initial_fee'] ?? 0) + (float)($s0['initial_fee_tax'] ?? 0)) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Usage :</label>
                        <div class="col-lg-6">{{ (float)($s0['rent'] ?? 0) + (float)($s0['discount'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Discount :</label>
                        <div class="col-lg-6">{{ $s0['discount'] ?? 0 }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Extra Mileage Fee :</label>
                        <div class="col-lg-6">{{ $s0['extra_mileage_fee'] ?? 0 }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">DIA Fee :</label>
                        <div class="col-lg-6">{{ $s0['dia_fee'] ?? 0 }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Usage:</label>
                        <div class="col-lg-5">{{ $usageRent }}</div>
                        <div class="col-lg-1">*A</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Tax :</label>
                        <div class="col-lg-5">{{ $usageTax }}</div>
                        <div class="col-lg-1 text-bold">*B</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Usage With Tax:</label>
                        <div class="col-lg-4">{{ sprintf('%0.2f', $usageRent + $usageTax) }}</div>
                        <div class="col-lg-2">*(A+B)</div>
                    </div>
                    <legend>Insurance</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Insurance Payer :</label>
                        <div class="col-lg-6">{{ $insurance_payer }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Insurance Amount :</label>
                        <div class="col-lg-6">{{ $s0['insurance_amt'] ?? 0 }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">DIA Ins Add On Fee :</label>
                        <div class="col-lg-6">{{ $s0['dia_insu'] ?? 0 }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Insurance:</label>
                        <div class="col-lg-6">{{ $insufee }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Violations/Deductions:</label>
                        <div class="col-lg-6">{{ $tolls }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Due:</label>
                        <div class="col-lg-6">{{ $totalDues }}</div>
                    </div>
                    <legend>Payments</legend>
                    @if(!empty($payments))
                        <div class="form-group">
                            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th class="text-center">Amount</th>
                                        <th class="text-center">Usage</th>
                                        <th class="text-center">Tax</th>
                                        <th class="text-center">Dia Fee</th>
                                        <th class="text-center">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $key => $records)
                                        <tr>
                                            <th colspan="6" class="text-center text-bold">{{ $paymentTypeValue[$key] ?? 'N/A' }}</th>
                                        </tr>
                                        @php $txnamount = $txnrent = $txntax = $txndia_fee = 0; @endphp
                                        @foreach($records as $payment)
                                            <tr>
                                                <td class="text-center">{{ $payment['id'] ?? '' }}</td>
                                                <td class="text-center">{{ $payment['amount'] ?? '' }}@php $txnamount += (float)($payment['amount'] ?? 0); @endphp</td>
                                                <td class="text-center">{{ $payment['rent'] ?? '' }}@php $txnrent += (float)($payment['rent'] ?? 0); @endphp</td>
                                                <td class="text-center">{{ $payment['tax'] ?? '' }}@php $txntax += (float)($payment['tax'] ?? 0); @endphp</td>
                                                <td class="text-center">{{ $payment['dia_fee'] ?? '' }}@php $txndia_fee += (float)($payment['dia_fee'] ?? 0); @endphp</td>
                                                <td class="text-center">{{ $payment['charged_at'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td class="text-center text-bold">Total</td>
                                            <td class="text-center text-bold">{{ $txnamount }}</td>
                                            <td class="text-center text-bold">{{ $txnrent }}</td>
                                            <td class="text-center text-bold">{{ $txntax }}</td>
                                            <td class="text-center text-bold">{{ $txndia_fee }}</td>
                                            <td><span class="text-warning">{{ $calcualted[$key] ?? $calcualted[(int)$key] ?? '' }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Paid Amount :</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $totalGrandPaid) }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Driver Bad Debt :</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', (float) $totalDues - (float) $totalGrandPaid) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Start Ododmeter :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['start_odometer'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">End Ododmeter :</label>
                        <div class="col-lg-6">{{ $lastOrder['CsOrder']['end_odometer'] ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Distance :</label>
                        <div class="col-lg-6">@if(($lastOrder['CsOrder']['end_odometer'] ?? 0) > 0){{ (float)($lastOrder['CsOrder']['end_odometer'] ?? 0) - (float)($csorder['CsOrder']['start_odometer'] ?? 0) }}@else N/A @endif</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Details :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['details'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Extention Request Date :</label>
                        <div class="col-lg-6"></div>
                    </div>
                </div>
                @include('reports._booking_details_program_column')
            @else
                <div class="col-lg-6">
                    <legend>Program Usage Cycle</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Customer :</label>
                        <div class="col-lg-6">{{ $csorder['User']['first_name'] }} {{ $csorder['User']['last_name'] }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Vehicle :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['vehicle_name'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Booking# :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['increment_id'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Start Date Time :</label>
                        <div class="col-lg-6">{{ \App\Support\BookingReportDetailPresenter::formatDateTime($csorder['CsOrder']['start_datetime'] ?? null, $csorder['CsOrder']['timezone'] ?? null) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">End Date Time :</label>
                        <div class="col-lg-6">{{ \App\Support\BookingReportDetailPresenter::formatDateTime($csorder['CsOrder']['end_datetime'] ?? null, $csorder['CsOrder']['timezone'] ?? null) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Actual End Time :</label>
                        <div class="col-lg-6">@if(!empty($csorder['CsOrder']['end_timing'])){{ \App\Support\BookingReportDetailPresenter::formatDateTime($csorder['CsOrder']['end_timing'], $csorder['CsOrder']['timezone'] ?? null) }}@else N/A @endif</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Renew Time :</label>
                        <div class="col-lg-6">{{ \App\Support\BookingReportDetailPresenter::formatDateTime($csorder['CsOrder']['created'] ?? null, $csorder['CsOrder']['timezone'] ?? null) }}</div>
                    </div>
                    <legend>Fees</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Initial Fee :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['initial_fee'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Initial Fee Tax :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['initial_fee_tax'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Initial Fee Discount :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['initial_discount'] ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Initial Fee:</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', (float)($csorder['CsOrder']['initial_fee'] ?? 0) + (float)($csorder['CsOrder']['initial_fee_tax'] ?? 0)) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Usage :</label>
                        <div class="col-lg-6">{{ (float)($csorder['CsOrder']['rent'] ?? 0) + (float)($csorder['CsOrder']['discount'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Discount :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['discount'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Extra Mileage Fee :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['extra_mileage_fee'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">DIA Fee :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['dia_fee'] ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Usage:</label>
                        <div class="col-lg-5">{{ $usageRent }}</div>
                        <div class="col-lg-1">*A</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Tax :</label>
                        <div class="col-lg-5">{{ $usageTax }}</div>
                        <div class="col-lg-1 text-bold">*B</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Usage with Tax:</label>
                        <div class="col-lg-4">{{ sprintf('%0.2f', $usageRent + $usageTax) }}</div>
                        <div class="col-lg-1 text-bold">*(A+B)</div>
                    </div>
                    <legend>Insurance</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Insurance Payer :</label>
                        <div class="col-lg-6">{{ $insurance_payer }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Insurance Amount :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['insurance_amt'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">DIA Ins Add On Fee :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['dia_insu'] ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Insurance:</label>
                        <div class="col-lg-6">{{ $insufee }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Violations/Deductions:</label>
                        <div class="col-lg-6">{{ $tolls }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Due:</label>
                        <div class="col-lg-6">{{ $totalDues }}</div>
                    </div>
                    <legend>Payments</legend>
                    @if(!empty($payments))
                        <div class="form-group">
                            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th class="text-center">Amount</th>
                                        <th class="text-center">Usage</th>
                                        <th class="text-center">Tax</th>
                                        <th class="text-center">Dia Fee</th>
                                        <th class="text-center">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $key => $records)
                                        <tr>
                                            <th colspan="6" class="text-center text-bold">{{ $paymentTypeValue[$key] ?? 'N/A' }}</th>
                                        </tr>
                                        @php $txnamount = $txnrent = $txntax = $txndia_fee = 0; @endphp
                                        @foreach($records as $payment)
                                            <tr>
                                                <td class="text-center">{{ $payment['id'] ?? '' }}</td>
                                                <td class="text-center">{{ $payment['amount'] ?? '' }}@php $txnamount += (float)($payment['amount'] ?? 0); @endphp</td>
                                                <td class="text-center">{{ $payment['rent'] ?? '' }}@php $txnrent += (float)($payment['rent'] ?? 0); @endphp</td>
                                                <td class="text-center">{{ $payment['tax'] ?? '' }}@php $txntax += (float)($payment['tax'] ?? 0); @endphp</td>
                                                <td class="text-center">{{ $payment['dia_fee'] ?? '' }}@php $txndia_fee += (float)($payment['dia_fee'] ?? 0); @endphp</td>
                                                <td class="text-center">{{ $payment['charged_at'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td class="text-center text-bold">Total</td>
                                            <td class="text-center text-bold">{{ $txnamount }}</td>
                                            <td class="text-center text-bold">{{ $txnrent }}</td>
                                            <td class="text-center text-bold">{{ $txntax }}</td>
                                            <td class="text-center text-bold">{{ $txndia_fee }}</td>
                                            <td></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Paid Amount :</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $totalGrandPaid) }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Driver Bad Debt :</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', (float) $totalDues - (float) $totalGrandPaid) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Start Ododmeter :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['start_odometer'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">End Ododmeter :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['end_odometer'] ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6">Total Distance :</label>
                        <div class="col-lg-6">@if(($csorder['CsOrder']['end_odometer'] ?? 0) > 0){{ (float)($csorder['CsOrder']['end_odometer'] ?? 0) - (float)($csorder['CsOrder']['start_odometer'] ?? 0) }}@else N/A @endif</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Details :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['details'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Extention Request Date :</label>
                        <div class="col-lg-6"></div>
                    </div>
                </div>
                @include('reports._booking_details_program_column')
            @endif

            @if(!empty($Promo))
                <div class="col-lg-6">
                    <div class="formgroup">
                        <legend class="text-semibold">Attached Promo Rule To Driver</legend>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-6 control-label"><strong>Code:</strong> {{ $Promo['PromotionRule']['promo'] ?? '' }}</div>
                        <div class="col-lg-6 control-label"><strong>Title:</strong> {{ $Promo['PromotionRule']['title'] ?? '' }}</div>
                    </div>
                </div>
            @endif

            @if(!empty($extlogs))
                <div class="col-lg-6">
                    <div class="formgroup">
                        <legend class="text-semibold">Extention Logs</legend>
                    </div>
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                <th style="text-align:center;">Booking#</th>
                                <th style="text-align:center;">Extended Date</th>
                                <th style="text-align:center;">Note</th>
                                <th style="text-align:center;">By</th>
                                <th style="text-align:center;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($extlogs as $extlog)
                                @php $oid = (int)($extlog['OrderExtlog']['cs_order_id'] ?? 0); @endphp
                                <tr>
                                    <td style="text-align:center;">{{ $Siblingbooking[$oid] ?? $Siblingbooking[(string)$oid] ?? '—' }}</td>
                                    <td style="text-align:center;">{{ \App\Support\BookingReportDetailPresenter::formatExtLogDateTime($extlog['OrderExtlog']['ext_date'] ?? null, $extLogTz ?? null) }}</td>
                                    <td style="text-align:center;">{{ $extlog['OrderExtlog']['note'] ?? '-' }}</td>
                                    <td style="text-align:center;">{{ ($extlog['Owner']['first_name'] ?? '') }} {{ ($extlog['Owner']['last_name'] ?? '') }}</td>
                                    <td style="text-align:center;">{{ \App\Support\BookingReportDetailPresenter::formatExtLogDateTime($extlog['OrderExtlog']['created'] ?? null, $extLogTz ?? null) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
