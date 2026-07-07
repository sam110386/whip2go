@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50, 'position' => 'top'])
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;"> Charged Transaction #</th>
                <th style="text-align:center;"> Charged Time (UTC)</th>
                <th style="text-align:center;"> Charged Amount</th>
                <th style="text-align:center;"> Used Amount</th>
                <th style="text-align:center;"> Used Transaction #</th>
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