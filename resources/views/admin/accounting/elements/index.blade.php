@php
    $timezone ??= config('app.timezone', 'UTC');
    $limit ??= 50;
    $reportlists ??= collect();
    $totalDebit ??= '0.00';
    $totalCredit ??= '0.00';
    $runningBal ??= '0.00';
    $columns = [
        ['title' => 'Time', 'field' => 'created'],
        ['title' => 'Debit', 'sortable' => false],
        ['title' => 'Credit', 'sortable' => false],
        ['title' => 'Running Bal.', 'sortable' => false],
        ['title' => 'Type', 'sortable' => false],
        ['title' => 'Source', 'sortable' => false],
        ['title' => 'Action', 'sortable' => false],
        ['title' => 'Booking#', 'field' => 'increment_id'],
        ['title' => 'Transaction', 'sortable' => false],
        ['title' => 'Note', 'sortable' => false, 'style' => 'width:160px;']
    ];
@endphp

<div style="width:100%; overflow: visible;">
    @if($reportlists && $reportlists->count() > 0)

        @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit, 'position' => 'top'])

        <div class="table-responsive">
            <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table  table-responsive table-bordered">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', compact('columns'))
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>TOTAL</strong></td>
                        <td><strong>{{ $totalDebit }}</strong></td>
                        <td><strong>{{ $totalCredit }}</strong></td>
                        <td><strong>{{ $runningBal }}</strong></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>

                    @foreach($reportlists as $trip)
                        <tr>
                            <td>
                                {{ ($trip->created && $timezone) ? \Carbon\Carbon::parse($trip->created)->timezone($timezone)->format('m/d/Y h:i A') : '' }}
                            </td>
                            <td>
                                {{ $trip->rtype === 'D' ? $trip->amt : '' }}
                            </td>
                            <td>
                                {{ $trip->rtype === 'C' ? $trip->amt : '' }}
                            </td>
                            <td>
                                {{ $trip->running_bal }}
                            </td>
                            <td>
                                {{ \App\Services\Legacy\Reportlib::getPaymentType(false, $trip->type) }}
                            </td>
                            <td>
                                {{ ucfirst($trip->source) }}
                            </td>
                            <td>
                                {{ \App\Services\Legacy\Reportlib::getPaymentTypeAction($trip->type, $trip->rtype, $trip->source) }}
                            </td>
                            <td>
                                @if(!empty($trip?->csOrder?->increment_id))
                                    <a href="javascript:void(0)" onclick="bookingDetail({{ $trip?->csOrder?->id }})">
                                        {{ $trip->csOrder->increment_id }}
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if(!empty($trip->transaction_id))
                                    @if($trip->type == 12)
                                        <a href="javascript:void(0)" onclick="payoutDetail('{{ $trip->transaction_id }}')">
                                            {{ $trip->transaction_id }}
                                        </a>
                                    @else
                                        <a href="javascript:void(0)" onclick="transactionDetail('{{ $trip->transaction_id }}')">
                                            {{ $trip->transaction_id }}
                                        </a>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $trip->note }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td height="6" colspan="17"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit])
    @else
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <td colspan="10" class="text-center">No record found</td>
                </tr>
            </table>
        </div>
    @endif
</div>