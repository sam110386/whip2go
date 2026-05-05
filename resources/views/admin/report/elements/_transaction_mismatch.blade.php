@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => 'Charged Transaction #', 'field' => 'cpl_transaction_id', 'style' => 'text-align:center;'],
                    ['title' => 'Charged Time (UTC)', 'field' => 'charged_at', 'style' => 'text-align:center;'],
                    ['title' => 'Charged Amount', 'field' => 'cpl_amount', 'style' => 'text-align:center;'],
                    ['title' => 'Used Amount', 'field' => 'c_amount', 'style' => 'text-align:center;'],
                    ['title' => 'Used Transaction #', 'field' => 'c_transaction_id', 'style' => 'text-align:center;'],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ $list['TransactionMismatch']['cpl_transaction_id'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['TransactionMismatch']['charged_at'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['TransactionMismatch']['cpl_amount'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['TransactionMismatch']['c_amount'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['TransactionMismatch']['c_transaction_id'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
