<td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
    {{ data_get($trip, 'increment_id') }}
</td>

<td style="text-align:center;">
    @php
        $insurancePayer = data_get($trip, 'rule_insurance_payer');
    @endphp
    {{ $insurancePayer ? $commonService->getInsurancePayer($insurancePayer) : 'N/A' }}
</td>

<td onclick="openBookingDetails({{ data_get($trip, 'id') }});">
    {{ data_get($trip, 'vehicle_name') }}
</td>

<td onclick="openUserTransactions({{ data_get($trip, 'renter_id', 0) }}, '{{ data_get($trip, 'id') }}', '{{ data_get($trip, 'currency') }}');"
    style="text-align:center;">
    <span class="btn-link text-blue">
        @if(data_get($trip, 'renter_id'))
            {{ data_get($trip, 'driver.first_name') }} {{ data_get($trip, 'driver.last_name') }}
        @endif
    </span>
</td>

<td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
    {{ \Carbon\Carbon::parse($commonService->getParentOrderStartDate(data_get($trip, 'parent_id'), data_get($trip, 'start_datetime')))->timezone(data_get($trip, 'timezone', 'UTC'))->format('Y-m-d h:i A') }}
</td>

<td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
    {{ \Carbon\Carbon::parse(data_get($trip, 'start_datetime'))->timezone(data_get($trip, 'timezone', 'UTC'))->format('Y-m-d h:i A') }}
</td>

<td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
    {{ \Carbon\Carbon::parse(data_get($trip, 'end_datetime'))->timezone(data_get($trip, 'timezone', 'UTC'))->format('Y-m-d h:i A') }}
</td>

<td onclick="openUserTransactions({{ data_get($trip, 'renter_id', 0) }});" style="text-align:center;">
    @if(data_get($trip, 'renter_id'))
        {{ data_get($trip, 'driver.first_name') }} {{ data_get($trip, 'driver.last_name') }}
    @endif
</td>

@php
    $paymentStatus = data_get($trip, 'payment_status');
    $totalRent = data_get($trip, 'rent', 0) + data_get($trip, 'tax', 0) + data_get($trip, 'dia_fee', 0);
@endphp

@if($paymentStatus == 2)
    <td class="text-danger anchortag" onclick="retryrentalfee('{{ base64_encode(data_get($trip, 'id')) }}');"
        style="text-align:center;">
        {{ $totalRent }}-D
    </td>
@else
    <td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
        {{ $totalRent }}
        @if($paymentStatus == 2)
            -D
        @elseif($paymentStatus == 1)
            -P
        @else
            -UP
        @endif
    </td>
@endif

@php
    $emfStatus = data_get($trip, 'emf_status');
    $totalEmf = data_get($trip, 'extra_mileage_fee', 0) + data_get($trip, 'emf_tax', 0);
@endphp

@if($emfStatus == 2)
    <td class="text-danger anchortag" onclick="retryemf('{{ base64_encode(data_get($trip, 'id')) }}');"
        style="text-align:center;">
        {{ $totalEmf }}-D
    </td>
@else
    <td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
        {{ $totalEmf }}{{ $emfStatus == 1 ? "-P" : "-UP" }}
    </td>
@endif

@php
    $diaInsuStatus = data_get($trip, 'dia_insu_status'); 
@endphp

@if($diaInsuStatus == 2)
    <td class="text-danger anchortag" onclick="retrydiainsurancefee('{{ base64_encode(data_get($trip, 'id')) }}');"
        style="text-align:center;">
        {{ data_get($trip, 'dia_insu') }}-D
    </td>
@else
    <td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
        {{ data_get($trip, 'dia_insu') }}
        @if($diaInsuStatus == 2)
            -D
        @elseif($diaInsuStatus == 1)
            -P
        @else
            -UP
        @endif
    </td>
@endif


@php
    $latenessStatus = data_get($trip, 'lateness_fee_status'); 
