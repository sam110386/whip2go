@php
    $records ??= [];
    $limit ??= 50;
@endphp


@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit, 'position' => 'top'])

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Started On</th>
                <th style="text-align:center;">Next On</th>
                <th style="text-align:center;">Dealer</th>
                <th style="text-align:center;"># of Units</th>
                <th style="text-align:center;">Subtotal Amt</th>
                <th style="text-align:center;">Amt</th>
                <th style="text-align:center;">Txn #</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ data_get($list, 'id') }}
                    </td>
                    <td style="text-align:center;">
                        @if (data_get($list, 'status') == 0)
                            Inactive
                        @elseif (data_get($list, 'status') == 1)
                            Active
                        @elseif (data_get($list, 'status') == 2)
                            Canceled
                        @endif

                        @if (data_get($list, 'status') == 1)
                            <img src="/img/green2.jpg" alt="Status" title="Status">
                        @else
                            <img src="/img/red3.jpg" alt="Status" title="Status">
                        @endif
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'created') != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse(data_get($list, 'created'))->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'next_on') != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse(data_get($list, 'next_on'))->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'owner.first_name') }} {{ data_get($list, 'owner.last_name') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'units') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'upfront_amt') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'amt') }}
                    </td>
                    <td style="text-align:center;">
                        {{ data_get($list, 'txn_id') }}
                    </td>
                    <td style="text-align:center;">
                        <a href="/admin/telematics_sub_devices/index/{{ base64_encode(data_get($list, 'id')) }}"
                            title="Manage Attached Devices">
                            <i class="icon-cabinet"></i>
                        </a>
                        <a href="javascript:void(0)" title="Payments"
                            onclick="openPayments('{{ base64_encode(data_get($list, 'id')) }}')">
                            <i class="icon-coin-dollar"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit])