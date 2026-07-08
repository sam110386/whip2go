@php
    $paymentTypeValue ??= [];
    $lists ??= [];
@endphp

@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50, 'position' => 'top'])
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;"> # </th>
                <th style="text-align:center;"> Start</th>
                <th style="text-align:center;"> End</th>
                <th style="text-align:center;"> Amount</th>
                <th style="text-align:center;"> Type
                <th style="text-align:center;"> Transaction #</th>
                <th style="text-align:center;"> Created (UTC)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ data_get($list, 'csOrder.increment_id', '--') }}
                    </td>
                    <td style="text-align:center;">
                        @if(!empty(data_get($list, 'csOrder', '')))
                            @php
                                $s = data_get($list, 'csOrder.start_datetime', null);
                                $tz = data_get($list, 'csOrder.timezone', config('app.timezone', 'UTC'));
                                echo $s ? \Carbon\Carbon::parse($s)->timezone($tz)->format('Y-m-d h:i A') : '--';
                            @endphp
                        @else
                            --
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if(!empty(data_get($list, 'csOrder', '')))
                            @php
                                $e = data_get($list, 'csOrder.end_datetime', null);
                                $tz = data_get($list, 'csOrder.timezone', config('app.timezone', 'UTC'));
                                echo $e ? \Carbon\Carbon::parse($e)->timezone($tz)->format('Y-m-d h:i A') : '--';
                            @endphp
                        @else
                            --
                        @endif
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'amount', '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ $paymentTypeValue[data_get($list, 'type', '')] ?? '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'transaction_id', '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'created', '') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif