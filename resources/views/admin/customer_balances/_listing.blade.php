@php
    $typeLabel = function ($t) use ($balanceTypes) {
        $k = (string)$t;
        return $balanceTypes[$k] ?? '';
    };

    $columns = [
        ['field' => 'id', 'title' => '#'],
    ];

    if (!$subscriptionMode) {
        $columns[] = ['field' => 'first_name', 'title' => 'Name'];
    }

    $columns = array_merge($columns, [
        ['field' => 'type', 'title' => 'Type'],
        ['field' => 'credit', 'title' => 'Credit'],
        ['field' => 'debit', 'title' => 'Debit'],
        ['field' => 'balance', 'title' => 'Balance'],
        ['field' => 'chargetype', 'title' => 'Schedule Details', 'sortable' => false],
        ['field' => 'last_processed', 'title' => 'Last Processed'],
        ['field' => 'note', 'title' => 'Note', 'sortable' => false],
        ['field' => 'created', 'title' => 'Created'],
        ['field' => 'action', 'title' => 'Action', 'sortable' => false, 'class' => 'text-center'],
    ]);
@endphp

<div class="table-responsive">
    <table class="table table-hover table-bordered">
        <thead>
            <tr class="bg-slate-300">
                @include('partials.dispacher.sortable_header', ['columns' => $columns])
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $row)
                @php
                    $ct = strtolower((string)($row->chargetype ?? ''));
                @endphp
                <tr>
                    <td>{{ $row->id }}</td>
                    @if (!$subscriptionMode)
                        <td>
                            <div class="text-semibold">{{ trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) }}</div>
                            @if(!empty($row->linked_user_id))
                                <div class="text-muted text-size-small">ID: {{ $row->linked_user_id }}</div>
                            @endif
                        </td>
                    @endif
                    <td><span class="label label-info">{{ $typeLabel($row->type) }}</span></td>
                    <td class="text-success text-semibold">{{ number_format($row->credit, 2) }}</td>
                    <td class="text-danger text-semibold">{{ number_format($row->debit, 2) }}</td>
                    <td class="text-primary text-bold">{{ number_format($row->balance, 2) }}</td>
                    <td class="text-size-small">
                        @if($row->chargetype)
                            <div><span class="text-semibold">Type:</span> {{ ucfirst($row->chargetype) }}</div>
                        @endif
                        @if($row->installment_type)
                            <div><span class="text-semibold">Installment:</span> {{ ucfirst($row->installment_type) }}</div>
                        @endif
                        @if($row->installment > 0)
                            <div><span class="text-semibold">Amt:</span> {{ number_format($row->installment, 2) }}</div>
                        @endif
                    </td>
                    <td>{{ $formatDt($row->last_processed ?? null) }}</td>
                    <td><span class="text-size-small">{{ $row->note }}</span></td>
                    <td><span class="text-size-small">{{ $row->created }}</span></td>
                    <td class="text-center">
                        <ul class="icons-list">
                            <li>
                                @if ((int)$row->status === 1)
                                    <a href="{{ url('admin/customer_balances/status', [base64_encode((string)$row->id), 0]) }}"
                                       onclick="return confirm('Are you sure to update this record?');">
                                        <span class="label label-success" title="Set inactive">Active</span>
                                    </a>
                                @else
                                    <a href="{{ url('admin/customer_balances/status', [base64_encode((string)$row->id), 1]) }}"
                                       onclick="return confirm('Are you sure to update this record?');">
                                        <span class="label label-default" title="Set active">Inactive</span>
                                    </a>
                                @endif
                            </li>
                            
                            @if (!$subscriptionMode && (int)$row->status === 1 && $ct === 'subscription' && !empty($row->linked_user_id))
                                <li>
                                    <a href="{{ url('admin/customer_balances/addsubscription', [base64_encode((string)$row->linked_user_id), base64_encode((string)$row->id)]) }}"
                                       class="btn btn-default btn-xs" title="Edit subscription"><i class="icon-pencil7"></i></a>
                                </li>
                            @elseif (!$subscriptionMode && (int)$row->status === 1 && $ct !== 'subscription')
                                <li>
                                    <a href="{{ url('admin/customer_balances/add', base64_encode((string)$row->id)) }}" 
                                       class="btn btn-default btn-xs" title="Edit"><i class="icon-pencil7"></i></a>
                                </li>
                            @elseif ($subscriptionMode && (int)$row->status === 1 && $subscriptionUserId)
                                <li>
                                    <a href="{{ url('admin/customer_balances/addsubscription', [base64_encode((string)$subscriptionUserId), base64_encode((string)$row->id)]) }}"
                                       class="btn btn-default btn-xs" title="Edit"><i class="icon-pencil7"></i> Edit</a>
                                </li>
                            @endif

                            @if (!$subscriptionMode)
                                <li>
                                    <a href="{{ url('admin/customer_balances/relatedpayments', base64_encode((string)$row->id)) }}"
                                       class="btn btn-default btn-xs" title="Details"><i class="icon-search4"></i></a>
                                </li>
                            @endif
                        </ul>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $subscriptionMode ? 10 : 11 }}" class="text-center text-muted">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit ?? 50])
