@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
<style type="text/css">
    .fixed_header tbody{
        display:block;
        overflow:auto;
        height:400px;
        width:100%;
     }
    .fixed_header thead tr{display:block; cursor: pointer;}
    .fixed_header thead tr th,.fixed_header tbody tr td{width: 100%;padding: 5px;min-width: 100px;max-width: 100px;}
</style>
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1"  border="0"  class="table fixed_header table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Start</th>
                <th style="text-align:center;">End</th>
                <th style="text-align:center;">Rent</th>
                <th style="text-align:center;">EMF</th>
                <th style="text-align:center;">DIA FEE</th>
                <th style="text-align:center;">Tax</th>
                <th style="text-align:center;">Lateness</th>
                <th style="text-align:center;">Total Rent</th>
                <th style="text-align:center;">Rental Revenue This Month</th>
                <th style="text-align:center;">Past Revenue</th>
                <th style="text-align:center;">Deferred Revenue</th>
                <th style="text-align:center;">Total Revenue</th>
                <th style="text-align:center;">Total Collected This Month</th>
                <th style="text-align:center;">Rent Wallet Refund</th>
                <th style="text-align:center;">Net Collected This Month</th>
                <th style="text-align:center;">Already Collected</th>
                <th style="text-align:center;">Subsequently Collected</th>
                <th style="text-align:center;">Total Collected</th>
                <th style="text-align:center;">Uncollected</th>
                <th style="text-align:center;">Insu. This Month</th>
                <th style="text-align:center;">Insu. Collected This Month</th>
                <th style="text-align:center;">Insu. Wallet Refund</th>
                <th style="text-align:center;">Net Insu. Collected This Month</th>
                <th style="text-align:center;">Past Insu.</th>
                <th style="text-align:center;">Collected Past Insu.</th>
                <th style="text-align:center;">Deferred Insu.</th>
                <th style="text-align:center;">Collected Deferred Insu.</th>
                <th style="text-align:center;">Total Insu.</th>
                <th style="text-align:center;">Total Insu. Collected</th>
                <th style="text-align:center;">Insu. Uncollected</th>
                <th style="text-align:center;">Current Payout Owed</th>
                <th style="text-align:center;">Past Payout Owed</th>
                <th style="text-align:center;">Differ Payout Owed</th>
                <th style="text-align:center;">Total Payout Owed</th>
                <th style="text-align:center;">Paid out in Month</th>
                <th style="text-align:center;">Strip Fee</th>
                <th style="text-align:center;">Net Paid out in Month</th>
                <th style="text-align:center;">Paid out in Differ Month</th>
                <th style="text-align:center;">Total Paid out</th>
                <th style="text-align:center;">Dealer Owed</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                @php
                    $s = $list['SummaryReport'];
                    $fmt = function ($dt) {
                        if (empty($dt)) {
                            return '';
                        }
                        try {
                            return \Carbon\Carbon::parse($dt)->format('Y-m-d h:i A');
                        } catch (\Throwable $e) {
                            return '';
                        }
                    };
                    $total = sprintf(
                        '%0.2f',
                        ($s['initial_fee'] ?? 0) + ($s['rent'] ?? 0) + ($s['extra_mileage_fee'] ?? 0) + ($s['dia_fee'] ?? 0) + ($s['tax'] ?? 0) + ($s['lateness_fee'] ?? 0)
                        + ($s['past_m_initial_fee'] ?? 0) + ($s['past_m_rent'] ?? 0) + ($s['past_m_emf'] ?? 0) + ($s['past_m_dia_fee'] ?? 0) + ($s['past_m_tax'] ?? 0) + ($s['past_m_lateness_fee'] ?? 0)
                        + ($s['differ_m_initial_fee'] ?? 0) + ($s['differ_m_rent'] ?? 0) + ($s['differ_m_emf'] ?? 0) + ($s['differ_m_dia_fee'] ?? 0) + ($s['differ_m_tax'] ?? 0) + ($s['differ_m_lateness_fee'] ?? 0)
                    );
                    $Revtotal = sprintf(
                        '%0.2f',
                        ($s['initial_fee'] ?? 0) + ($s['rent'] ?? 0) + ($s['extra_mileage_fee'] ?? 0) + ($s['dia_fee'] ?? 0) + ($s['tax'] ?? 0) + ($s['lateness_fee'] ?? 0)
                    );
                    $pasttotal = sprintf(
                        '%0.2f',
                        ($s['past_m_initial_fee'] ?? 0) + ($s['past_m_rent'] ?? 0) + ($s['past_m_emf'] ?? 0) + ($s['past_m_dia_fee'] ?? 0) + ($s['past_m_tax'] ?? 0) + ($s['past_m_lateness_fee'] ?? 0)
                    );
                    $Diffetotal = sprintf(
                        '%0.2f',
                        ($s['differ_m_initial_fee'] ?? 0) + ($s['differ_m_rent'] ?? 0) + ($s['differ_m_emf'] ?? 0) + ($s['differ_m_dia_fee'] ?? 0) + ($s['differ_m_tax'] ?? 0) + ($s['differ_m_lateness_fee'] ?? 0)
                    );
                    $total_collected = $s['total_collected'] ?? 0;
                    $past_m_total_collected = $s['past_m_total_collected'] ?? 0;
                    $differ_m_total_collected = $s['differ_m_total_collected'] ?? 0;
                    $totalinsu = sprintf('%0.2f', ($s['insurance_amt'] ?? 0) + ($s['dia_insu'] ?? 0));
                    $pastinsu = ($s['past_m_dia_insu'] ?? 0) + ($s['past_m_dia_insurance_amt'] ?? 0);
                    $differinsu = ($s['differ_m_dia_insu'] ?? 0) + ($s['differ_m_dia_insurance_amt'] ?? 0);
                    $collectedinsu = sprintf(
                        '%0.2f',
                        (($s['emfinsurance'] ?? 0) + ($s['insurance'] ?? 0)) + (($s['past_m_insurance'] ?? 0) + ($s['past_m_emfinsurance'] ?? 0)) + (($s['differ_m_insurance'] ?? 0) + ($s['differ_m_emfinsurance'] ?? 0))
                    );
                @endphp
                <tr id="{{ $s['id'] ?? '' }}">
                    <td style="text-align:center;">{{ $s['increment_id'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $fmt($s['start_datetime'] ?? null) }}</td>
                    <td style="text-align:center;">{{ $fmt($s['end_datetime'] ?? null) }}</td>
                    <td style="text-align:center;">{{ ($s['rent'] ?? 0) + ($s['initial_fee'] ?? 0) + ($s['past_m_rent'] ?? 0) + ($s['past_m_initial_fee'] ?? 0) + ($s['differ_m_rent'] ?? 0) + ($s['differ_m_initial_fee'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ ($s['extra_mileage_fee'] ?? 0) + ($s['past_m_emf'] ?? 0) + ($s['differ_m_emf'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ ($s['dia_fee'] ?? 0) + ($s['past_m_dia_fee'] ?? 0) + ($s['differ_m_dia_fee'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ ($s['tax'] ?? 0) + ($s['past_m_tax'] ?? 0) + ($s['differ_m_tax'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ ($s['lateness_fee'] ?? 0) + ($s['past_m_lateness_fee'] ?? 0) + ($s['differ_m_lateness_fee'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ $total }}</td>
                    <td style="text-align:center;">{{ $Revtotal }}</td>
                    <td style="text-align:center;">{{ $pasttotal }}</td>
                    <td style="text-align:center;">{{ $Diffetotal }}</td>
                    <td style="text-align:center;">{{ sprintf('%0.2f', ((float) $Revtotal + (float) $Diffetotal + (float) $pasttotal)) }}</td>
                    <td style="text-align:center;">{{ $total_collected }}</td>
                    <td style="text-align:center;">{{ $s['rent_wallet_refund'] ?? '' }}</td>
                    <td style="text-align:center;">{{ sprintf('%0.2f', ($total_collected - ($s['rent_wallet_refund'] ?? 0))) }}</td>
                    <td style="text-align:center;">{{ $past_m_total_collected }}</td>
                    <td style="text-align:center;">{{ $differ_m_total_collected }}</td>
                    <td style="text-align:center;">{{ sprintf('%0.2f', ($total_collected + $past_m_total_collected + $differ_m_total_collected)) }}</td>
                    <td style="text-align:center;">{{ sprintf('%0.2f', ((float) $total - ($total_collected + $past_m_total_collected))) }}</td>
                    <td style="text-align:center;">{{ $totalinsu }}</td>
                    <td style="text-align:center;">{{ ($s['emfinsurance'] ?? 0) + ($s['insurance'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ $s['insu_wallet_refund'] ?? '' }}</td>
                    <td style="text-align:center;">{{ sprintf('%0.2f', (($s['emfinsurance'] ?? 0) + ($s['insurance'] ?? 0) - ($s['insu_wallet_refund'] ?? 0))) }}</td>
                    <td style="text-align:center;">{{ $pastinsu }}</td>
                    <td style="text-align:center;">{{ ($s['past_m_insurance'] ?? 0) + ($s['past_m_emfinsurance'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ $differinsu }}</td>
                    <td style="text-align:center;">{{ ($s['differ_m_insurance'] ?? 0) + ($s['differ_m_emfinsurance'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ ($totalinsu + $pastinsu + $differinsu) }}</td>
                    <td style="text-align:center;">{{ $collectedinsu }}</td>
                    <td style="text-align:center;">{{ sprintf('%0.2f', (($totalinsu + $pastinsu + $differinsu) - (float) $collectedinsu)) }}</td>
                    <td style="text-align:center;">{{ $s['dealer_payout'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $s['past_m_payout'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $s['differ_m_dealer_payout'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $s['total_payout'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $s['paid_payout'] ?? '' }}</td>
                    <td style="text-align:center;">{{ ($s['net_paid_payout'] ?? 0) > 0 ? sprintf('%0.2f', (($s['paid_payout'] ?? 0) - ($s['net_paid_payout'] ?? 0))) : 0 }}</td>
                    <td style="text-align:center;">{{ $s['net_paid_payout'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $s['differ_paid_payout'] ?? '' }}</td>
                    <td style="text-align:center;">{{ sprintf('%0.2f', (($s['differ_paid_payout'] ?? 0) + ($s['paid_payout'] ?? 0))) }}</td>
                    <td style="text-align:center;">{{ sprintf('%0.2f', (($s['differ_paid_payout'] ?? 0) + ($s['paid_payout'] ?? 0) - ($s['total_payout'] ?? 0))) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
