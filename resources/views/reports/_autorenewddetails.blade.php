<div class="panel">
    <div class="panel-body">
        <div class="row">
            @if (!empty($csorder))
                @php
                    $calcualted=[];
                @endphp
                <form class="form-horizontal">
                    <div class="col-lg-6">
                        <legend>Program Usage Cycle</legend>
                        <div class="form-group">
                            <label class="col-lg-6">Customer :</label>
                            <div class="col-lg-6">
                                {{ $csorder?->user?->first_name . ' ' . $csorder?->user?->last_name }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Vehicle :</label>
                            <div class="col-lg-6">
                                {{ $csorder->vehicle_name }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Booking# :</label>
                            <div class="col-lg-6">
                                {{ $csorder->increment_id }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Start Date Time :</label>
                            <div class="col-lg-6">
                                {{ !empty($csorder->start_datetime) && strpos($csorder->start_datetime, '0000') !== 0 ? \Carbon\Carbon::parse($csorder->start_datetime)->timezone($csorder->timezone ?? config('app.timezone'))->format("m/d/Y h:i A") : '--' }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">End Date Time :</label>
                            <div class="col-lg-6">
                                {{ !empty($lastOrder->end_datetime) && strpos($lastOrder->end_datetime, '0000') !== 0 ? \Carbon\Carbon::parse($lastOrder->end_datetime)->timezone($lastOrder->timezone ?? config('app.timezone'))->format("m/d/Y h:i A") : '--' }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Actual End Time :</label>
                            <div class="col-lg-6">
                                {{ !empty($lastOrder->end_timing) && strpos($lastOrder->end_timing, '0000') !== 0 ? \Carbon\Carbon::parse($lastOrder->end_timing)->timezone($lastOrder->timezone ?? config('app.timezone'))->format("m/d/Y h:i A") : 'N/A' }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Renew Time :</label>
                            <div class="col-lg-6">
                                {{ !empty($lastOrder->created) && strpos($lastOrder->created, '0000') !== 0 ? \Carbon\Carbon::parse($lastOrder->created)->timezone($lastOrder->timezone ?? config('app.timezone'))->format("m/d/Y h:i A") : '--' }}
                            </div>
                        </div>

                        <legend>Fees</legend>
                        <div class="form-group">
                            <label class="col-lg-6">Initial Fee :</label>
                            <div class="col-lg-6">{{ $subOrders->initial_fee ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Initial Fee Tax :</label>
                            <div class="col-lg-6">{{ $subOrders->initial_fee_tax ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Initial Fee Discount :</label>
                            <div class="col-lg-6">{{ $subOrders->initial_discount ?? 0 }}</div>
                        </div>
                        <div class="form-group heading-divided-updown text-bold">
                            <label class="col-lg-6">Total Initial Fee:</label>
                            <div class="col-lg-6">
                                @php
                                    $initlafee = sprintf('%0.2f', ($subOrders->initial_fee ?? 0) + ($subOrders->initial_fee_tax ?? 0));
                                    $calcualted[3] = $initlafee;
                                @endphp
                                {{ $initlafee }}
                            </div>
                        </div>

                        <!-- Rent -->
                        <div class="form-group">
                            <label class="col-lg-6">Usage :</label>
                            <div class="col-lg-6">
                                {{ ($subOrders->rent ?? 0) + ($subOrders->discount ?? 0) }}
                                @php$calcualted[2]=$subOrders->rent ?? 0; @endphp
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Discount :</label>
                            <div class="col-lg-6">{{ $subOrders->discount ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Extra Mileage Fee :</label>
                            <div class="col-lg-6">
                                {{ $subOrders->extra_mileage_fee ?? 0 }}
                                @php$calcualted[16]=$subOrders->extra_mileage_fee ?? 0; @endphp
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">DIA Fee :</label>
                            <div class="col-lg-6">
                                {{ $subOrders->dia_fee ?? 0 }}
                            </div>
                        </div>
                        <div class="form-group heading-divided-updown text-bold">
                            <label class="col-lg-6">Total Usage:</label>
                            <div class="col-lg-5">
                                @php$rent = ($subOrders->rent ?? 0) + ($subOrders->extra_mileage_fee ?? 0) + ($subOrders->dia_fee ?? 0) + ($subOrders->initial_fee ?? 0); @endphp
                                {{ $rent }}
                            </div>
                            <div class="col-lg-1">*A</div>
                        </div>
                        <!-- TAX -->
                        <div class="form-group">
                            <label class="col-lg-6">Tax :</label>
                            <div class="col-lg-5">
                                @php$tax = ($subOrders->tax ?? 0) + ($subOrders->initial_fee_tax ?? 0); @endphp
                                {{ $tax }}
                            </div>
                            <div class="col-lg-1 text-bold">*B</div>
                        </div>
                        <div class="form-group heading-divided-updown text-bold">
                            <label class="col-lg-6">Total Usage With Tax:</label>
                            <div class="col-lg-4">{{ sprintf('%0.2f', ($rent + $tax)) }}</div>
                            <div class="col-lg-2">*(A+B)</div>
                        </div>

                        <legend>Insurance</legend>
                        <div class="form-group">
                            <label class="col-lg-6">Insurance Payer :</label>
                            <div class="col-lg-6">{{ $insurance_payer }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Insurance Amount :</label>
                            <div class="col-lg-6">
                                {{ $subOrders->insurance_amt ?? 0 }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">DIA Ins Add On Fee :</label>
                            <div class="col-lg-6">{{ $subOrders->dia_insu ?? 0 }}</div>
                        </div>
                        <div class="form-group heading-divided-updown text-bold">
                            <label class="col-lg-6">Total Insurance:</label>
                            <div class="col-lg-6">
                                @php
                                $insufee = sprintf('%0.2f', ($subOrders->insurance_amt ?? 0) + ($subOrders->dia_insu ?? 0));
                                $calcualted[4]=$insufee; 
                                @endphp
                                {{ $insufee }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Violations/Deductions:</label>
                            <div class="col-lg-6">
                                @php
                                $tolls = sprintf('%0.2f', ($subOrders->toll ?? 0) + ($subOrders->pending_toll ?? 0) + ($subOrders->lateness_fee ?? 0) + ($subOrders->damage_fee ?? 0) + ($subOrders->uncleanness_fee ?? 0));
                                $calcualted[6]=($subOrders->toll ?? 0) + ($subOrders->pending_toll ?? 0);
                                $calcualted[19]=$subOrders->lateness_fee ?? 0;
                                @endphp
                                {{ $tolls }}
                            </div>
                        </div>
                        <div class="form-group heading-divided-updown text-bold">
                            <label class="col-lg-6">Total Due:</label>
                            <div class="col-lg-6">
                                @php$totalDues = sprintf('%0.2f', ($tolls + $insufee + $rent + $tax)); @endphp
                                {{ $totalDues }}
                            </div>
                        </div>

                        <legend>Payments</legend>
                        @if (!empty($payments))
                            @php
                                $paymentTypeValue= $commonService->getPayoutTypeValue(1);
                            @endphp
                            <div class="form-group">
                                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table  table-responsive">
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
                                        @foreach ($payments as $key => $records)
                                            <tr>
                                                <th colspan="6" class="text-center text-bold">
                                                    {{ $paymentTypeValue[$key] ?? 'N/A' }}
                                                </th>
                                            </tr>
                                            @php$txnamount=$txnrent=$txntax=$txndia_fee=0; @endphp
                                            @foreach ($records as $payment)
                                                <tr>
                                                    <td class="text-center">
                                                        {{ $payment['id'] }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $payment['amount'] }} 
                                                        @php$txnamount +=$payment['amount']; @endphp
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $payment['rent'] ?? 0 }} @php$txnrent +=($payment['rent'] ?? 0); @endphp
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $payment['tax'] }} @php$txntax +=$payment['tax']; @endphp
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $payment['dia_fee'] }} @php$txndia_fee +=$payment['dia_fee']; @endphp
                                                    </td>
                                                    <td class="text-center">
                                                        {{ !empty($payment['charged_at']) && strpos($payment['charged_at'], '0000') !== 0 ? \Carbon\Carbon::parse($payment['charged_at'])->timezone(config('app.timezone'))->format("m/d/Y h:i A") : '--' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td class="text-center text-bold">Total </td>
                                                <td class="text-center text-bold">{{ $txnamount }}</td>
                                                <td class="text-center text-bold">{{ $txnrent }}</td>
                                                <td class="text-center text-bold">{{ $txntax }}</td>
                                                <td class="text-center text-bold">{{ $txndia_fee }}</td>
                                                <td><span class="text-warning">{{ $calcualted[$key] ?? '' }}</span></td>
                                            </tr>
                                        @endforeach
                                    <tbody>
                                </table>
                            </div>
                        @endif

                        <div class="form-group heading-divided-updown text-bold">
                            <label class="col-lg-6">Total Paid Amount :</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $totalGrandPaid) }}</div>
                        </div>
                        <div class="form-group heading-divided-updown text-bold">
                            <label class="col-lg-6">Driver Bad Debt :</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', ($totalDues - $totalGrandPaid)) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Start Ododmeter :</label>
                            <div class="col-lg-6">{{ $csorder->start_odometer }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">End Ododmeter :</label>
                            <div class="col-lg-6">{{ $lastOrder->end_odometer }}</div>
                        </div>
                        <div class="form-group heading-divided-updown text-bold">
                            <label class="col-lg-6">Total Distance :</label>
                            <div class="col-lg-6">
                                {{ $lastOrder->end_odometer > 0 ? $lastOrder->end_odometer - $csorder->start_odometer : 'N/A' }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Details :</label>
                            <div class="col-lg-6">
                                {{ $csorder->details }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Extention Request Date :</label>
                            <div class="col-lg-6"></div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <legend>Program & Fee Allocations</legend>
                        <div class="form-group">
                            <label class="col-lg-6">Vehicle Cost</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', ($OrderDepositRule->msrp ?? 0) - ($OrderDepositRule->initial_fee ?? 0)) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Vehicle Selling Price</label>
                            <div class="col-lg-6">{{ $OrderDepositRule->msrp ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Vehicle Listing Price</label>
                            <div class="col-lg-6">{{ $OrderDepositRule->premium_msrp ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Write Down Allocation %</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['write_down_allocation'] ?? 0) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Finance Cost %</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['finance_allocation'] ?? 0) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Maintenance Allocation %</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['maintenance_allocation'] ?? 0) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Disposition Fee</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['disposition_fee'] ?? 0) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Program Length</label>
                            <div class="col-lg-6">{{ $OrderDepositRule->num_of_days ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Total Program Cost</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['total_program_cost'] ?? 0) }}</div>
                        </div>
                        <legend>Program Breakdown</legend>
                        <div class="form-group">
                            <label class="col-lg-6">Total Write Down Allocation In Program</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', (($calculation['write_down_allocation'] ?? 0) * ($calculation['total_program_cost'] ?? 0) / 100)) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Total Finance Cost In Program</label>
                            <div class="col-lg-6">
                                {{ sprintf('%0.2f', (($calculation['finance_allocation'] ?? 0) * ($calculation['total_program_cost'] ?? 0) / 100)) }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Total Maintnenance Cost In Program</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', (($calculation['maintenance_allocation'] ?? 0) * ($calculation['total_program_cost'] ?? 0) / 100)) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Total DIA Fee In Program</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', (($calculation['total_program_fee_with_dia'] ?? 0) - ($calculation['total_program_fee_without_dia'] ?? 0))) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Total Disposition Fee In Program</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['disposition_fee'] ?? 0) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Total Program Cost</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['total_program_cost'] ?? 0) }}</div>
                        </div>
                        <legend>Rates</legend>
                        <div class="form-group">
                            <label class="col-lg-6">Initial Fee</label>
                            <div class="col-lg-6">{{ $OrderDepositRule->initial_fee ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Base Usage Rate</label>
                            <div class="col-lg-6">{{ $OrderDepositRule->base_rent ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Included Distance</label>
                            <div class="col-lg-6">{{ $OrderDepositRule->miles ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Extra Usage Rate</label>
                            <div class="col-lg-6">{{ $OrderDepositRule->emf_rate ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Daily Program Distance</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $OrderDepositRule->miles ?? 0) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Total Daily Rate</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $OrderDepositRule->rental ?? 0) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Weekly Rate</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', ($OrderDepositRule->rental ?? 0) * 7) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Monthly Rate</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', ($OrderDepositRule->rental ?? 0) * 365 / 12) }}</div>
                        </div>
                        <legend>Usage Payment Breakdown</legend>
                        <div class="form-group">
                            <label class="col-lg-6">Write Down Allocation</label>
                            <div class="col-lg-6">
                                @php$writedownallocation = (($calculation['write_down_allocation'] ?? 0) * $downpaymentPaid / 100); @endphp
                                {{ sprintf('%0.2f', $writedownallocation) }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Finance Allocation</label>
                            <div class="col-lg-6">
                                @php$financeallocation = (($calculation['finance_allocation'] ?? 0) * $downpaymentPaid / 100); @endphp
                                {{ sprintf('%0.2f', $financeallocation) }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Maintnenace Allocation</label>
                            <div class="col-lg-6">
                                @php$maintenanceallocation = (($calculation['maintenance_allocation'] ?? 0) * $downpaymentPaid / 100); @endphp
                                {{ sprintf('%0.2f', $maintenanceallocation) }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">Disposition Fee Allocation</label>
                            <div class="col-lg-6">
                                @php$dispositionfee = (($calculation['disposition_fee'] ?? 0) * $downpaymentPaid / 100); @endphp
                                {{ sprintf('%0.2f', $dispositionfee) }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6">DIA Fee</label>
                            <div class="col-lg-6">{{ sprintf('%0.2f', $totalDiaFee) }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-6 text-bold">Total</label>
                            <div class="col-lg-6 text-bold">{{ sprintf('%0.2f', ($writedownallocation + $financeallocation + $maintenanceallocation + $dispositionfee + $totalDiaFee)) }}</div>
                        </div>
                    </div>
                    @if (!empty($Promo))
                        <div class="col-lg-6">
                            <div class="formgroup">
                                <legend class="text-semibold">Attached Promo Rule To Driver</legend>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-6 control-label">
                                    <strong>Code: </strong>{{ $Promo->promo }}
                                </div>
                                <div class="col-lg-6 control-label">
                                    <strong>Title: </strong>{{ $Promo->title }}
                                </div>
                            </div>
                        </div>
                    @endif
                    @if (!$extlogs)
                        <div class="col-lg-6">
                            <div class="formgroup">
                                <legend class="text-semibold">Extention Logs</legend>
                            </div>
                            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table  table-responsive">
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
                                    @foreach ($extlogs as $extlog)
                                        <tr>
                                            <td style="text-align:center;">
                                                {{ $Siblingbooking[$extlog->cs_order_id] ?? '' }}
                                            </td>
                                            <td style="text-align:center;">
                                                {{ !empty($extlog->ext_date) && strpos($extlog->ext_date, '0000') !== 0 ? \Carbon\Carbon::parse($extlog->ext_date)->timezone(session('default_timezone', config('app.timezone')))->format("m/d/Y h:i A") : '--' }}
                                            </td>
                                            <td style="text-align:center;">
                                                {{ $extlog->note ?? '-' }}
                                            </td>
                                            <td style="text-align:center;">
                                                {{ ($extlog->owner?->first_name ?? '') . ' ' . ($extlog->owner?->last_name ?? '') }}
                                            </td>
                                            <td style="text-align:center;">
                                                {{ !empty($extlog->created) && strpos($extlog->created, '0000') !== 0 ? \Carbon\Carbon::parse($extlog->created)->timezone(session('default_timezone', config('app.timezone')))->format("m/d/Y h:i A") : '--' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    @endif
                </form>
            @else
                Sorry no order found.
            @endif
        </div>
    </div>
</div>
