@php
    $reportlists ??= [];
    $total ??= 0;
    $limit ??= 50;
@endphp

<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th style="width:5px;">Booking#</th>
                <th style="width:5px;">Start</th>
                <th style="width:5px;">End</th>
                <th style="width:5px;">Amount</th>
                <th style="width:5px;">Transaction #</th>
                <th style="width:5px;">Charge Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reportlists as $reportlist)
                <tr>
                    <td>
                        {{ data_get($reportlist, 'increment_id', '') }}
                    </td>
                    <td>
                        @if (!empty(data_get($reportlist, 'start_datetime', '')))
                            {{ \Carbon\Carbon::parse(data_get($reportlist, 'start_datetime', ''))->timezone(data_get($reportlist, 'timezone', 'UTC'))->format('Y-m-d g:i A') }}
                        @endif
                    </td>
                    <td>
                        @if (!empty(data_get($reportlist, 'end_datetime', '')))
                            {{ \Carbon\Carbon::parse(data_get($reportlist, 'end_datetime', ''))->timezone(data_get($reportlist, 'timezone', 'UTC'))->format('Y-m-d g:i A') }}
                        @endif
                    </td>
                    <td>
                        {{ data_get($reportlist, 'amount', '') }}
                    </td>
                    <td>
                        {{ data_get($reportlist, 'transaction_id', '') }}
                    </td>
                    <td>
                        @if (!empty(data_get($reportlist, 'created', null)))
                            {{ \Carbon\Carbon::parse(data_get($reportlist, 'created', null))->timezone(data_get($reportlist, 'timezone', 'UTC'))->format('Y-m-d g:i A') }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No payments in range.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="6" class="text-center">
                    <strong>Total {{ number_format((float) $total, 2) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit])