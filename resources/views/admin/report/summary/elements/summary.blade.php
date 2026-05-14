@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50, 'position' => 'top'])
@endif

<style type="text/css">
    .fixed_header tbody {
        display: block;
        overflow: auto;
    }

    .fixed_header thead tr {
        display: block;
        cursor: pointer;
    }

    .fixed_header thead tr th,
    .fixed_header tbody tr td {
        width: 100%;
        padding: 5px;
        min-width: 100px;
        max-width: 100px;
    }
</style>

<div class="panel-flat table-responsive" style="overflow-x: scroll;width: 100%;">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', [
                    'columns' => [
                        ['title' => '#', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Start','style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'End', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Rent', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'EMF', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'DIA FEE', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Tax', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Lateness', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Total Rent', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Rental Rev. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Past Revenue', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Deferred Rev.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Total Revenue', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Collected (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Rent Wallet Refund', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Net Collected (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Already Collected', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Subsequent Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Total Collected', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Uncollected', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Insu. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Insu. Coll. (Mon)', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Insu. Wallet Ref.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Net Insu. Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Past Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Coll. Past Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Deferred Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Coll. Def. Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Total Insu.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Total Insu. Coll.', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Insu. Uncollected', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Current Payout Owed', 'style' => 'text-align:center;','sortable' => false],
                        ['title' => 'Past Payout Owed', 'style' => 'text-align:center;','sortable' => false],
                        ['title' => 'Differ Payout Owed', 'style' => 'text-align:center;','sortable' => false],
                        ['title' => 'Total Payout Owed', 'style' => 'text-align:center;','sortable' => false],
                        ['title' => 'Paid out in Month', 'style' => 'text-align:center;','sortable' => false],
                        ['title' => 'Strip Fee', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Net Paid out (Mon)', 'style' => 'text-align:center;','sortable' => false],
                        ['title' => 'Paid out (Differ)', 'style' => 'text-align:center;','sortable' => false],
                        ['title' => 'Total Paid out', 'style' => 'text-align:center;', 'sortable' => false],
                        ['title' => 'Dealer Owed', 'style' => 'text-align:center;', 'sortable' => false],
                    ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
            @php
                $total = sprintf(
                    '%0.2f',
                    ($list->initial_fee ?? 0) 
                    + ($list->rent ?? 0) 
                    + ($list->extra_mileage_fee ?? 0) 
                    + ($list->dia_fee ?? 0) 
                    + ($list->tax ?? 0) 
                    + ($list->lateness_fee ?? 0)
                    + ($list->past_m_initial_fee ?? 0) 
                    + ($list->past_m_rent ?? 0) 
                    + ($list->past_m_emf ?? 0) 
                    + ($list->past_m_dia_fee ?? 0) 
                    + ($list->past_m_tax ?? 0) 
                    + ($list->past_m_lateness_fee ?? 0)
                    + ($list->differ_m_initial_fee ?? 0) 
                    + ($list->differ_m_rent ?? 0) 
                    + ($list->differ_m_emf ?? 0) 
                    + ($list->differ_m_dia_fee ?? 0) 
                    + ($list->differ_m_tax ?? 0) 
                    + ($list->differ_m_lateness_fee ?? 0)
                );
                $Revtotal = sprintf(
                    '%0.2f',
                    ($list->initial_fee ?? 0) 
                    + ($list->rent ?? 0) 
                    + ($list->extra_mileage_fee ?? 0) 
                    + ($list->dia_fee ?? 0) 
                    + ($list->tax ?? 0) 
                    + ($list->lateness_fee ?? 0)
                );
                $pasttotal = sprintf(
                    '%0.2f',
                    ($list->past_m_initial_fee ?? 0) 
                    + ($list->past_m_rent ?? 0) 
                    + ($list->past_m_emf ?? 0) 
                    + ($list->past_m_dia_fee ?? 0) 
                    + ($list->past_m_tax ?? 0) 
                    + ($list->past_m_lateness_fee ?? 0)
                );
                $Diffetotal = sprintf(
                    '%0.2f',
                    ($list->differ_m_initial_fee ?? 0) 
                    + ($list->differ_m_rent ?? 0) 
                    + ($list->differ_m_emf ?? 0) 
                    + ($list->differ_m_dia_fee ?? 0) 
                    + ($list->differ_m_tax ?? 0) 
                    + ($list->differ_m_lateness_fee ?? 0)
                );

                $total_collected = $list->total_collected ?? 0;
                $past_m_total_collected = $list->past_m_total_collected ?? 0;
                $differ_m_total_collected = $list->differ_m_total_collected ?? 0;
                $totalinsu = sprintf('%0.2f', ($list->insurance_amt ?? 0) + ($list->dia_insu ?? 0));
                $pastinsu = ($list->past_m_dia_insu ?? 0) + ($list->past_m_dia_insurance_amt ?? 0);
                $differinsu = ($list->differ_m_dia_insu ?? 0) + ($list->differ_m_dia_insurance_amt ?? 0);
                $collectedinsu = sprintf(
                    '%0.2f',
                    (($list->emfinsurance ?? 0) + ($list->insurance ?? 0)) 
                    + (($list->past_m_insurance ?? 0) + ($list->past_m_emfinsurance ?? 0))
                    + (($list->differ_m_insurance ?? 0) + ($list->differ_m_emfinsurance ?? 0))
                );
            @endphp

            <tr id="{{ $list->id}}">
                <td style="text-align:center;">
                    {{ $list->increment_id }}
                </td>
                <td style="text-align:center;">
                    {{ !empty($list->start_datetime) ? \Carbon\Carbon::parse($list->start_datetime)->format('Y-m-d h:i A') : '--' }}
                </td>
                <td style="text-align:center;">
                    {{ !empty($list->end_datetime) ? \Carbon\Carbon::parse($list->end_datetime)->format('Y-m-d h:i A') : '--' }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->rent ?? 0) + ($list->initial_fee ?? 0) + ($list->past_m_rent ?? 0) + ($list->past_m_initial_fee ?? 0) + ($list->differ_m_rent ?? 0) + ($list->differ_m_initial_fee ?? 0) }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->extra_mileage_fee ?? 0) + ($list->past_m_emf ?? 0) + ($list->differ_m_emf ?? 0) }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->dia_fee ?? 0) + ($list->past_m_dia_fee ?? 0) + ($list->differ_m_dia_fee ?? 0) }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->tax ?? 0) + ($list->past_m_tax ?? 0) + ($list->differ_m_tax ?? 0) }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->lateness_fee ?? 0) + ($list->past_m_lateness_fee ?? 0) + ($list->differ_m_lateness_fee ?? 0) }}
                </td>
                <td style="text-align:center;">
                    {{ $total }}
                </td>
                <td style="text-align:center;">
                    {{ $Revtotal }}
                </td>
                <td style="text-align:center;">
                    {{ $pasttotal }}
                </td>
                <td style="text-align:center;">
                    {{ $Diffetotal }}
                </td>
                <td style="text-align:center;">
                    {{ sprintf('%0.2f', ((float) $Revtotal + (float) $Diffetotal + (float) $pasttotal)) }}
                </td>
                <td style="text-align:center;">
                    {{ $total_collected }}
                </td>
                <td style="text-align:center;">
                    {{ $list->rent_wallet_refund ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ sprintf('%0.2f', ($total_collected - ($list->rent_wallet_refund ?? 0))) }}
                </td>
                <td style="text-align:center;">
                    {{ $past_m_total_collected }}
                </td>
                <td style="text-align:center;">
                    {{ $differ_m_total_collected }}
                </td>
                <td style="text-align:center;">
                    {{ sprintf('%0.2f', ($total_collected + $past_m_total_collected + $differ_m_total_collected)) }}
                </td>
                <td style="text-align:center;">
                    {{ sprintf('%0.2f', ((float) $total - ($total_collected + $past_m_total_collected))) }}
                </td>
                <td style="text-align:center;">
                    {{ $totalinsu }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->emfinsurance ?? 0) + ($list->insurance ?? 0) }}
                </td>
                <td style="text-align:center;">
                    {{ $list->insu_wallet_refund ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ sprintf('%0.2f', (($list->emfinsurance ?? 0) + ($list->insurance ?? 0) - ($list->insu_wallet_refund ?? 0))) }}
                </td>
                <td style="text-align:center;">
                    {{ $pastinsu }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->past_m_insurance ?? 0) + ($list->past_m_emfinsurance ?? 0) }}
                </td>
                <td style="text-align:center;">
                    {{ $differinsu }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->differ_m_insurance ?? 0) + ($list->differ_m_emfinsurance ?? 0) }}
                </td>
                <td style="text-align:center;">
                    {{ ($totalinsu + $pastinsu + $differinsu) }}
                </td>
                <td style="text-align:center;">
                    {{ $collectedinsu }}
                </td>
                <td style="text-align:center;">
                    {{ sprintf('%0.2f', (($totalinsu + $pastinsu + $differinsu) - (float) $collectedinsu)) }}
                </td>
                <td style="text-align:center;">
                    {{ $list->dealer_payout ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ $list->past_m_payout ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ $list->differ_m_dealer_payout ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ $list->total_payout ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ $list->paid_payout ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ ($list->net_paid_payout ?? 0) > 0 ? sprintf('%0.2f', (($list->paid_payout ?? 0) - ($list->net_paid_payout ?? 0))) : 0 }}
                </td>
                <td style="text-align:center;">
                    {{ $list->net_paid_payout ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ $list->differ_paid_payout ?? '' }}
                </td>
                <td style="text-align:center;">
                    {{ sprintf('%0.2f', (($list->differ_paid_payout ?? 0) + ($list->paid_payout ?? 0))) }}
                </td>
                <td style="text-align:center;">
                    {{ sprintf('%0.2f', (($list->differ_paid_payout ?? 0) + ($list->paid_payout ?? 0) - ($list->total_payout ?? 0))) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
