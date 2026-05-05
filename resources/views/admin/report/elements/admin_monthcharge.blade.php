@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'field' => 'increment_id', 'style' => 'text-align:center;'],
                    ['title' => 'Start', 'field' => 'start_datetime', 'style' => 'text-align:center;'],
                    ['title' => 'End', 'field' => 'end_datetime', 'style' => 'text-align:center;'],
                    ['title' => 'Amount', 'field' => 'amount', 'style' => 'text-align:center;'],
                    ['title' => 'Type', 'field' => 'type', 'style' => 'text-align:center;'],
                    ['title' => 'Transaction #', 'field' => 'transaction_id', 'style' => 'text-align:center;'],
                    ['title' => 'Created (UTC)', 'field' => 'created', 'style' => 'text-align:center;'],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ !empty($list['CsOrder']) ? ($list['CsOrder']['increment_id'] ?? '--') : '--' }}
                    </td>
                    <td style="text-align:center;">
                        @if(!empty($list['CsOrder']))
                            @php
                                $s = $list['CsOrder']['start_datetime'] ?? null;
                                $tz = $list['CsOrder']['timezone'] ?? config('app.timezone');
                                echo $s ? \Carbon\Carbon::parse($s)->timezone($tz)->format('Y-m-d h:i A') : '--';
                            @endphp
                        @else
                            --
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if(!empty($list['CsOrder']))
                            @php
                                $e = $list['CsOrder']['end_datetime'] ?? null;
                                $tz = $list['CsOrder']['timezone'] ?? config('app.timezone');
                                echo $e ? \Carbon\Carbon::parse($e)->timezone($tz)->format('Y-m-d h:i A') : '--';
                            @endphp
                        @else
                            --
                        @endif
                    </td>
                    <td style="text-align:center;">
                        {{ $list['CsPaymentLog']['amount'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ ($paymentTypeValue ?? [])[$list['CsPaymentLog']['type'] ?? ''] ?? '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['CsPaymentLog']['transaction_id'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['CsPaymentLog']['created'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
