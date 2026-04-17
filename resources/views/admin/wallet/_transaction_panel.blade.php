@php
    $listUrl = !empty($adminContext) && !empty($useridB64)
        ? '/admin/wallet/index/' . $useridB64
        : '/wallet/index';
    $appends = array_filter([
        'searchKey' => $keyword ?? '',
        'Record' => ['limit' => $limit ?? 50],
    ]);
@endphp
<div class="panel panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">Balance</th>
                <th style="text-align:center;">Amount</th>
                <th style="text-align:center;">Booking#</th>
                <th style="text-align:center;">Transaction#</th>
                <th style="text-align:center;">Charged Date</th>
                <th style="text-align:left;">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td style="text-align:center;">{{ $transaction->balance }}</td>
                    <td style="text-align:center;">{{ $transaction->amount }}</td>
                    <td style="text-align:center;">{{ $transaction->order_increment_id ?? '' }}</td>
                    <td style="text-align:center;">{{ $transaction->transaction_id }}</td>
                    <td style="text-align:center;">
                        {{ $transaction->charged_at ? date('Y-m-d h:i A', strtotime((string) $transaction->charged_at)) : '' }}
                    </td>
                    <td style="text-align:left;">{{ ucwords(str_replace('_', ' ', (string) ($transaction->note ?? ''))) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <th colspan="6">No record found!</th>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($transactions, 'links'))
    <section class="pagging">
        {{ $transactions->appends($appends)->links() }}
    </section>
@endif