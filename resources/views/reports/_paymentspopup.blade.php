<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading"><h6 class="panel-title">Payments</h6></div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered text-center">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Amount($)</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Payment Source</th>
                            <th class="text-center">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($payments) && count($payments) > 0)
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>{{ $payment->id ?? '' }}</td>
                                    <td>{{ $payment->amount ?? '' }}</td>
                                    <td>{{ $paymentTypeValue[$payment->type] ?? '' }}</td>
                                    <td>{{ ($payment->pay_type ?? 0) == 1 ? "Card" : "Bank Account" }}</td>
                                    <td>{{ !empty($payment->charged_at) && strpos($payment->charged_at, '0000') !== 0 ? \Carbon\Carbon::parse($payment->charged_at)->timezone($payment->timezone ?? config('app.timezone'))->format("m/d/Y h:i A") : '--' }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5">Sorry, no record found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
