@include('partials.dispacher.paging_box', ['paginator' => $tripLog, 'limit' => $limit ?? 100, 'position' => 'top'])

<em><strong>*EMF</strong>->Extra Mileage Fee, <strong>*EMINS</strong>->EMF Insurance <strong>*Toll(P)</strong>->Pending
    Toll, <strong>*Cal. Rent</strong>->Calculated Rent,<strong>*Ini. Fee</strong>->Scheduled Fee</em>

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
    <thead>
        <tr>
            <th style="text-align:center;"> Booking# </th>
            <th style="text-align:center;"> Insu. By </th>
            <th style="text-align:center;"> Vehicle </th>
            <th style="text-align:center;"> Dealer </th>
            <th style="text-align:center;"> Begin Date </th>
            <th style="text-align:center;"> Start Date </th>
            <th style="text-align:center;"> End Date </th>
            <th style="text-align:center;"> Customer </th>
            <th style="text-align:center;"> Cal. Rent </th>
            <th style="text-align:center;"> EMF </th>
            <th style="text-align:center;"> EMINS </th>
            <th style="text-align:center;"> Late Fee </th>
            <th style="text-align:center;"> Deposit </th>
            <th style="text-align:center;"> Insu. Fee </th>
            <th style="text-align:center;"> Ini. Fee </th>
            <th style="text-align:center;"> Toll </th>
            <th style="text-align:center;"> Toll(P) </th>
            <th style="text-align:center;"> Action </th>
        </tr>
    </thead>
    <tbody>
        @forelse ($tripLog as $trip)
            @php
                $class = $commonService->checkAutoRenew($trip->renter_id, $trip->end_datetime);

                if (
                    $trip->payment_status == 2 ||
                    $trip->dpa_status == 2 ||
                    $trip->insu_status == 2 ||
                    $trip->infee_status == 2
                ) {
                    $class .= " text-warning-700";
                }

                if ($trip->checkr_status) {
                    $class .= " alpha-violet";
                }

            @endphp
            <tr id="tripRow{{ $trip->id }}" class="anchor {{ $class }} {{ $trip->status }}_status">
                <td onclick="openBookingDetails('{{ $trip->id }}')" style="text-align:center;">
                    {{ $trip->increment_id }}
                </td>

                <td style="text-align:center;">
                    {{ $trip->insurance_payer ? $commonService->getInsurancePayer($trip->insurance_payer) : 'N/A' }}
                </td>

                <td onclick="inspektScanReport('{{ $trip->id }}');" class="">
                    <span class="btn-link text-blue">
                        {{ $trip->vehicle_name }}
                    </span>
                </td>

                <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                    {{ !empty($trip->user_id) ? $trip->owner->first_name . ' ' . $trip->owner->last_name : '' }}
                </td>

                <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                    {{ \Carbon\Carbon::parse($commonService->getParentOrderStartDate($trip->parent_id, $trip->start_datetime))->timezone($trip->timezone)->format('Y-m-d h:i A') }}
                </td>

                <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                    {{ \Carbon\Carbon::parse($trip->start_datetime)->timezone($trip->timezone)->format('Y-m-d h:i A') }}
                </td>

                <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                    {{ \Carbon\Carbon::parse($trip->end_datetime)->timezone($trip->timezone)->format('Y-m-d h:i A') }}
                </td>

                <td onclick="openUserTransactions('{{ $trip->renter_id }}','{{ $trip->id }}','{{ $trip->currency }}');"
                    style="text-align:center;">
                    <span class="btn-link text-blue">
                        {{ !empty($trip->renter_id) ? $trip->driver->first_name . ' ' . $trip->driver->last_name : '' }}
                    </span>
                </td>

                @if ($trip->payment_status == 2)
                    <td class="text-danger  anchortag" onclick="retryrentalfee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->rent + $trip->tax + $trip->dia_fee }}{{ '-D' }}
                    </td>
                @else
                    <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->rent + $trip->tax + $trip->dia_fee }}
                        @if ($trip->payment_status == 2)
                            {{ '-D' }}
                        @elseif ($trip->payment_status == 1)
                            {{ '-P' }}
                        @else
                            {{ '-UP' }}
                        @endif
                    </td>
                @endif

                @if ($trip->emf_status == 2)
                    <td class="text-danger  anchortag" onclick="retryemf('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->extra_mileage_fee + $trip->emf_tax . '-D' }}
                    </td>
                @else
                    <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->extra_mileage_fee + $trip->emf_tax }}
                        {{ $trip->emf_status == 1 ? '-P' : '-UP' }}
                    </td>
                @endif

                @if ($trip->dia_insu_status == 2)
                    <td class="text-danger  anchortag" onclick="retrydiainsurancefee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->dia_insu . '-D' }}
                    </td>
                @else
                    <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->dia_insu }}
                        @if ($trip->dia_insu_status == 2)
                            {{ '-D' }}
                        @elseif ($trip->dia_insu_status == 1)
                            {{ '-P' }}
                        @else
                            {{ '-UP' }}
                        @endif
                    </td>
                @endif

                @if ($trip->lateness_fee_status == 2)
                    <td class="text-danger  anchortag" onclick="retrylatefee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->lateness_fee . '-D' }}
                    </td>
                @else
                    <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->lateness_fee }}
                        @if ($trip->lateness_fee_status == 1)
                            {{ '-P' }}
                        @else
                            {{ '-UP' }}
                        @endif
                    </td>
                @endif

                @if ($trip->dpa_status == 2)
                    <td class="text-danger  anchortag" onclick="retrydepositfee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->deposit . '-D' }}
                        ({{ $commonService->getBookingTotalDeposit($trip->parent_id ?: $trip->id) }})
                    </td>
                @else
                    <td style="text-align:center;">
                        {{ $trip->deposit }}
                        @if ($trip->dpa_status == 2)
                            {{ '-D' }}
                        @elseif ($trip->dpa_status == 1)
                            {{ '-P' }}
                        @else
                            {{ '-UP' }}
                        @endif
                        ({{ $commonService->getBookingTotalDeposit($trip->parent_id ?: $trip->id) }})
                    </td>
                @endif

                @if ($trip->insu_status == 2)
                    <td class="text-danger  anchortag" onclick="retryinsurancefee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->insurance_amt . '-D' }}
                        ({{ $commonService->getBookingTotalInsurance($trip->parent_id ?: $trip->id) }})
                    </td>
                @else
                    <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->insurance_amt }}
                        @if ($trip->insu_status == 2)
                            {{ '-D' }}
                        @elseif ($trip->insu_status == 1)
                            {{ '-P' }}
                        @else
                            {{ '-UP' }}
                        @endif
                        ({{ $commonService->getBookingTotalInsurance($trip->parent_id ?: $trip->id) }})
                    </td>
                @endif

                @if ($trip->infee_status == 2)
                    <td class="text-danger  anchortag" onclick="retryinitialfee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->initial_fee + $trip->initial_fee_tax . '-D' }}
                        ({{ $commonService->getBookingTotalInitialFee($trip->parent_id ?: $trip->id) }})
                    </td>
                @else
                    <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->initial_fee + $trip->initial_fee_tax }}
                        @if ($trip->infee_status == 2)
                            {{ '-D' }}
                        @elseif ($trip->infee_status == 1)
                            {{ '-P' }}
                        @else
                            {{ '-UP' }}
                        @endif
                        ({{ $commonService->getBookingTotalInitialFee($trip->parent_id ?: $trip->id) }})
                    </td>
                @endif

                <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                    {{ $trip->toll }}
                </td>

                @if ($trip->pending_toll > 0)
                    <td class="text-danger  anchortag" onclick="retrytollfee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->pending_toll . '-D' }}
                    </td>
                @else
                    <td onclick="openBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->pending_toll }}
                    </td>
                @endif

                <td>
                    <span class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-position="left"
                            aria-expanded="true">
                            <i class="icon-cog7"></i>
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-solid pull-right">
                            @if (!empty($trip->delivery))
                                <li>
                                    <a href="javascript:void(0)" title="Pickup/ Delivery"
                                        onclick="return pickupDelivery('{{ base64_encode($trip->delivery) }}', {{ $trip->id }});">
                                        <i class="icon-steering-wheel"></i> Pickup/ Delivery
                                    </a>
                                </li>
                            @endif

                            @if ($trip->status == 0 && $trip->checkr_status == 1)
                                <li>
                                    <a href="javascript:void(0)" title="Checkr Approve"
                                        onclick="return checkrApprove('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-thumbs-up3"></i> Checkr Approve
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" title="Checkr Disapprove"
                                        onclick="return checkrDisapprove('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-thumbs-down3"></i> Checkr Disapprove
                                    </a>
                                </li>
                            @endif

                            @if ($trip->status == 0 && $trip->checkr_status == 0)
                                <li>
                                    <a href="javascript:void(0)" title="Start"
                                        onclick="return startBooking('{{ base64_encode($trip->id) }}');">
                                        <i class="glyphicon glyphicon-ok-circle"></i> Start Booking
                                    </a>
                                </li>
                            @endif

                            @if (($trip->status == 0 || $trip->status == 1) && $trip->checkr_status == 0)
                                <li>
                                    <a href="javascript:void(0)" title="Complete"
                                        onclick="return completeBooking('{{ base64_encode($trip->id) }}');">
                                        <i class="glyphicon glyphicon-saved"></i> Complete/Renew
                                    </a>
                                </li>
                            @endif

                            @if ($trip->status == 0 && $trip->checkr_status == 0)
                                <li>
                                    <a href="javascript:void(0)" title="Cancel"
                                        onclick="return cancelBooking('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-trash"></i> Cancel Booking
                                    </a>
                                </li>
                            @endif

                            @if ($trip->status == 1)
                                <li>
                                    <a href="javascript:void(0)" title="Download Doc"
                                        onclick="return downloadBookingDoc('{{ base64_encode($trip->id) }}', 1);">
                                        <i class="icon-magazine"></i> Download Doc
                                    </a>
                                </li>
                            @endif

                            <li>
                                <a href="javascript:void(0)" title="Transaction logs"
                                    onclick="return gettransactionlogs('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-coin-dollar"></i> Transaction logs
                                </a>
                            </li>

                            @if ($trip->status != 0)
                                <li>
                                    <a href="javascript:void(0)" title="Start"
                                        onclick="return OpenIntercomActionPopUp('{{ base64_encode($trip->renter_id) }}');">
                                        <i class="glyphicon glyphicon-ok-circle"></i> Check Intercom API Result
                                    </a>
                                </li>
                            @endif

                            <li>
                                <a href="javascript:void(0)" title="Message History"
                                    onclick="return getmessagehistory('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-comments"></i> Message History
                                </a>
                            </li>

                            @if ($trip->checkr_status == 0)
                                <li>
                                    <a href="{{ url('admin/bookings/edit/' . base64_encode($trip->id)) }}" title="Update">
                                        <i class="icon-pencil"></i> Booking Edit
                                    </a>
                                </li>
                            @endif

                            <li>
                                <a href="javascript:void(0)" title="Extend Vehicle Lock Time"
                                    onclick="return changeVehicleLockTime('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-alarm"></i> Extend Vehicle Lock Time
                                </a>
                            </li>

                            <li>
                                <a href="javascript:void(0)" title="Edit Pyament Rules"
                                    onclick="return updateOrderDepositRules('{{ !empty($trip->parent_id) ? base64_encode($trip->parent_id) : base64_encode($trip->id) }}');">
                                    <i class="icon-price-tag"></i> Edit Pyament Rules
                                </a>
                            </li>

                            <li>
                                <a href="javascript:void(0)" title="Edit Vehicle GPS Setting"
                                    onclick="return changeVehicleGps('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-car"></i> Edit Vehicle GPS Setting
                                </a>
                            </li>

                            <li>
                                <a href="javascript:void(0)" title="Update Odometer"
                                    onclick="return updateOdometer('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-meter2"></i> Update Odometer
                                </a>
                            </li>

                            <li>
                                <a href="{{ url('admin/bookings/goalrecalculate/' . (!empty($trip->parent_id) ? base64_encode($trip->parent_id) : base64_encode($trip->id))) }}"
                                    title="Edit Goal Calculations"
                                    onclick="return confirm('Are you sure you want to edit this booking goal calculations?')">
                                    <i class="icon-cog3"></i> Edit Goal Calculations
                                </a>
                            </li>

                            <li>
                                <a href="javascript:void(0)" title="Schedule Extend time"
                                    onclick="return loadextendtime('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-watch2"></i>Schedule Extend time
                                </a>
                            </li>

                            <li>
                                <a href="javascript:void(0)" title="Partial Payment"
                                    onclick="return partialPayment('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-percent"></i> Partial Payment
                                </a>
                            </li>

                            <li>
                                <a href="javascript:void(0)" title="Insurance"
                                    onclick="return bookingInsurancePopup('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-magazine"></i> Insurance Tasks
                                </a>
                            </li>

                            <li>
                                <a href="javascript:void(0)" title="Booking Notes"
                                    onclick="return getBookingNotes('{{ base64_encode($trip->id) }}');">
                                    <i class="icon-pencil7"></i> Booking Notes
                                </a>
                            </li>
                        </ul>
                    </span>
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="18" style="text-align:center;">No orders found.</td>
            </tr>
        @endforelse
    </tbody>
</table>


@include('partials.dispacher.paging_box', ['paginator' => $tripLog, 'limit' => $limit ?? 100])