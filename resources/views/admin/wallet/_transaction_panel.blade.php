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
    <form method="get" action="{{ $listUrl }}" class="form-inline" style="margin-bottom:12px;">
        <label>Per page
            <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                @foreach([25, 50, 100, 200] as $opt)
                    <option value="{{ $opt }}" @if((int)($limit ?? 50) === $opt) selected @endif>{{ $opt }}</option>
                @endforeach
            </select>
        </label>
        @if(($keyword ?? '') !== '')
            <input type="hidden" name="searchKey" value="{{ $keyword }}">
        @endif
    </form>

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
                <td style="text-align:center;">{{ $transaction->charged_at ? date('Y-m-d h:i A', strtotime((string)$transaction->charged_at)) : '' }}</td>
                <td style="text-align:left;">{{ ucwords(str_replace('_', ' ', (string)($transaction->note ?? ''))) }}</td>
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
