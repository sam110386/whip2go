@if ($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
<div class="text-center">
    {{ $records->appends(['order_rule_id' => $order_rule_id])->links() }}
</div>
@endif
<table width="100%" cellpadding="1" cellspacing="1"  border="0"  class="table  table-responsive">
    <thead>
        <tr>
            <th style="width:5px;">
                #
            </th>	   
            <th style="width:10px;">
                Amount
            </th>
            <th style="width:5px;">
                Transaction #
            </th>
            <th style="width:5px;">
                Date
            </th>
        </tr>
    </thead>
    <tbody>
        @foreach ($records as $record)
            <tr>
                <td>
                    {{ $record['InsurancePayerPayment']['id'] }}
                </td>
                <td>
                    {{ $record['InsurancePayerPayment']['amount'] }}
                </td>
                <td>
                    {{ $record['InsurancePayerPayment']['transaction_id'] }}
                </td>
                <td>
                    {{ $record['InsurancePayerPayment']['created'] }}
                </td>
            </tr>
        @endforeach
     </tbody>
</table>
