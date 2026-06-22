@php
    $trip ??= [];
    $renterUser = \App\Helpers\Legacy\UtilityHelper::getUserLicenceDetails(data_get($trip, 'renter_id'));
    $Owner = \App\Helpers\Legacy\UtilityHelper::get_User(data_get($trip, 'user_id'));
@endphp

<td style="text-align:center;">
    {{ data_get($trip, 'id') }}
</td>

<td style="text-align:center;">
    <a href="javascript:void(0)"  onclick="changeReservationStatus('{{ base64_encode(data_get($trip, 'id')) }}')">
        {{ $commonService->getReservationStatus(false, data_get($trip, 'status')) }}
    </a>
</td>

<td style="text-align:center;">
    <a href="javascript:void(0)"  onclick="changeReservationVehicle('{{ base64_encode(data_get($trip, 'id')) }}')">
        {{ data_get($trip, 'vehicle.vehicle_name')}}
    </a>
</td>

<td style="text-align:center;">
    {{ trim((data_get($Owner, 'first_name', '')) . ' ' . (data_get($Owner, 'last_name', ''))) }}
</td>

<td style="text-align:center;">
    {{ \Carbon\Carbon::parse(data_get($trip, 'start_datetime'))->format('Y-m-d h:i A') }}
</td>

<td style="text-align:center;">
    <a href="javascript:void(0)" 
        onclick="getuserdetails('{{ base64_encode(data_get($trip, 'renter_id')) }}','{{ base64_encode(data_get($trip, 'user_id')) }}','{{ base64_encode(data_get($trip, 'id')) }}')">
        {{ data_get($trip, 'renter.first_name', '') . ' ' . data_get($trip, 'renter.last_name', '') }}
    </a>
</td>

<td style="text-align:center;">
    {{ (int) data_get($trip, 'buy', 0) === 1 ? "Buy" : (data_get($trip, 'pto') ? "RTO" : "Rent") }}
</td>

<td style="text-align:center;">
    <a href="javascript:void(0)" 
        onclick="getvehicledetails('{{ base64_encode(data_get($trip, 'vehicle.id')) }}','{{ base64_encode(data_get($trip, 'id')) }}')">
        {{ data_get($trip, 'vehicle.vin_no')}}
    </a>
</td>

<td style="text-align:center;">
    {{data_get($trip, 'vehicle.msrp')}}
</td>

<td style="text-align:center;">
    {{ data_get($trip, 'orderDepositRule.downpayment') }}
</td>

<td style="text-align:center;">
    @if ((int) data_get($trip, 'checkr_status', 0) === 1)
        Clear
    @else
        <a href="#" class="mvredit" data-type="select" data-inputclass="form-control" data-pk="{{ data_get($trip, 'id') }}"
            data-value="{{ data_get($trip, 'checkr_status') }}" data-title="Select status" id="checkr_status"
            data-url="{{ url('admin/vehicle_reservations/updatelist') }}">
            {{ $commonService->getCheckrTypeValue(false, data_get($trip, 'checkr_status')) }}
        </a>
    @endif
</td>

<td style="text-align:center;">
    @if ((int) data_get($trip, 'income_threshold', 0) === 1)
        Yes
    @else
        <a href="#" class="selectedit" data-type="select" data-inputclass="form-control"
            data-pk="{{ data_get($trip, 'id') }}" data-value="{{ data_get($trip, 'income_threshold') }}"
            data-title="Select status" id="income_threshold" data-url="{{ url('admin/vehicle_reservations/updatelist') }}">
            {{ (int) data_get($trip, 'income_threshold', 0) === 3 ? "Suspected" : ((int) data_get($trip, 'income_threshold', 0) === 2 ? "NR" : (data_get($trip, 'income_threshold', 0) ? "Yes" : "No")) }}
        </a>
    @endif
</td>

<td style="text-align:center;">
    {{ (int) data_get($trip, 'gps', 0) === 1 ? "Yes" : "No" }}
</td>

<td style="text-align:center;">
    @if ((int) data_get($trip, 'clue_report', 0) === 1)
        Yes
    @else
        <a href="#" class="selectedit" data-type="select" data-inputclass="form-control"
            data-pk="{{ data_get($trip, 'id') }}" data-value="{{ data_get($trip, 'clue_report') }}"
            data-title="Select status" id="clue_report" data-url="{{ url('admin/vehicle_reservations/updatelist') }}">
            {{ (int) data_get($trip, 'clue_report', 0) === 2 ? "NR" : (data_get($trip, 'clue_report', 0) ? "Yes" : "No") }}
        </a>
    @endif
