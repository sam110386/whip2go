@php
    $fmtMoney = fn($v) => number_format((float) $v, 2);
@endphp

<div class="panel">
    <div class="panel-body">
        <div class="table-responsive">
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
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
                    @forelse ($transactions as $transaction)
                        @php
                            $refund = (float)($transaction->refund ?? 0);
                            $amt = (float)($transaction->amount ?? 0);
                            $stripe = (float)($transaction->stripe_amt ?? 0);
                            $showAmt = $refund > 0 ? '-' . $fmtMoney($refund) : $fmtMoney($amt);
                            $misc = $refund > 0 ? '-' : ($stripe > 0 ? $fmtMoney($amt - $stripe) : '0.00');
                            $net = $refund > 0 ? '-' . $fmtMoney($refund) : ($stripe > 0 ? $fmtMoney($stripe) : $fmtMoney($amt));
                            $oid = base64_encode((string)($transaction?->csOrder?->id ?? ''));
                        @endphp
                        <tr>
                            <td class="text-center">
                            {{ $transaction?->csOrder?->increment_id ?? '' }}
                            </td>
                            <td class="text-center">
                                {{ $transaction?->csOrder?->vehicle?->vehicle_name ?? '' }}
                            </td>
                            <td class="text-center">
                                {{ trim(($transaction?->csOrder?->renter?->first_name ?? '') . ' ' . ($transaction?->csOrder?->renter?->last_name ?? '')) }}
                            </td>
                            <td class="text-center">
                                {{ $paymentTypeValue[$transaction->type] }}
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
                                {{ \Carbon\Carbon::parse($transaction?->csOrder?->start_datetime)->format('Y-m-d h:i A') }}
                            </td>
                            <td>
                                @if (!empty($transaction?->csOrder?->id))
                                    <a href="/admin/transactions/updatetransaction/{{ $oid }}" class="btn btn-default btn-xs">
                                        <img src="{{ asset('img/edit.png') }}" alt="Edit" title="Edit" style="border:0px;">
                                    </a>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $transaction->start_datetime }}
                            </td>
                        </tr>
                    @empty
                        <tr id="set_hide">
                            <th colspan="4">No Record Available!</th>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>