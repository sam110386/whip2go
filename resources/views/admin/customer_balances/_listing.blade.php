@php
    $typeLabel = function ($t) use ($balanceTypes) {
        $k = (string)$t;
        return $balanceTypes[$k] ?? '';
    };

    $currentSort = request('sort', 'id');
    $currentDir = request('direction', 'desc');
    $nextDir = ($currentDir === 'asc') ? 'desc' : 'asc';

    $sortLink = function($field, $label) use ($currentSort, $currentDir, $nextDir) {
        $url = url()->current() . '?' . http_build_query(array_merge(request()->all(), ['sort' => $field, 'direction' => ($currentSort === $field ? $nextDir : 'asc')]));
        $icon = '';
        if ($currentSort === $field) {
            $icon = $currentDir === 'asc' ? ' <i class="icon-arrow-up8"></i>' : ' <i class="icon-arrow-down8"></i>';
        }
        return '<a href="'.$url.'">'.$label.$icon.'</a>';
    };
@endphp

@if(!$subscriptionMode)
    <form action="" method="GET" name="frm1" id="frm1">
@endif

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <tr>
        <th valign="top" width="5%">{!! $sortLink('id', '#') !!}</th>
        @if (!$subscriptionMode)
            <th valign="top">{!! $sortLink('first_name', 'Name') !!}</th>
        @endif
        <th valign="top">Type</th>
        @if($subscriptionMode)
            <th valign="top">{!! $sortLink('credit', 'Credit') !!}</th>
            <th valign="top">{!! $sortLink('debit', 'Debit') !!}</th>
            <th valign="top">{!! $sortLink('balance', 'Balance') !!}</th>
        @else
            <th valign="top">{!! $sortLink('credit', 'Charge on Driver') !!}</th>
            <th valign="top">{!! $sortLink('debit', 'Debit') !!}</th>
            <th valign="top">{!! $sortLink('balance', 'Balance') !!}</th>
        @endif
        <th valign="top">Charge Type</th>
        <th valign="top">Last Processed</th>
        <th valign="top">Note</th>
        @if(!$subscriptionMode)
            <th valign="top">{!! $sortLink('created', 'Created') !!}</th>
        @endif
        <th valign="top">Action</th>
    </tr>
    @foreach ($records as $row)
        @php
            $ct = strtolower((string)($row->chargetype ?? ''));
        @endphp
        <tr>
            <td valign="top">{{ $row->id }}</td>
            @if (!$subscriptionMode)
                <td valign="top">{{ trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) }}</td>
            @endif
            <td valign="top">{{ $typeLabel($row->type) }}</td>
            <td valign="top">{{ $row->credit }}</td>
            <td valign="top">{{ $row->debit }}</td>
            <td valign="top">{{ $row->balance }}</td>
            <td valign="top">
                <strong>Type:</strong> {{ ucfirst((string)$row->chargetype) }}
                <br><strong>Installment Type:</strong> {{ ucfirst((string)$row->installment_type) }}
                <br><strong>Installment:</strong> {{ $row->installment }}
            </td>
            <td valign="top">{{ $formatDt($row->last_processed ?? null) }}</td>
            <td valign="top">{{ $row->note }}</td>
            @if(!$subscriptionMode)
                <td valign="top">{{ $row->created }}</td>
            @endif
            <td valign="top">
                @if ((int)$row->status === 1)
                    <a href="{{ url('admin/customer_balances/status', [base64_encode((string)$row->id), 0]) }}"
                       onclick="return confirm('Are you sure to update this record?');">
                        <img src="/img/green2.jpg" alt="Status" title="Status">
                    </a>
                @else
                    <a href="{{ url('admin/customer_balances/status', [base64_encode((string)$row->id), 1]) }}"
                       onclick="return confirm('Are you sure to update this record?');">
                        <img src="/img/red3.jpg" alt="Status" title="Status">
                    </a>
                @endif
                &nbsp;
                @if ($subscriptionMode && (int)$row->status === 1 && $subscriptionUserId)
                    <a href="{{ url('admin/customer_balances/addsubscription', [base64_encode((string)$subscriptionUserId), base64_encode((string)$row->id)]) }}">
                        <i class="glyphicon glyphicon-pencil"></i> Edit
                    </a>
                @else
                    @if ((int)$row->status === 1 && $ct === 'subscription' && !empty($row->linked_user_id))
                        <a href="{{ url('admin/customer_balances/addsubscription', [base64_encode((string)$row->linked_user_id), base64_encode((string)$row->id)]) }}">
                            <i class="glyphicon glyphicon-pencil"></i>
                        </a>
                    @elseif ((int)$row->status === 1 && $ct !== 'subscription')
                        &nbsp;
                        <a href="{{ url('admin/customer_balances/add', base64_encode((string)$row->id)) }}">
                            <i class="glyphicon glyphicon-pencil"></i>
                        </a>
                    @endif
                @endif
                &nbsp;
                @if (!$subscriptionMode)
                    <a href="{{ url('admin/customer_balances/relatedpayments', base64_encode((string)$row->id)) }}">
                        <i class="glyphicon glyphicon-zoom-in"></i>
                    </a>
                @endif
            </td>
        </tr>
    @endforeach
    <tr><td height="6" colspan="{{ $subscriptionMode ? 9 : 11 }}"></td></tr>
</table>

@if(!$subscriptionMode)
    </form>
@endif

@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit ?? 50])