@endphp

@if($latenessStatus == 2)
    <td class="text-danger anchortag" onclick="retrylatefee('{{ base64_encode(data_get($trip, 'id')) }}');"
        style="text-align:center;">
        {{ data_get($trip, 'lateness_fee') }}-D
    </td>
@else
    <td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
        {{ data_get($trip, 'lateness_fee') }}
        {{ $latenessStatus == 1 ? "-P" : "-UP" }}
    </td>
@endif

@php
    $targetId = data_get($trip, 'parent_id') ?: data_get($trip, 'id');
    $dpaStatus = data_get($trip, 'dpa_status');
@endphp

@if($dpaStatus == 2)
    <td class="text-danger anchortag" onclick="retrydepositfee('{{ base64_encode(data_get($trip, 'id')) }}');"
        style="text-align:center;">
        {{ data_get($trip, 'deposit') }}-D
        ({{ $commonService->getBookingTotalDeposit($targetId) }})
    </td>
@else
    <td style="text-align:center;">
        {{ data_get($trip, 'deposit') }}
        @if($dpaStatus == 2)
            -D
        @elseif($dpaStatus == 1)
            -P
        @else
            -UP
        @endif
        ({{ $commonService->getBookingTotalDeposit($targetId) }})
    </td>
@endif

@php
    $insuStatus = data_get($trip, 'insu_status'); 
@endphp

@if($insuStatus == 2)
    <td class="text-danger anchortag" onclick="retryinsurancefee('{{ base64_encode(data_get($trip, 'id')) }}');"
        style="text-align:center;">
        {{ data_get($trip, 'insurance_amt') }}-D
        ({{ $commonService->getBookingTotalInsurance($targetId) }})
    </td>
@else
    <td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
        {{ data_get($trip, 'insurance_amt') }}
        @if($insuStatus == 2)
            -D
        @elseif($insuStatus == 1)
            -P
        @else
            -UP
        @endif
        ({{ $commonService->getBookingTotalInsurance($targetId) }})
    </td>
@endif

@php
    $infeeStatus = data_get($trip, 'infee_status'); 
@endphp

@if($infeeStatus == 2)
    <td class="text-danger anchortag" onclick="retryinitialfee('{{ base64_encode(data_get($trip, 'id')) }}');"
        style="text-align:center;">
        {{ data_get($trip, 'initial_fee') }}-D
        ({{ $commonService->getBookingTotalInitialFee($targetId) }})
    </td>
@else
    <td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
        {{ data_get($trip, 'initial_fee') }}
        @if($infeeStatus == 2)
            -D
        @elseif($infeeStatus == 1)
            -P
        @else
            -UP
        @endif
        ({{ $commonService->getBookingTotalInitialFee($targetId) }})
    </td>
@endif

<td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
    {{ data_get($trip, 'toll') }}
</td>

@if(data_get($trip, 'pending_toll', 0) > 0)
    <td class="text-danger anchortag" onclick="retrytollfee('{{ base64_encode(data_get($trip, 'id')) }}');"
        style="text-align:center;">
        {{ data_get($trip, 'pending_toll') }}-D
    </td>
@else
    <td onclick="openBookingDetails({{ data_get($trip, 'id') }});" style="text-align:center;">
        {{ data_get($trip, 'pending_toll') }}
    </td>
@endif

