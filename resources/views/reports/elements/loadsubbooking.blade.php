@php
$class = 'even';
@endphp
@foreach ($subbookinglists as $k => $trip)
    @php
    $class = (trim($class) == 'even') ? 'odd' : 'even';
    $openTripDetails = "openTripDetails('".base64_encode($trip['CsOrder']['id'])."')";
    @endphp
    <tr class="child_{{ $booking_id }}" style="background: rgb(225, 245, 254);">
        <td></td>
        
        <td onclick="{{ $openTripDetails }}">
            {{ $booking_id == $trip['CsOrder']['id'] ? $trip['CsOrder']['increment_id'].'-0' : $trip['CsOrder']['increment_id'] }}
        </td>
        
        <td onclick="{{ $openTripDetails }}">
            @if ($trip['CsOrder']['status'] == 3)
                Completed
            @elseif ($trip['CsOrder']['status'] == 2)
                Canceled
            @else
                Incomplete
            @endif
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $trip['CsOrder']['vehicle_name'] }}
        </td>

        <td onclick="{{ $openTripDetails }}">
            {{ !empty($trip['CsOrder']['start_datetime']) && strpos($trip['CsOrder']['start_datetime'], '0000') !== 0 ? \Carbon\Carbon::parse($trip['CsOrder']['start_datetime'])->timezone($trip['CsOrder']['timezone'] ?? config('app.timezone'))->format("m/d/Y h:i A") : '--' }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ !empty($trip['CsOrder']['end_datetime']) && strpos($trip['CsOrder']['end_datetime'], '0000') !== 0 ? \Carbon\Carbon::parse($trip['CsOrder']['end_datetime'])->timezone($trip['CsOrder']['timezone'] ?? config('app.timezone'))->format("m/d/Y h:i A") : '--' }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ \App\Support\PortfolioSupport::daysBetweenDates($trip['CsOrder']['start_datetime'], $trip['CsOrder']['end_datetime']) }}
        </td> 
        <td  onclick="{{ $openTripDetails }}">
            {{ $trip['User']['first_name'] . ' ' . $trip['User']['last_name'] }}
        </td>

        <td onclick="{{ $openTripDetails }}">
            {{ $trip['CsOrder']['status'] == 3 ? $trip['CsOrder']['end_odometer'] - $trip['CsOrder']['start_odometer'] : 0 }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $trip['CsOrder']['paid_amount'] }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $trip['CsOrder']['insurance_amt'] + $trip['CsOrder']['dia_insu'] }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $trip['CsOrder']['toll'] + $trip['CsOrder']['pending_toll'] }}
        </td>
        
        <td>
            @if ($trip['CsOrder']['status'] == 3)
                <a href="javascript:void(0)" title="Review Images" onclick="reviewimages('{{ base64_encode($trip['CsOrder']['id']) }}')"><i class='icon-clipboard3'></i></a>
            @endif
            <a href="javascript:void(0)" title="Download Doc" onclick="return downloadBookingDoc('{{ base64_encode($trip['CsOrder']['id']) }}');"><i class=" icon-file-pdf"></i></a>
            <a href="javascript:void(0)" title="Download Payment Receipt" onclick="return loadPaymentsPopup('{{ base64_encode($trip['CsOrder']['id']) }}');"><i class=" icon-download"></i></a>
        </td>
    </tr>
@endforeach
