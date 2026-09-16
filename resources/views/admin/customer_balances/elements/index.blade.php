@php
    $csUserBalances ??= collect();
    $balanceTypes ??= [];
    $limit ??= 50;
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $csUserBalances, 'limit' => $limit, 'position' => 'top'])

<form action="" method="GET" name="frm1" id="frm1">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <tr>
            <th valign="top" width="5%">#</th>
            <th valign="top">Name</th>
            <th valign="top">Type</th>
            <th valign="top">Charge on Driver</th>
            <th valign="top">Debit</th>
            <th valign="top">Balance</th>
            <th valign="top">Charge Type</th>
            <th valign="top">Last Processed</th>
            <th valign="top">Note</th>
            <th valign="top">Created</th>
            <th valign="top">Action</th>
        </tr>

        @foreach ($csUserBalances as $csUserBalance)
            <tr>
                <td valign="top">
                    {{ data_get($csUserBalance, 'id', '')  }}
                </td>
                <td valign="top">
                    {{ trim((data_get($csUserBalance, 'user.first_name', '')) . ' ' . (data_get($csUserBalance, 'user.last_name', ''))) }}
                </td>
                <td valign="top">
                    {{ data_get($balanceTypes, data_get($csUserBalance, 'type', ''), '') }}
                </td>
                <td valign="top">
                    {{ data_get($csUserBalance, 'credit', '') }}
                </td>
                <td valign="top">
                    {{ data_get($csUserBalance, 'debit', '') }}
                </td>
                <td valign="top">
                    {{ data_get($csUserBalance, 'balance', '') }}
                </td>
                <td valign="top">
                    <strong>Type:</strong>
                    {{ ucfirst(data_get($csUserBalance, 'chargetype', '')) }}
                    <br>
                    <strong>Installment Type:</strong>
                    {{ ucfirst(data_get($csUserBalance, 'installment_type', '')) }}
                    <br>
                    <strong>Installment:</strong>
                    {{ data_get($csUserBalance, 'installment', '') }}
                </td>
                <td valign="top">
                    {{ data_get($csUserBalance, 'last_processed', false) ? \Carbon\Carbon::parse(data_get($csUserBalance, 'last_processed'))->setTimezone(session('timezone'))->format('Y-m-d h:i A') : '' }}
                </td>
                <td valign="top">
                    {{ data_get($csUserBalance, 'note', '') }}
                </td>
                <td valign="top">
                    {{ data_get($csUserBalance, 'created', '') }}
                </td>
                <td valign="top">
                    @if (data_get($csUserBalance, 'status', null) === 1)
                        <a href="{{ url('admin/customer_balances/status/' . base64_encode(data_get($csUserBalance, 'id')) . '/0') }}"
                            onclick="return confirm('Are you sure to update this record?');">
                            <img src="/img/green2.jpg" alt="Status" title="Status">
                        </a>
                    @else
                        <a href="{{ url('admin/customer_balances/status/' . base64_encode(data_get($csUserBalance, 'id')) . '/1') }}"
                            onclick="return confirm('Are you sure to update this record?');">
                            <img src="/img/red3.jpg" alt="Status" title="Status">
                        </a>
                    @endif
                    &nbsp;
                    @if (
                            data_get($csUserBalance, 'status', null) === 1
                            && data_get($csUserBalance, 'chargetype', null) === 'subscription'
                        )
                        <a
                            href="{{ url('admin/customer_balances/addsubscription/' . base64_encode(data_get($csUserBalance, 'user.id')) . '/' . base64_encode(data_get($csUserBalance, 'id')))}}">
                            <i class="glyphicon glyphicon-pencil"></i>
                        </a>
                    @endif
                    @if (
                            data_get($csUserBalance, 'status', null) === 1
                            && data_get($csUserBalance, 'chargetype', null) !== 'subscription'
                        )
                        &nbsp;
                        <a href="{{ url('admin/customer_balances/add/' . base64_encode(data_get($csUserBalance, 'id'))) }}">
                            <i class="glyphicon glyphicon-pencil"></i>
                        </a>
                    @endif
                    &nbsp;
                    <a
                        href="{{ url('admin/customer_balances/relatedpayments/' . base64_encode(data_get($csUserBalance, 'id'))) }}">
                        <i class="glyphicon glyphicon-zoom-in"></i>
                    </a>
                </td>
            </tr>
        @endforeach
        <tr>
            <td heigth="6" colspan="10"></td>
        </tr>
    </table>
</form>

@include('partials.dispacher.paging_box', ['paginator' => $csUserBalances, 'limit' => $limit])