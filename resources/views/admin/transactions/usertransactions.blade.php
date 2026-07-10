@php
    $bookingid ??= '';
    $currency ??= '';
    $userid ??= '';
    $time ??= '';
    $wallet_balance ??= 0;
@endphp

<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Driver</span> - Transactions
            </h4>
        </div>
        <div class="heading-elements">
            <span class="text-bold">Wallet Balance : ${{ $wallet_balance ?? 0 }}</span>

            <a href="javascript:;" class="btn left-margin"
                onclick="chargePartialAmtPopup('{{ $userid }}', '{{ $bookingid }}', '{{ $currency }}')">
                Charge Partial Amount
            </a>

            <button type="button" class="btn btn-primary" title="Check Income"
                onclick="return checkMeasureOneIncome('{{ base64_encode($userid) }}');">
                Check User Income
            </button>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <form id="ReportDriverTransactionAdminUsertransactionsForm" action="{{ url()->current() }}" method="GET"
            class="form-horizontal">
            @csrf

            <div class="row pb-10">
                <div class="col-md-12">
                    <div class="col-md-3">
                        @php
                            $options = [
                                '1 day' => 'Last 1 day',
                                '3 days' => 'Last 3 days',
                                '7 days' => 'Last 7 days',
                                '14 days' => 'Last 14 days',
                                '30 days' => 'Last 30 days'
                            ];
                            $selectedTime = old('time', $time ?? '');
                        @endphp

                        <select name="time" class="form-control" id="SearchTime">
                            @foreach($options as $value => $label)
                                <option value="{{ $value }}" {{ $selectedTime == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="user_id" value="{{ $userid }}">

                    <div class="col-md-3">

                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-body" id="transsactionlisting">
        @include('admin.transactions.elements.usertransactions')
    </div>
</div>