</td>

<td style="text-align:center;">
    @if(!empty(data_get($renterUser, 'dob')))
        {{ \Carbon\Carbon::parse(data_get($renterUser, 'dob'))->age }}
    @else
        N/A
    @endif
</td>

<td class="text-center">
    <a href="javascript:void(0)"  onclick="loadInsurancePopUp({{ data_get($trip, 'id') }})"><i class="icon-menu2"></i></a>
</td>

<td class="text-center">
    @if (in_array((int) (data_get($trip, 'orderDepositRule.insurance_payer')), [3, 4, 5, 6]))
        <a href="#" class="gpsedit" data-type="select" data-inputclass="form-control" data-pk="{{ data_get($trip, 'id') }}"
            data-value="{{ data_get($trip, 'docusign') }}" data-title="Select status" id="docusign"
            data-url="{{ url('admin/vehicle_reservations/updatelist') }}">
            {{ (int) data_get($trip, 'docusign', 0) === 2 ? "NR" : ((int) data_get($trip, 'docusign', 0) === 1 ? "Yes" : "No") }}
        </a>
    @else
        NR
    @endif
</td>

<td class="text-center">
    <a href="{{ url('admin/users/add' . base64_encode(data_get($trip, 'renter_id'))) }}"
        title="Edit Driver License state" target="_blank">
        {{ data_get($renterState, 'licence_state') }}
    </a>
</td>

<td class="text-left">
    <a href="javascript:void(0)" class="text" title="Status Checklist"
        onclick="return loadStatusChecklistPopup('{{ base64_encode(data_get($trip, 'id')) }}');">
        {{ $checklistele }}
    </a>
</td>

<td>
    <span class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-position="left" aria-expanded="true">
            <i class="icon-cog7"></i>
            <span class="caret"></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-solid pull-right">
            <li>
                <a href="javascript:void(0)" title="Status Logs"
                    onclick="return vehicleReservationLog('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-bubble-dots3"></i> Status Logs
                </a>
            </li>
            @if ((int) data_get($trip, 'buy') === 0)
                <li>
                    <a href="javascript:void(0)" title="Accept"
                        onclick="return createVehicleReservation('{{ base64_encode(data_get($trip, 'id')) }}');">
                        <i class="glyphicon glyphicon-ok-circle"></i> Activate
                    </a>
                </li>
            @endif
            <li>
                <a href="javascript:void(0)" title="Change Date"
                    onclick="return changeDatetime('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-calendar3"></i> Change Date
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" class="text-danger" title="Cancel"
                    onclick="return cancelReservation('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-cancel-square"></i> Cancel
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" class="text" title="Capture Payment"
                    onclick="return captureVehicleReservationPayment('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-coins"></i> Capture Payment
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" class="text" title="Insurance Ticket"
                    onclick="return getReservationInsuranceDoc('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-magazine"></i> Insurance Ticket
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" class="text" title="Agreement Doc"
                    onclick="return getReservationAgreementDoc('{{ data_get($trip, 'id') }}');">
                    <i class="icon-file-pdf"></i> Agreement Doc
                </a>
            </li>
            <li>
                <a href="{{ url('admin/booking_reviews/reservationreview' . base64_encode(data_get($trip, 'id'))) }}"
                    class="text" title="Beginning Condition Report">
                    <i class="glyphicon glyphicon-list-alt"></i> Beginning Condition Report
                </a>
            </li>
            <li>
                <a href="{{ url('admin/vehicle_reservations/goalrecalculate' . base64_encode(data_get($trip, 'orderDepositRule.id'))) }}"
                    title="Edit Goal Calculations"
                    onclick="return confirm('Are you sure you want to edit this booking goal calculations?')">
                    <i class="icon-cog3"></i> Edit Goal Calculations
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" class="text" title="Status Checklist"
                    onclick="return loadStatusChecklistPopup('{{ base64_encode(data_get($trip, 'id')) }}');">
                    <i class="icon-stack-check"></i> Status Checklist
                </a>
            </li>
        </ul>
    </span>
</td>