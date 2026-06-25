@include('partials.dispacher.paging_box', ['paginator' => $bookings, 'limit' => $limit ?? 50, 'position' => 'top'])

<em><strong>*MVR</strong>->Motor Vehicle Record, <strong>*ITH</strong>->Income Threshold, <strong>*SP</strong>->Selling
    Price, <strong>*CR</strong>->Clue Report</em>

<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
                <th align="center" class="text-center">#</th>
                <th align="center" class="text-center">Status</th>
                <th align="center" class="text-center">Vehicle#</th>
                <th align="center" class="text-center">Vehicle Source</th>
                <th align="center" class="text-center">Book Date</th>
                <th align="center" class="text-center">Start Date</th>
                <th align="center" class="text-center">Customer </th>
                <th align="center" style="text-align:center">Financing</th>
                <th align="center" class="text-center">VIN #</th>
                <th align="center" class="text-center">MVR</th>
                <th align="center" class="text-center">ITH</th>
                <th align="center" class="text-center">GPS</th>
                <th align="center" class="text-center">GPS2</th>
                <th align="center" class="text-center">CLUE</th>
                <th align="center" class="text-center">Age(Yrs)</th>
                <th align="center">Insu.</th>
                <th align="center">Docusign</th>
                <th align="center">Market</th>
                <th align="center">Checklist</th>
                <th align="center" class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $trip)
                @php
                    $renterUser = \App\Helpers\Legacy\UtilityHelper::getUserLicenceDetails($trip->renter_id);
                    $Owner = \App\Helpers\Legacy\UtilityHelper::get_User($trip->user_id);
                    $checklistele = $commonService->getLastChecklist($trip->checklists, $checklists);
                    [$statusText, $statusClass] = $readyForDealerStatus[$trip->ready_for_dealer] ?? ['Unknown', 'bg-secondary'];
                @endphp

                <tr id="tripRow{{ $trip->id }}" class="">
                    <td class="text-center">
                        <span class="{{ $statusClass }} text-highlight">
                            {{ $trip->id }}
                        </span>
                    </td>

                    <td class="text-center">
                        <a herf="javascript:void(0)" onclick="changeReservationStatus('{{ base64_encode($trip->id) }}')">
                            {{ $commonService->getReservationStatus(false, $trip->status) }}
                        </a>
                    </td>

                    <td class="text-center">
                        <a herf="javascript:void(0)" onclick="changeReservationVehicle('{{ base64_encode($trip->id) }}')">
                            {{ $trip?->vehicle?->vehicle_name }}
                        </a>
                    </td>

                    <td class="text-center">
                        {{ $Owner['first_name'] . ' ' . $Owner['last_name'] }}
                    </td>

                    <td class="text-center">
                        {{ $trip->created ? \Carbon\Carbon::parse($trip->created)->format('Y-m-d h:i A') : '' }}
                    </td>

                    <td class="text-center">
                        {{ $trip->start_datetime ? \Carbon\Carbon::parse($trip->start_datetime)->format('Y-m-d h:i A') : '' }}
                    </td>

                    <td class="text-center">
                        <a herf="javascript:void(0)"
                            onclick="getuserdetails('{{ base64_encode($trip->renter_id) }}','{{ base64_encode($trip->user_id) }}','{{ base64_encode($trip->id) }}')">
                            {{ $trip?->renter?->first_name . ' ' . $trip?->renter?->last_name  }}
                        </a>
                    </td>

                    <td class="text-center">
                        {{ $trip->buy == 1 ? 'Buy' : ($commonService->getVehicleFinancing($trip?->orderDepositRule?->financing ?? 0)) }}
                    </td>

                    <td class="text-center">
                        <a herf="javascript:void(0)"
                            onclick="getvehicledetails('{{ base64_encode($trip?->vehicle?->id) }}','{{ base64_encode($trip->id) }}')">
                            {{ $trip?->vehicle?->vin_no }}
                        </a>
                    </td>

                    <td class="text-center">
                        @if ($trip->checkr_status == 1)
                            Clear
                        @else
                            <a herf="javascript:void(0)" id="checkr_status" class="mvredit" data-type="address"
                                data-inputclass="form-control" data-pk="{{ $trip->id }}" data-value="{{ $trip->checkr_status }}"
                                data-title="Please answer the following questions">
                                {{ $commonService->getCheckrTypeValue(false, $trip->checkr_status) }}
                            </a>
                        @endif
                    </td>

                    <td class="text-center">
                        @if ($trip->income_threshold == 1)
                            Yes
                        @else
                            <a herf="javascript:void(0)" id="income_threshold" class="selectedit" data-type="select"
                                data-inputclass="form-control" data-pk="{{ $trip->id }}"
                                data-value="{{ $trip->income_threshold }}" data-title="Select status"
                                data-url="{{ url('admin/vehicle_reservations/updatelist') }}">
                                {{ ($trip->income_threshold == 3) ? 'Suspected' : ($trip->income_threshold == 2 ? 'NR' : ($trip->income_threshold ? 'Yes' : 'No')) }}
                            </a>
                        @endif
                    </td>

                    <td class="text-center">
                        {{ $trip->gps == 1 ? 'Yes' : 'No' }}
                    </td>

                    <td class="text-center">
                        {{ $trip->gps2 == 1 ? 'Yes' : 'No' }}
                    </td>

                    <td class="text-center">
                        <a herf="javascript:void(0)" id="clue_report" class="cluereport" data-type="cluereport"
                            data-inputclass="form-control" data-pk="{{ $trip->id }}" data-value="{{ $trip->clue_report }}"
                            data-title="Select status" data-url="{{ url('admin/vehicle_reservations/updatelist') }}">
                            {{ $trip->clue_report == 1 ? 'Clear' : 'Fail' }}
                        </a>
                    </td>

                    <td class="text-center">
                        @if ($renterUser['dob'] != '' && $renterUser['dob'] != NULL)
                            {{ $commonService->years_between_dates($renterUser['dob'], date('Y-m-d')) }}
                        @else
                            N/A
                        @endif
                    </td>

                    <td class="text-center">
                        <a herf="javascript:void(0)" onclick="loadInsurancePopUp('{{ base64_encode($trip->id) }}')">
                            <i class=" icon-menu2"></i>
                        </a>
                    </td>

                    <td class="text-center">
                        @if (in_array($trip?->orderDepositRule?->insurance_payer, [3, 4, 5, 6]))
                            <a herf="javascript:void(0)" id="docusign" class="gpsedit" data-type="select"
                                data-inputclass="form-control" data-pk="{{ $trip->id }}" data-value="{{ $trip->docusign }}"
                                data-title="Select status" data-url="{{ url('admin/vehicle_reservations/updatelist') }}">
                                {{ ($trip->docusign == 2) ? 'NR' : (($trip->docusign == 1) ? 'Yes' : 'No') }}
                            </a>
                        @else
                            NR
                        @endif
                    </td>

                    <td class="text-center">
                        <a href="{{ url('/admin/users/add/' . base64_encode($trip->renter_id)) }}"
                            title="Edit Driver License state" target="_blank">
                            {{ $trip->renter?->state ?: ($renterUser['licence_state'] ?? 'N/A') }}
                        </a>
                    </td>

                    <td class="text-left">
                        <a href="javascript:void(0)" class="text" title="Status Checklist"
                            onclick="return loadStatusChecklistPopup('{{ base64_encode($trip->id) }}');">
                            {{ $checklistele }}
                        </a>
                    </td>
                    <td>
                        <span class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-position="left"
                                aria-expanded="true">
                                <i class="icon-cog7"></i>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-solid pull-right">
                                <li>
                                    <a href="javascript:void(0)" title="Status Logs"
                                        onclick="return vehicleReservationLog('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-bubble-dots3"></i>
                                        Status Logs
                                    </a>
                                </li>

                                @if ($trip->ready_for_dealer == 0)
                                    <li>
                                        <a href="{{ url('/admin/vehicle_reservations/pushToDealer/' . base64_encode($trip->id) . '/1') }}"
                                            title="Ready For Dealer"
                                            onclick="return confirm('Are you sure this booking is ready for dealer?')">
                                            <i class="icon-drag-left-right"></i>
                                            Ready For Dealer
                                        </a>
                                    </li>
                                @else
                                    <li>
                                        <a href="{{ url('/admin/vehicle_reservations/pushToDealer/' . base64_encode($trip->id) . '/0') }}"
                                            title="Pull From Dealer"
                                            onclick="return confirm('Are you sure this booking is pulled from dealer?')">
                                            <i class="icon-drag-right"></i>
                                            Pull From Dealer
                                        </a>
                                    </li>
                                @endif

                                @if ($trip->buy == 0)
                                    <li>
                                        <a href="javascript:void(0)" title="Accept"
                                            onclick="return createVehicleReservation('{{ base64_encode($trip->id) }}');">
                                            <i class="glyphicon glyphicon-ok-circle"></i>
                                            Activate
                                        </a>
                                    </li>
                                @endif

                                <li>
                                    <a href="javascript:void(0)" title="Change Date"
                                        onclick="return changeDatetime('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-calendar3"></i>
                                        Change Date
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)" class="text-danger" title="Cancel"
                                        onclick="return cancelReservation('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-cancel-square"></i>
                                        Cancel
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)" class="text" title="Capture Payment"
                                        onclick="return captureVehicleReservationPayment('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-coins"></i>
                                        Capture Payment
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)" class="text" title="Insurance Ticket"
                                        onclick="return getReservationInsuranceDoc('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-magazine"></i>
                                        Insurance Ticket
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)" class="text" title="Agreement Doc"
                                        onclick="return getReservationAgreementDoc('{{ $trip->id }}');">
                                        <i class="icon-file-pdf"></i>
                                        Agreement Doc
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ url('/admin/booking_reviews/reservationreview/' . base64_encode($trip->id)) }}"
                                        class="text" title="Beginning Condition Report">
                                        <i class="glyphicon glyphicon-list-alt"></i>
                                        Beginning Condition Report
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ url('/admin/vehicle_reservations/goalrecalculate/' . base64_encode($trip->orderDepositRule?->id)) }}"
                                        title="Edit Goal Calculations"
                                        onclick="return confirm('Are you sure you want to edit this booking goal calculations?')">
                                        <i class="icon-cog3"></i>
                                        Edit Goal Calculations
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)" class="text" title="Status Checklist"
                                        onclick="return loadStatusChecklistPopup('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-stack-check"></i>
                                        Status Checklist
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)" class="{{ $statusClass }}" title="Vehicle Selling Options"
                                        onclick="return vehicleSellingOpionsPopup('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-gear"></i>
                                        Vehicle Selling Options
                                    </a>
                                </li>
                            </ul>
                        </span>
                    </td>
                </tr>
            @empty
                <tr id="set_hide">
                    <th colspan="12">No Booking Available!</th>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $bookings, 'limit' => $limit ?? 50])