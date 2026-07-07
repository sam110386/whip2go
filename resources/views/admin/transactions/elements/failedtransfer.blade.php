@php
    $reportlists ??= [];
    $limit ??= 50;
    $paymentTypes = $commonService->getPayoutTypeValue(true);
    $formatDate = function ($value, $format, $tz = 'UTC') {
        if (empty($value))
            return '--';
        try {
            $date = is_numeric($value) ? new \DateTime("@{$value}") : new \DateTime($value);
            $date->setTimezone(new \DateTimeZone($tz));
            return $date->format($format);
        } catch (\Exception $e) {
            return '--';
        }
    };
@endphp

@include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit, 'position' => "top"])

<table width="100%" cellpadding="1" cellspacing="1" border="0" class="table  table-responsive">
    <thead>
        <tr>
            <th>Booking# </th>
            <th>Start Date </th>
            <th>End Date </th>
            <th>Amount </th>
            <th>Transaction # </th>
            <th>Charged Date </th>
            <th>Charged Type </th>
            <th>Action </th>
        </tr>
    </thead>
    <tbody>
        @forelse ($reportlists as $trip)
            <tr id="failedtr_{{ data_get($trip, 'id', '') }}">
                <td>
                    {{ data_get($trip, 'csOrder.increment_id', '') }}
                </td>
                <td>
                    {{ $formatDate(data_get($trip, 'csOrder.start_datetime'), 'Y-m-d h:i A', data_get($trip, 'csOrder.timezone', config('app.timezone', 'UTC'))) }}
                </td>
                <td>
                    {{ $formatDate(data_get($trip, 'csOrder.end_datetime'), 'Y-m-d h:i A', data_get($trip, 'csOrder.timezone', config('app.timezone', 'UTC'))) }}
                </td>
                <td>
                    {{ data_get($trip, 'amount', '') }}
                </td>
                <td>
                    {{ data_get($trip, 'transaction_id', '') }}
                </td>
                <td>
                    {{ $formatDate(data_get($trip, 'charged_at'), 'Y-m-d h:i A') }}
                </td>
                <td>
                    {{ $paymentTypes[data_get($trip, 'type')] ?? '--' }}
                </td>
                <td>
                    <a href="javascript:;" alt="Add To Transfer Queue" title="Add To Transfer Queue"
                        onclick="RequeueFailedTrasnfer('{{ data_get($trip, 'id', '') }}')">
                        <i class="glyphicon glyphicon-edit"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="padding:12px;">No records.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit])