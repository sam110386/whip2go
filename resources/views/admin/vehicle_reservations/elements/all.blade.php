@include('partials.dispacher.paging_box', ['paginator' => $bookings, 'limit' => $limit ?? 50, 'position' => 'top'])

<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
                <th align="center" style="text-align:center;">#</th>
                <th align="center" style="text-align:center;">Status</th>
                <th align="center" style="text-align:center;">Vehicle#</th>
                <th align="center" style="text-align:center;">Dealer</th>
                <th align="center" style="text-align:center;">Start Date</th>
                <th align="center" style="text-align:center;">Customer </th>
                <th align="center" style="text-align:center;">VIN #</th>
                <th align="center" style="text-align:center;">Selling Price</th>
                <th align="center" style="text-align:center;" width="50px">Cancel Note</th>
                <th align="center" style="text-align:center;">Status</th>
                <th align="center" style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $trip)
                @php
                    $renterUser = \App\Helpers\Legacy\UtilityHelper::get_User($trip->renter_id);
                    $expired = ($trip->status == 0 && \Carbon\Carbon::parse($trip->start_datetime)->isBefore(now()->subDays(7))) ? 1 : 0;
                @endphp

                <tr id="tripRow{{ $trip->id }}" class="{{ $expired ? 'danger' : '' }}">
                    <td class="text-center">
                        {{ $trip->id }}
                    </td>

                    <td class="text-center">
                        {{ $commonService->getReservationStatus(false, $trip->status) }}
                    </td>

                    <td class="text-center">
                        {{ $trip?->vehicle?->vehicle_name }}
                    </td>

                    <td class="text-center">
                        {{ $trip?->owner?->first_name . ' ' . $trip?->owner?->last_name}}
                    </td>

                    <td class="text-center">
                        {{ $trip->start_datetime ? \Carbon\Carbon::parse($trip->start_datetime)->format('Y-m-d h:i A') : '' }}
                    </td>

                    <td class="text-center">
                        {{ $renterUser['first_name'] . ' ' . $renterUser['last_name']  }}
                    </td>

                    <td class="text-center">
                        {{ $trip?->vehicle?->vin_no }}
                    </td>

                    <td class="text-center">
                        {{ $trip?->vehicle?->msrp }}
                    </td>

                    <td class="text-center">
                        {{ $trip->cancel_note }}
                    </td>

                    <td class="text-center">
                        @if ($trip->status == 0)
                            New
                        @elseif ($trip->status == 1)
                            Approved
                        @elseif ($trip->status == 2)
                            Canceled
                        @endif
                    </td>

                    <td class="text-center">
                        <a href="javascript:void(0)" title="Status Logs"
                            onclick="return vehicleReservationLog('{{ base64_encode($trip->id) }}');">
                            <i class="icon-bubble-dots3"></i>
                        </a>
                        @if ($expired)
                            <a href="javascript:void(0)" class="text-danger" title="Cancel"
                                onclick="return cancelReservation('{{ base64_encode($trip->id) }}');">
                                <i class='icon-cancel-square'></i>
                            </a>
                        @endif
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