<td>
    <span class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-position="left" aria-expanded="true">
            <i class="icon-cog7"></i>
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-solid pull-right">
            @if(data_get($trip, 'delivery'))
                <li>
                    <a href="javascript:void(0)" title="Pickup/ Delivery"
                        onclick="return pickupDelivery('{{ base64_encode(data_get($trip, 'delivery')) }}', {{ data_get($trip, 'id') }});">
                        <i class="icon-steering-wheel"></i> Pickup/ Delivery
                    </a>
                </li>
            @endif

            @if(data_get($trip, 'status') == 0 && data_get($trip, 'checkr_status') == 1)
                <li>
                    <a href="javascript:void(0)" title="Checkr Approve"
                        onclick="return checkrApprove('{{ base64_encode(data_get($trip, 'id')) }}');">
                        <i class="icon-thumbs-up3"></i> Checkr Approve
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0)" title="Checkr Disapprove"
                        onclick="return checkrDisapprove('{{ base64_encode(data_get($trip, 'id')) }}');">
                        <i class="icon-thumbs-down3"></i> Checkr Disapprove
                    </a>
                </li>
            @endif

            @if(data_get($trip, 'status') == 0 && data_get($trip, 'checkr_status') == 0)
                <li>
                    <a href="javascript:void(0)" title="Start"
                        onclick="return startBooking('{{ base64_encode(data_get($trip, 'id')) }}');">
                        <i class="glyphicon glyphicon-ok-circle"></i> Start Booking
                    </a>
                </li>
            @endif

            @if((data_get($trip, 'status') == 0 || data_get($trip, 'status') == 1) && data_get($trip, 'checkr_status') == 0)
                <li>
                    <a href="javascript:void(0)" title="Complete"
                        onclick="return completeBooking('{{ base64_encode(data_get($trip, 'id')) }}');">
                        <i class="glyphicon glyphicon-saved"></i> Complete/Renew
                    </a>
                </li>
            @endif

            @if(data_get($trip, 'status') == 0 && data_get($trip, 'checkr_status') == 0)
                <li>
                    <a href="javascript:void(0)" title="Cancel"
                        onclick="return cancelBooking('{{ base64_encode(data_get($trip, 'id')) }}');">
                        <i class="icon-trash"></i> Cancel Booking
                    </a>
                </li>
            @endif

            @if(data_get($trip, 'status') == 1)
                <li>
                    <a href="javascript:void(0)" title="Download Doc"
                        onclick="return downloadBookingDoc('{{ base64_encode(data_get($trip, 'id')) }}', 1);">
                        <i class="icon-magazine"></i> Download Doc
                    </a>
                </li>
            @endif

            <li>
                <a href="javascript:void(0)" title="Transaction logs"
                    onclick="return gettransactionlogs('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-coin-dollar"></i> Transaction logs
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" title="Message History"
                    onclick="return getmessagehistory('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-comments"></i> Message History
                </a>
            </li>

            @if(data_get($trip, 'checkr_status') == 0)
                <li>
                    <a href="{{ url('admin/bookings/edit/' . base64_encode(data_get($trip, 'id'))) }}" title="Update">
                        <i class="icon-pencil"></i> Booking Edit
                    </a>
                </li>
            @endif

            <li>
                <a href="javascript:void(0)" title="Extend Vehicle Lock Time"
                    onclick="return changeVehicleLockTime('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-alarm"></i> Extend Vehicle Lock Time
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" title="Edit Payment Rules"
                    onclick="return updateOrderDepositRules('{{ base64_encode($targetId) }}');">
                    <i class="icon-price-tag"></i> Edit Payment Rules
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" title="Edit Vehicle GPS Setting"
                    onclick="return changeVehicleGps('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-car"></i> Edit Vehicle GPS Setting
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" title="Update Odometer"
                    onclick="return updateOdometer('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-meter2"></i> Update Odometer
                </a>
            </li>
            <li>
                <a href="{{ url('admin/bookings/goalrecalculate/' . base64_encode($targetId)) }}"
                    title="Edit Goal Calculations"
                    onclick="return confirm('Are you sure you want to edit this booking goal calculations?')">
                    <i class="icon-cog3"></i> Edit Goal Calculations
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" title="Schedule Extend time"
                    onclick="return loadextendtime('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-watch2"></i> Schedule Extend time
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" title="Partial Payment"
                    onclick="return partialPayment('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-percent"></i> Partial Payment
                </a>
            </li>
        </ul>
    </span>
</td>