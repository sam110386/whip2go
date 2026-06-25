@include('partials.dispacher.paging_box', ['paginator' => $tripLog, 'limit' => $limit ?? 100, 'position' => 'top'])

<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
    <thead>
        <tr> 
            @include('partials.dispacher.sortable_header', [
                'columns' => [
                    ['title' => 'Booking#', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Vehicle#', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Passtime Status', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Moving Status', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => '# of days late', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Start Date', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'End Date', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Customer', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Cal. Rent', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Deposit', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Insu. Fee', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Ini. Fee', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Extended Date', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Note', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Cycle Ext(s)', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Total Ext(s)', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Action', 'sortable' => false, 'style' => 'text-align:center;']
                ]
            ])
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

               $moving = $commonService->getVehicleMovementFromHistory($trip->vehicle_id);
            @endphp

            <tr id="tripRow{{ $trip->id }}" class="anchor {{ $class }} {{ $trip->status }}_status">
                <td onclick="openOverDueBookingDetails('{{ $trip->id }}')" style="text-align:center;">
                    {{ $trip->increment_id }}
                </td>

                <td onclick="openOverDueBookingDetails('{{ $trip->id }}')">
                    {{ $trip->vehicle_name }}
                </td>

                <td onclick="openOverDueBookingDetails('{{ $trip->id }}')">
                    {{ ($trip?->vehicle?->passtime_status == 0) ? "Disabled" : ($trip?->vehicle?->passtime_status == 2 ? "Temp Disabled" : "Enabled") }}
                </td>

                <td onclick="openOverDueBookingDetails('{{ $trip->id }}')">
                    {{ $moving }}
                </td>

                <td onclick="openOverDueBookingDetails('{{ $trip->id }}')">
                    {{ $trip->due_days }}
                </td>

                <td onclick="openOverDueBookingDetails('{{ $trip->id }}')" style="text-align:center;">
                    {{ \Carbon\Carbon::parse($trip->start_datetime)->timezone($trip->timezone)->format('Y-m-d h:i A') }}
                </td>

                <td onclick="openOverDueBookingDetails('{{ $trip->id }}')" style="text-align:center;">
                    {{ \Carbon\Carbon::parse($trip->end_datetime)->timezone($trip->timezone)->format('Y-m-d h:i A') }}
                </td>

                <td onclick="openOverDueBookingDetails('{{ $trip->id }}')" style="text-align:center;">
                    {{ !empty($trip->renter_id) ? \App\Helpers\Legacy\UtilityHelper::get_UserFirstLastName($trip->renter_id) : '' }}
                </td>

                @if ($trip->payment_status == 2)
                    <td class="text-danger  anchortag" onclick="retryrentalfee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ ($trip->rent + $trip->tax) . '-D' }}
                    </td>
                @else
                    <td onclick="openOverDueBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->rent + $trip->tax }}
                        @if ($trip->payment_status == 2)
                            {{ '-D' }}
                        @elseif ($trip->payment_status == 1)
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
                    </td>
                @else
                    <td onclick="openOverDueBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->deposit }}
                        @if ($trip->dpa_status == 2)
                            {{ '-D' }}
                        @elseif ($trip->dpa_status == 1)
                            {{ '-P' }}
                        @else
                            {{ '-UP' }}
                        @endif
                    </td>
                @endif

                @if ($trip->insu_status == 2)
                    <td class="text-danger  anchortag" onclick="retryinsurancefee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->insurance_amt . '-D' }}
                    </td>
                @else
                    <td onclick="openOverDueBookingDetails('{{ $trip->id }}');" style="text-align:center;">
                        {{ $trip->insurance_amt }}
                        @if ($trip->insu_status == 2)
                            {{ '-D' }}
                        @elseif ($trip->insu_status == 1)
                            {{ '-P' }}
                        @else
                            {{ '-UP' }}
                        @endif
                    </td>
                @endif

                @if ($trip->infee_status == 2)
                    <td class="text-danger  anchortag" onclick="retryinitialfee('{{ base64_encode($trip->id) }}');"
                        style="text-align:center;">
                        {{ $trip->initial_fee + $trip->initial_fee_tax . '-D' }}
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
                    </td>
                @endif

                <td style="text-align:center;">
                    {{ isset($trip->orderExtlogs[0]) && $trip->orderExtlogs[0]->ext_date ? \Carbon\Carbon::parse($trip->orderExtlogs[0]->ext_date)->timezone(session('default_timezone', 'UTC'))->format('m/d/Y h:i A') : '--' }}
                </td>

                <td style="text-align:center;">
                    {{ $trip->orderExtlogs[0]->note ?? '-' }}
                </td>

                <td style="text-align:center;">
                    <a href="javascript:void(0)" onclick="ShowPastDueLogs({{ $trip->id }})">
                        {{ \App\Helpers\Legacy\ReportHelper::getExtCount($trip->id) }}
                    </a>
                </td>

                <td style="text-align:center;">
                    <a href="javascript:void(0)" onclick="ShowPastDueLogs({{ $trip->parent_id ?? $trip->id }},true)">
                        {{ \App\Helpers\Legacy\ReportHelper::getExtParentWithSiblingCount($trip->parent_id ?? $trip->id) }}
                    </a>
                </td>

                 <td>
                    @if ($trip->status == 0)
                        <a href="javascript:void(0)" title = "Start" onclick="return startBooking('{{ base64_encode($trip->id) }}');">
                            <i class="glyphicon glyphicon-ok-circle"></i>
                        </a>
                    @endif

                    @if ($trip->status == 0 || $trip->status == 1)
                        <a href="javascript:void(0)" title = "Complete" onclick="return completeBooking('{{ base64_encode($trip->id) }}');">
                            <i class="glyphicon glyphicon-saved"></i>
                        </a>
                    @endif

                    @if ($trip->status == 0)
                        <a href="javascript:void(0)" title = "Cancel" onclick="return cancelBooking('{{ base64_encode($trip->id) }}');">
                            <img src="{{ legacy_asset('img/b_drop.png') }}" alt = "Cancel" style = "border:0px;" />
                        </a>
                    @endif

                    @if ($trip->status == 1)
                        <a href="javascript:void(0)" title = "Download Doc" onclick="return downloadBookingDoc('{{ base64_encode($trip->id) }}');">
                            <i class="icon-magazine"></i>
                        </a>
                    @endif

                    <a href="javascript:void(0)" title = "Message History" onclick="return getmessagehistory('{{ base64_encode($trip->id) }}');">
                        <i class="icon-comments"></i>
                    </a>

                    <a href="javascript:void(0)" title = "Transaction logs" onclick="return gettransactionlogs('{{ base64_encode($trip->id) }}');">
                        <i class="icon-coin-dollar"></i>
                    </a>
                    
                    <a href="javascript:void(0)" title = "Extend Vehicle Lock Time" onclick="return changeVehicleLockTime('{{ base64_encode($trip->id) }}');">
                        <i class="icon-alarm"></i>
                    </a>

                </td>
            </tr>
        @empty
            <tr id="set_hide">
                <th colspan="9">No Past Due Booking Available!</th>
            </tr>
        @endforelse
    </tbody>
</table>


@include('partials.dispacher.paging_box', ['paginator' => $tripLog, 'limit' => $limit ?? 100])