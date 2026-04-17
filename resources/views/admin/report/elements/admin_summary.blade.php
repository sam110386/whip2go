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
                    ['field' => 'increment_id', 'title' => '#', 'style' => 'text-align:center;'],
                    ['field' => 'start_datetime', 'title' => 'Start', 'style' => 'text-align:center;'],
                    ['field' => 'end_datetime', 'title' => 'End', 'style' => 'text-align:center;'],
                    ['field' => 'rent', 'title' => 'Rent', 'style' => 'text-align:center;'],
                    ['field' => 'extra_mileage_fee', 'title' => 'EMF', 'style' => 'text-align:center;'],
                    ['field' => 'dia_fee', 'title' => 'DIA FEE', 'style' => 'text-align:center;'],
                    ['field' => 'tax', 'title' => 'Tax', 'style' => 'text-align:center;'],
                    ['field' => 'lateness_fee', 'title' => 'Lateness', 'style' => 'text-align:center;'],
                    ['field' => 'total_rent', 'title' => 'Total Rent', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'rental_rev_current', 'title' => 'Rental Rev. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'past_rev', 'title' => 'Past Revenue', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'deferred_rev', 'title' => 'Deferred Rev.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'total_rev', 'title' => 'Total Revenue', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'total_collected_current', 'title' => 'Collected (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'rent_wallet_refund', 'title' => 'Rent Wallet Refund', 'style' => 'text-align:center;'],
                    ['field' => 'net_collected_current', 'title' => 'Net Collected (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'already_collected', 'title' => 'Already Collected', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'subsequent_collected', 'title' => 'Subsequent Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'total_collected', 'title' => 'Total Collected', 'style' => 'text-align:center;'],
                    ['field' => 'uncollected', 'title' => 'Uncollected', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'insu_current', 'title' => 'Insu. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'insu_collected_current', 'title' => 'Insu. Coll. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'insu_wallet_refund', 'title' => 'Insu. Wallet Ref.', 'style' => 'text-align:center;'],
                    ['field' => 'net_insu_collected_current', 'title' => 'Net Insu. Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'past_insu', 'title' => 'Past Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'past_insu_collected', 'title' => 'Coll. Past Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'deferred_insu', 'title' => 'Deferred Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'deferred_insu_collected', 'title' => 'Coll. Def. Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'total_insu', 'title' => 'Total Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'total_insu_collected', 'title' => 'Total Insu. Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'insu_uncollected', 'title' => 'Insu. Uncollected', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'dealer_payout', 'title' => 'Current Payout Owed', 'style' => 'text-align:center;'],
                    ['field' => 'past_m_payout', 'title' => 'Past Payout Owed', 'style' => 'text-align:center;'],
                    ['field' => 'differ_m_dealer_payout', 'title' => 'Differ Payout Owed', 'style' => 'text-align:center;'],
                    ['field' => 'total_payout', 'title' => 'Total Payout Owed', 'style' => 'text-align:center;'],
                    ['field' => 'paid_payout', 'title' => 'Paid out in Month', 'style' => 'text-align:center;'],
                    ['field' => 'stripe_fee', 'title' => 'Strip Fee', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'net_paid_payout', 'title' => 'Net Paid out (Mon)', 'style' => 'text-align:center;'],
                    ['field' => 'differ_paid_payout', 'title' => 'Paid out (Differ)', 'style' => 'text-align:center;'],
                    ['field' => 'total_paid_payout', 'title' => 'Total Paid out', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'dealer_owed', 'title' => 'Dealer Owed', 'style' => 'text-align:center;', 'sortable' => false],
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
