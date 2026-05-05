@php
    $timezone ??= config('app.timezone');
    $limit ??= 50;
@endphp
<div style="width:100%; overflow: visible;">
    @if(!empty($reportlists) && $reportlists->total() > 0)
        <div class="table-responsive">
            <table class="table table-responsive table-bordered">
                <thead>
                    <tr>
                        @include('partials.dispacher.sortable_header', ['columns' => [
                            ['title' => 'Time', 'field' => 'created'],
                            ['title' => 'Debit', 'field' => 'amt'],
                            ['title' => 'Credit', 'field' => 'amt'],
                            ['title' => 'Running Bal.', 'sortable' => false],
                            ['title' => 'Type', 'field' => 'type'],
                            ['title' => 'Source', 'field' => 'source'],
                            ['title' => 'Action', 'sortable' => false],
                            ['title' => 'Booking#', 'field' => 'increment_id'],
                            ['title' => 'Transaction', 'field' => 'transaction_id'],
                            ['title' => 'Note', 'field' => 'note', 'style' => 'width:160px;']
                        ]])
                    </tr>
                </thead>
                <tbody>
                    @php
                        $runningBal = 0;
                        $totalDebit = $totalCredit = 0;
                        $reversedList = collect($reportlists->items())->reverse();
                        $finalRows = [];
                    @endphp
                    @foreach($reversedList as $trip)
                        @php
                            if ($trip->rtype == 'C') { $totalCredit += $trip->amt; }
                            elseif ($trip->rtype == 'D') { $totalDebit += $trip->amt; }
                            $runningBal = $trip->rtype == 'C'
                                ? sprintf('%0.2f', ($runningBal + $trip->amt))
                                : sprintf('%0.2f', ($runningBal - $trip->amt));
                            $finalRows[] = (object) array_merge((array) $trip, ['running_bal' => $runningBal]);
                        @endphp
                    @endforeach
                    <tr>
                        <td><strong>TOTAL</strong></td>
                        <td><strong>{{ $totalDebit }}</strong></td>
                        <td><strong>{{ $totalCredit }}</strong></td>
                        <td><strong>{{ $runningBal }}</strong></td>
                        <td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                    @foreach(array_reverse($finalRows) as $trip)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($trip->created)->timezone($timezone)->format('m/d/Y h:i A') }}</td>
                            <td>{{ $trip->rtype == 'D' ? $trip->amt : '' }}</td>
                            <td>{{ $trip->rtype == 'C' ? $trip->amt : '' }}</td>
                            <td>{{ $trip->running_bal }}</td>
                            <td>{{ $reportlib->getPaymentType(false, $trip->type) }}</td>
                            <td>{{ ucfirst($trip->source) }}</td>
                            <td>{{ $reportlib->getPaymentTypeAction($trip->type, $trip->rtype, $trip->source) }}</td>
                            <td>
                                @if(!empty($trip->increment_id))
                                    <a href="javascript:;" onclick="bookingDetail({{ $trip->cs_order_id }})">{{ $trip->increment_id }}</a>
                                @endif
                            </td>
                            <td>
                                @if(!empty($trip->transaction_id))
                                    @if($trip->type == 12)
                                        <a href="javascript:;" onclick="payoutDetail('{{ $trip->transaction_id }}')">{{ $trip->transaction_id }}</a>
                                    @else
                                        <a href="javascript:;" onclick="transactionDetail('{{ $trip->transaction_id }}')">{{ $trip->transaction_id }}</a>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $trip->note }}</td>
                        </tr>
                    @endforeach
                    <tr><td height="6" colspan="17"></td></tr>
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
