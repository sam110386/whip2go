@php
    $fmtMoney = fn($v) => number_format((float) $v, 2);
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $payoutlists, 'limit' => $limit ?? 25, 'position' => 'top'])

@if (empty($listtype))
    <div class="table-responsive">
        <table width="100%" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['title' => 'Payout#', 'field' => 'id','style'=>'width:5px;'],
                        ['title' => 'Amount Paid', 'sortable' => false,'style'=>'width:5px;'],
                        ['title' => 'Payout Date', 'sortable' => false,'style'=>'width:20px;'],
                        ['title' => 'Payout#', 'sortable' => false,'style'=>'width:10px;'],
                        ['title' => 'Action', 'sortable' => false,'style'=>'width:10px;']
                    ]])
                </tr>
            </thead>
            <tbody>
                @forelse ($payoutlists as $payoutlist)
                <tr>
                    <td>{{ $payoutlist->id }}</td>
                    <td>{{ $payoutlist->amount }}</td>
                    <td>{{ \Carbon\Carbon::parse($payoutlist->processed_on)->format('m/d/Y') }}</td>
                    <td>{{ $payoutlist->transaction_id }}</td>
                    <td>
                        <a  href="javascript:void(0)" onclick="getTransactions({{$payoutlist->id }})" title="Associated transactions">
                            <i class='icon-coin-dollar'></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td heigth="6" colspan="16"></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="table-responsive">
        <table width="100%" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['title' => 'Booking#', 'sortable' => false, 'style'=>'text-align:center;'],
                        ['title' => 'Vehicle', 'sortable' => false, 'style'=>'text-align:center;'],
                        ['title' => 'Customer', 'sortable' => false, 'style'=>'text-align:center;'],
                        ['title' => 'Transaction Type', 'sortable' => false, 'style'=>'text-align:center;'],
                        ['title' => 'Amount', 'sortable' => false, 'style'=>'text-align:center;'],
                        ['title' => 'Misc Fee', 'sortable' => false, 'style'=>'text-align:center;'],
                        ['title' => 'Net Amount', 'sortable' => false, 'style'=>'text-align:center;'],
                        ['title' => 'Date', 'sortable' => false, 'style'=>'text-align:center;'],
                        ['title' => 'Action', 'sortable' => false, 'style'=>'text-align:center;']
                    ]])
                </tr>
            </thead>
            <tbody>
                @forelse ($payoutlists as $payoutlist)
                    @php
                        $refund = (float)($payoutlist->refund ?? 0);
                        $amt = (float)($payoutlist->amount ?? 0);
                        $stripe = (float)($payoutlist->stripe_amt ?? 0);
                        $showAmt = $refund > 0 ? '-' . $fmtMoney($refund) : $fmtMoney($amt);
                        $misc = $refund > 0 ? '-' : ($stripe > 0 ? $fmtMoney($amt - $stripe) : '0.00');
                        $net = $refund > 0 ? '-' . $fmtMoney($refund) : ($stripe > 0 ? $fmtMoney($stripe) : $fmtMoney($amt));
                        $oid = base64_encode((string)($payoutlist?->csOrder?->id ?? ''));
                    @endphp
                    <tr>
                        <td class="text-center">
                            {{ $payoutlist?->csOrder?->increment_id ?? '' }}
                        </td>
                        <td class="text-center">
                            {{ $payoutlist?->csOrder?->vehicle?->vehicle_name ?? '' }}
                        </td>
                        <td class="text-center">
                            {{ trim(($payoutlist?->csOrder?->renter?->first_name ?? '') . ' ' . ($payoutlist?->csOrder?->renter?->last_name ?? '')) }}
                        </td>
                        <td class="text-center">
                            {{ $paymentTypeValue[$payoutlist->type] }}
                        </td>
                        <td class="text-center">
                            {{ $showAmt }}
                        </td>
                        <td class="text-center">
                            {{ $misc }}
                        </td>
                        <td class="text-center">
                            {{ $net }}
                        </td>
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($payoutlist->created)->format('Y-m-d h:i A') }}
                        </td>
                        <td>
                            @if (!empty($payoutlist?->csOrder?->id))
                                <a href="/admin/transactions/updatetransaction/{{ $oid }}" class="btn btn-default btn-xs">
                                    <img src="{{ asset('img/edit.png') }}" alt="Edit" title="Edit" style="border:0px;">
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" align="center">No rows found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@include('partials.dispacher.paging_box', ['paginator' => $payoutlists, 'limit' => $limit ?? 25])
