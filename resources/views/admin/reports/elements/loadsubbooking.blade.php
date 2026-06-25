@foreach ($subbookinglists as $k => $trip)
    @php
        $openTripDetails = "openTripDetails('" . base64_encode($trip['id']) . "')";
    @endphp
    <tr class="child_{{ $booking_id }}" style="background: rgb(225, 245, 254);">
        <td></td>
        <td onclick="{{ $openTripDetails }}">
            {{ $booking_id == $trip->id ? $trip->increment_id . '-0' : $trip->increment_id }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            @if ($trip->status == 3)
                Completed
            @elseif ($trip->status == 2)
                Canceled
            @else
                Incomplete
            @endif
        </td>
        <td onclick="inspektScanReport({{ $trip->id }});" class="">
            <span class="btn-link text-blue">
                {{ $trip->vehicle_name }}
            </span>
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ !empty($trip->start_datetime) && strpos($trip->start_datetime, '0000') !== 0 ? \Carbon\Carbon::parse($trip->start_datetime)->timezone($trip->timezone ?? config('app.timezone'))->format("m/d/Y h:i A") : '--' }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ !empty($trip->end_datetime) && strpos($trip->end_datetime, '0000') !== 0 ? \Carbon\Carbon::parse($trip->end_datetime)->timezone($trip->timezone ?? config('app.timezone'))->format("m/d/Y h:i A") : '--' }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $commonService->days_between_dates($trip->start_datetime, $trip->end_datetime) }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $trip?->user?->first_name . ' ' . $trip?->user?->last_name }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ $trip->status == 3 ? $trip->end_odometer - $trip->start_odometer : 0 }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ (float) $trip->rent + (float) $trip->initial_fee + (float) $trip->extra_mileage_fee + (float) $trip->damage_fee + (float) $trip->lateness_fee + (float) $trip->uncleanness_fee }}
        </td>

        <td onclick="{{ $openTripDetails }}">
            {{ (float) $trip->insurance_amt + (float) $trip->dia_insu }}
        </td>
        <td onclick="{{ $openTripDetails }}">
            {{ (float) $trip->toll + (float) $trip->pending_toll }}
        </td>

        <td>
            @if ($trip->status == 3)
                <a href="javascript:void(0)" title="Review Images" onclick="reviewimages('{{ base64_encode($trip->id) }}')">
                    <i class='icon-clipboard3'></i>
                </a>
            @endif
            <a href="javascript:void(0)" title="Download Doc"
                onclick="return downloadBookingDoc('{{ base64_encode($trip->id) }}');">
                <i class=" icon-file-pdf"></i>
            </a>
            <a href="javascript:void(0)" title="Download Payment Receipt"
                onclick="return loadPaymentsPopup('{{ base64_encode($trip->id) }}');">
                <i class=" icon-download"></i>
            </a>
            <a href="javascript:void(0)" title="Inspeck Scan report" onclick="return inspektScanReport('{{ $trip->id }}');">
                <i class="icon-magazine"></i>
            </a>
        </td>
    </tr>
@endforeach