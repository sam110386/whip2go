@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50, 'position' => 'top'])
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => 'Charged Transaction #', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Charged Time (UTC)', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Charged Amount', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Used Amount', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Used Transaction #', 'sortable' => false, 'style' => 'text-align:center;'],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ $list->cpl_transaction_id ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->charged_at ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->cpl_amount ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->c_amount ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->c_transaction_id ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
