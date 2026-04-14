<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th valign="top">Debit</th>
            <th valign="top">Credit</th>
            <th valign="top">Date</th>
            <th valign="top">Transaction</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction['amount'] >= 0 ? $transaction['iso_currency_code'] . ' ' . $transaction['amount'] : '' }}</td>
                <td>{{ $transaction['amount'] < 0 ? $transaction['iso_currency_code'] . ' ' . $transaction['amount'] : '' }}</td>
                <td>{{ $transaction['date'] }}</td>
                <td>{{ $transaction['name'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
