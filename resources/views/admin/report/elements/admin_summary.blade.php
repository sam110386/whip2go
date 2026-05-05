@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
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
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'field' => 'increment_id', 'style' => 'text-align:center;'],
                    ['title' => 'Start', 'field' => 'start_datetime', 'style' => 'text-align:center;'],
                    ['title' => 'End', 'field' => 'end_datetime', 'style' => 'text-align:center;'],
                    ['title' => 'Rent', 'field' => 'rent', 'style' => 'text-align:center;'],
                    ['title' => 'EMF', 'field' => 'extra_mileage_fee', 'style' => 'text-align:center;'],
                    ['title' => 'DIA FEE', 'field' => 'dia_fee', 'style' => 'text-align:center;'],
                    ['title' => 'Tax', 'field' => 'tax', 'style' => 'text-align:center;'],
                    ['title' => 'Lateness', 'field' => 'lateness_fee', 'style' => 'text-align:center;'],
                    ['title' => 'Total Rent', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Rental Rev. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Past Revenue', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Deferred Rev.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Revenue', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Collected (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Rent Wallet Refund', 'field' => 'rent_wallet_refund', 'style' => 'text-align:center;'],
                    ['title' => 'Net Collected (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Already Collected', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Subsequent Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Collected', 'field' => 'total_collected', 'style' => 'text-align:center;'],
                    ['title' => 'Uncollected', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Insu. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Insu. Coll. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Insu. Wallet Ref.', 'field' => 'insu_wallet_refund', 'style' => 'text-align:center;'],
                    ['title' => 'Net Insu. Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Past Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Coll. Past Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Deferred Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Coll. Def. Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Insu. Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Insu. Uncollected', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Current Payout Owed', 'field' => 'dealer_payout', 'style' => 'text-align:center;'],
                    ['title' => 'Past Payout Owed', 'field' => 'past_m_payout', 'style' => 'text-align:center;'],
                    ['title' => 'Differ Payout Owed', 'field' => 'differ_m_dealer_payout', 'style' => 'text-align:center;'],
                    ['title' => 'Total Payout Owed', 'field' => 'total_payout', 'style' => 'text-align:center;'],
                    ['title' => 'Paid out in Month', 'field' => 'paid_payout', 'style' => 'text-align:center;'],
                    ['title' => 'Strip Fee', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Net Paid out (Mon)', 'field' => 'net_paid_payout', 'style' => 'text-align:center;'],
                    ['title' => 'Paid out (Differ)', 'field' => 'differ_paid_payout', 'style' => 'text-align:center;'],
                    ['title' => 'Total Paid out', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Dealer Owed', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
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
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
