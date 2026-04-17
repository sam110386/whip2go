@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['field' => 'cpl_transaction_id', 'title' => 'Charged Transaction #', 'style' => 'text-align:center;'],
                    ['field' => 'charged_at', 'title' => 'Charged Time (UTC)', 'style' => 'text-align:center;'],
                    ['field' => 'cpl_amount', 'title' => 'Charged Amount', 'style' => 'text-align:center;'],
                    ['field' => 'c_amount', 'title' => 'Used Amount', 'style' => 'text-align:center;'],
                    ['field' => 'c_transaction_id', 'title' => 'Used Transaction #', 'style' => 'text-align:center;'],
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
