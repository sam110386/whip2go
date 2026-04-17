@php
    /** Controller should pass $paymentTypeValue or resolve via Common parity: app(...)->getPayoutTypeValue(1) */
    $paymentTypeValue = $paymentTypeValue ?? [];
@endphp
<div class="panel">
    <div class="panel-body">
        <div class="row">
            @if(!empty($csorder))
            <form class="form-horizontal">
                @csrf
                <div class="col-lg-6">
                    <legend>Conversion Equity Details</legend>
                    <div class="form-group">
                        <label class="col-lg-6 ">Customer :</label>
                        <div class="col-lg-6">{{ ($csorder['User']['first_name'] ?? '') . ' ' . ($csorder['User']['last_name'] ?? '') }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Vehicle :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['vehicle_name'] ?? '' }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Booking# :</label>
                        <div class="col-lg-6">
                            {{ $csorder['CsOrder']['increment_id'] ?? '' }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-6 ">Start Date Time :</label>
                        <div class="col-lg-6">
                            @php
                                $sd = $csorder['CsOrder']['start_datetime'] ?? null;
                                $stz = $csorder['CsOrder']['timezone'] ?? config('app.timezone');
                                echo $sd ? \Carbon\Carbon::parse($sd)->timezone($stz)->format('m/d/Y h:i A') : '';
                            @endphp
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-6 ">End Date Time :</label>
                        <div class="col-lg-6">
                            @php
                                $ed = $lastOrder['CsOrder']['end_datetime'] ?? null;
                                $etz = $lastOrder['CsOrder']['timezone'] ?? config('app.timezone');
                                echo $ed ? \Carbon\Carbon::parse($ed)->timezone($etz)->format('m/d/Y h:i A') : '';
                            @endphp
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Actual End Time :</label>
                        <div class="col-lg-6">
                            @if(!empty($lastOrder['CsOrder']['end_timing']))
                                @php
                                    $et = $lastOrder['CsOrder']['end_timing'];
                                    $etz2 = $lastOrder['CsOrder']['timezone'] ?? config('app.timezone');
                                    echo \Carbon\Carbon::parse($et)->timezone($etz2)->format('m/d/Y h:i A');
                                @endphp
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Renew Time :</label>
                        <div class="col-lg-6">
                            @php
                                $cr = $lastOrder['CsOrder']['created'] ?? null;
                                $ctz = $lastOrder['CsOrder']['timezone'] ?? config('app.timezone');
                                echo $cr ? \Carbon\Carbon::parse($cr)->timezone($ctz)->format('m/d/Y h:i A') : '';
                            @endphp
                        </div>
                    </div>
                    <legend>Fees</legend>
                    <div class="form-group">
                        <label class="col-lg-6 ">Initial Fee :</label>
                        <div class="col-lg-6">{{ $subOrders[0]['initial_fee'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Initial Fee Tax :</label>
                        <div class="col-lg-6">{{ $subOrders[0]['initial_fee_tax'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Initial Fee Discount :</label>
                        <div class="col-lg-6">{{ $subOrders[0]['initial_discount'] ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6 ">Total Initial Fee:</label>
                        <div class="col-lg-6">@php $initlafee = sprintf('%0.2f', (($subOrders[0]['initial_fee'] ?? 0) + ($subOrders[0]['initial_fee_tax'] ?? 0))); @endphp{{ $initlafee }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Usage :</label>
                        <div class="col-lg-6">{{ ($subOrders[0]['rent'] ?? 0) + ($subOrders[0]['discount'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Discount :</label>
                        <div class="col-lg-6">{{ $subOrders[0]['discount'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Extra Mileage Fee :</label>
                        <div class="col-lg-6">{{ $subOrders[0]['extra_mileage_fee'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">DIA Fee :</label>
                        <div class="col-lg-6">{{ $subOrders[0]['dia_fee'] ?? '' }}</div>
                    </div>

                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6 ">Total Usage:</label>
                        <div class="col-lg-6">@php $rent = (($subOrders[0]['rent'] ?? 0) + ($subOrders[0]['extra_mileage_fee'] ?? 0) + ($subOrders[0]['dia_fee'] ?? 0)); @endphp{{ $rent }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Tax :</label>
                        <div class="col-lg-6">@php $tax = $subOrders[0]['tax'] ?? 0; @endphp{{ $tax }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6 ">Total Usage with Tax:</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', ($rent + $tax)) }}</div>
                    </div>
                    <legend>Insurance</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Insurance Payer :</label>
                        <div class="col-lg-6">{{ $insurance_payer ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Insurance Amount :</label>
                        <div class="col-lg-6">{{ $subOrders[0]['insurance_amt'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">DIA Ins Add On fee :</label>
                        <div class="col-lg-6">{{ $subOrders[0]['dia_insu'] ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6 ">Total Insurance:</label>
                        <div class="col-lg-6">@php $insufee = sprintf('%0.2f', (($subOrders[0]['insurance_amt'] ?? 0) + ($subOrders[0]['dia_insu'] ?? 0))); @endphp{{ $insufee }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Violations (Tolls/Misc):</label>
                        <div class="col-lg-6">@php $tolls = sprintf('%0.2f', (($subOrders[0]['toll'] ?? 0) + ($subOrders[0]['pending_toll'] ?? 0) + ($subOrders[0]['lateness_fee'] ?? 0) + ($subOrders[0]['damage_fee'] ?? 0) + ($subOrders[0]['uncleanness_fee'] ?? 0))); @endphp{{ $tolls }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6 ">Grand Total:</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', ((float) $tolls + (float) $insufee + (float) $rent + (float) $tax + (float) $initlafee)) }}</div>
                    </div>
                    <legend>Payments</legend>
                    @if (!empty($payments))
                    <div class="form-group">
                        <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table  table-responsive">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Amount</th>
                                    <th>Usage</th>
                                    <th>Tax</th>
                                    <th>Dia Fee</th>
                                    <th>Type</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment['CsOrderPayment']['transaction_id'] ?? '' }}</td>
                                        <td>{{ $payment['CsOrderPayment']['amount'] ?? '' }}</td>
                                        <td>{{ $payment['CsOrderPayment']['rent'] ?? '' }}</td>
                                        <td>{{ $payment['CsOrderPayment']['tax'] ?? '' }}</td>
                                        <td>{{ $payment['CsOrderPayment']['dia_fee'] ?? '' }}</td>
                                        <td>{{ $paymentTypeValue[$payment['CsOrderPayment']['type']] ?? '' }}</td>
                                        <td>{{ $payment['CsOrderPayment']['charged_at'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6 ">Total Paid Amount :</label>
                        <div class="col-lg-6">{{ $totalGrandPaid ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6 ">Driver Bad Debt :</label>
                        <div class="col-lg-6">{{ $lastOrder['CsOrder']['bad_debt'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Start Ododmeter :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['start_odometer'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">End Ododmeter :</label>
                        <div class="col-lg-6">{{ $lastOrder['CsOrder']['end_odometer'] ?? '' }}</div>
                    </div>
                    <div class="form-group heading-divided-updown text-bold">
                        <label class="col-lg-6 ">Total Miles :</label>
                        <div class="col-lg-6">{{ ($lastOrder['CsOrder']['end_odometer'] ?? 0) > 0 ? ($lastOrder['CsOrder']['end_odometer'] - ($csorder['CsOrder']['start_odometer'] ?? 0)) : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Details :</label>
                        <div class="col-lg-6">{{ $csorder['CsOrder']['details'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Extention Request Date :</label>
                        <div class="col-lg-6"></div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <legend>Program And Fee Allocations</legend>
                    <div class="form-group">
                        <label class="col-lg-6 ">Vehicle Cost</label>
                        <div class="col-lg-6">{{ ($OrderDepositRule['OrderDepositRule']['msrp'] ?? 0) - ($OrderDepositRule['OrderDepositRule']['initial_fee'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Vehicle Selling Price</label>
                        <div class="col-lg-6">{{ $OrderDepositRule['OrderDepositRule']['msrp'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Vehicle Listing Price</label>
                        <div class="col-lg-6">{{ $OrderDepositRule['OrderDepositRule']['premium_msrp'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Write Down Allocation %</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['write_down_allocation'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Finance Cost %</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['finance_allocation'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Maintenance Allocation monthly</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['maintenance_allocation'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Disposition Fee</label>
                        <div class="col-lg-6">{{ $calculation['disposition_fee'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Program Length</label>
                        <div class="col-lg-6">{{ $OrderDepositRule['OrderDepositRule']['num_of_days'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 ">Total Program Cost</label>
                        <div class="col-lg-6">{{ $calculation['total_program_cost'] ?? '' }}</div>
                    </div>
                    <legend>&nbsp;</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Total Write down allocation in program</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', (($calculation['write_down_allocation'] ?? 0) * ($calculation['total_program_cost'] ?? 0) / 100)) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Total Finance cost in program</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', (($calculation['finance_allocation'] ?? 0) * ($calculation['total_program_cost'] ?? 0) / 100)) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Total Maintnenance cost in program</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', (($calculation['maintenance_allocation'] ?? 0) * ($calculation['total_program_cost'] ?? 0) / 100)) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Total DIA fee in program</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', (($calculation['total_program_fee_with_dia'] ?? 0) - ($calculation['total_program_fee_without_dia'] ?? 0))) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Total Disposition fee in program</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['disposition_fee'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Total program cost</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $calculation['total_program_cost'] ?? 0) }}</div>
                    </div>
                    <legend>&nbsp;</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Initial Fee</label>
                        <div class="col-lg-6">{{ $OrderDepositRule['OrderDepositRule']['initial_fee'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Base Usage Rate</label>
                        <div class="col-lg-6">{{ $OrderDepositRule['OrderDepositRule']['base_rent'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Included Miles</label>
                        <div class="col-lg-6">{{ $OrderDepositRule['OrderDepositRule']['miles'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Extra Usage Rate</label>
                        <div class="col-lg-6">{{ $OrderDepositRule['OrderDepositRule']['emf_rate'] ?? '' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Daily Program Miles</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $OrderDepositRule['OrderDepositRule']['miles'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Total Daily Rate</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $OrderDepositRule['OrderDepositRule']['rental'] ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Weekly Rate</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', ($OrderDepositRule['OrderDepositRule']['rental'] ?? 0) * 7) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Monthly Rate</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', ($OrderDepositRule['OrderDepositRule']['rental'] ?? 0) * 365 / 12) }}</div>
                    </div>
                    <legend>Payment Breakdown of total Usage of cycle</legend>
                    <div class="form-group">
                        <label class="col-lg-6">Write Down Allocation</label>
                        <div class="col-lg-6">@php $writedownallocation = (($calculation['write_down_allocation'] ?? 0) * ($downpaymentPaid ?? 0) / 100); @endphp{{ sprintf('%0.2f', $writedownallocation) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Finance Allocation</label>
                        <div class="col-lg-6">@php $financeallocation = (($calculation['finance_allocation'] ?? 0) * ($downpaymentPaid ?? 0) / 100); @endphp{{ sprintf('%0.2f', $financeallocation) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Maintnenace Allocation</label>
                        <div class="col-lg-6">@php $maintenanceallocation = (($calculation['maintenance_allocation'] ?? 0) * ($downpaymentPaid ?? 0) / 100); @endphp{{ sprintf('%0.2f', $maintenanceallocation) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">Disposition Fee Allocation</label>
                        <div class="col-lg-6">@php $dispositionfee = (($calculation['disposition_fee'] ?? 0) * ($downpaymentPaid ?? 0) / 100); @endphp{{ sprintf('%0.2f', $dispositionfee) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6">DIA Fee</label>
                        <div class="col-lg-6">{{ sprintf('%0.2f', $totalDiaFee ?? 0) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-6 text-bold">Total</label>
                        <div class="col-lg-6 text-bold">{{ sprintf('%0.2f', ($writedownallocation + $financeallocation + $maintenanceallocation + $dispositionfee + ($totalDiaFee ?? 0))) }}</div>
                    </div>
                </div>
                @if (!empty($extlogs))
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
                                <th style="text-align:center;">Count Towards Ext.</th>
                                <th style="text-align:center;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($extlogs as $extlog)
                                <tr>
                                    <td style="text-align:center;">
                                        {{ $Siblingbooking[$extlog['OrderExtlog']['cs_order_id']] ?? '' }}
                                    </td>
                                    <td style="text-align:center;">
                                        @php
                                            $dzt = session('default_timezone', config('app.timezone'));
                                            $exd = $extlog['OrderExtlog']['ext_date'] ?? '';
                                            echo ($exd != '' && $exd != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($exd)->timezone($dzt)->format('m/d/Y h:i A') : '--';
                                        @endphp
                                    </td>
                                    <td style="text-align:center;">
                                        {{ $extlog['OrderExtlog']['note'] ?? '-' }}
                                    </td>
                                    <td style="text-align:center;">
                                        {{ ($extlog['Owner']['first_name'] ?? '') . ' ' . ($extlog['Owner']['last_name'] ?? '') }}
                                    </td>
                                    <td style="text-align:center;">
                                        {{ ($extlog['OrderExtlog']['admin_count'] ?? 0) == 0 ? 'Yes' : 'No' }}
                                    </td>
                                    <td style="text-align:center;">
                                        @php
                                            $dzt = session('default_timezone', config('app.timezone'));
                                            $crd = $extlog['OrderExtlog']['created'] ?? '';
                                            echo ($crd != '' && $crd != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($crd)->timezone($dzt)->format('m/d/Y h:i A') : '--';
                                        @endphp
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
