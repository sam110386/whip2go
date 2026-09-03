@if (isset($nonreviews) && method_exists($nonreviews, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $nonreviews, 'limit' => $limit, 'position' => 'top'])
@endif

<div class="table-responsive">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table  table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;"> Booking# </th>
                <th style="text-align:center;"> Vehicle# </th>
                <th style="text-align:center;"> Start Date </th>
                <th style="text-align:center;"> End Date </th>
                <th style="text-align:center;"> Customer </th>
                <th style="text-align:center;"> Cal. Rent </th>
                <th style="text-align:center;"> Deposit </th>
                <th style="text-align:center;"> Insu. Fee </th>
                <th style="text-align:center;"> Ini. Fee </th>
                <th style="text-align:center;"> Actions </th>
            </tr>
        </thead>
        <tbody>
            @forelse($nonreviews as $trip)
                @php
                    $class = ($trip->payment_status == 2) ? "text-warning-700" : '';
                @endphp

                <tr id="tripRow{{  $trip->id }}" class="{{  $class }} {{ $trip->status . '_status' }}">
                    <td style="text-align:center;">
                        {{  $trip->increment_id }}
                    </td>

                    <td style="text-align:center;">
                        {{  $trip?->vehicle?->vehicle_unique_id ?? '' }}
                    </td>

                    <td style="text-align:center;">
                        {{ \Carbon\Carbon::parse($trip->start_datetime)->format('Y-m-d h:i A') }}
                    </td>

                    <td style="text-align:center;">
                        {{ \Carbon\Carbon::parse($trip->end_datetime)->format('Y-m-d h:i A') }}
                    </td>

                    <td style="text-align:center;">
                        {{ !empty($trip->renter_id) ? \App\Helpers\Legacy\UtilityHelper::get_UserFirstLastName($trip->renter_id) : '' }}
                    </td>

                    <td style="text-align:center;">
                        {{ number_format((float) $trip->rent + (float) $trip->tax, 2) }}

                        @if($trip->payment_status == 2)
                            {{ "-D " }}
                        @elseif($trip->payment_status == 1)
                            {{ " -P " }}
                        @else
                            {{ "-UP" }}
                        @endif
                    </td>

                    <td style="text-align:center;">
                        {{ number_format((float) $trip->deposit, 2) }}

                        @if($trip->dpa_status == 2)
                            {{"-D"}}
                        @elseif($trip->dpa_status == 1)
                            {{"-P "}}
                        @else
                            {{"-UP "}}
                        @endif
                    </td>

                    <td style="text-align:center;">
                        {{ number_format((float) $trip->insurance_amt, 2) }}

                        @if($trip->insu_status == 2)
                            {{ "-D" }}
                        @elseif($trip->insu_status == 1)
                            {{ "-P" }}
                        @else
                            {{ "-UP" }}
                        @endif
                    </td>

                    <td style="text-align:center;">
                        {{ number_format((float) $trip->initial_fee, 2) }}

                        @if($trip->infee_status == 2)
                            {{ "-D" }}
                        @elseif($trip->infee_status == 1)
                            {{ "-P" }}
                        @else
                            {{ "-UP" }}
                        @endif
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
                                    <a href="javascript:void(0);" title="Reopen Booking"
                                        onclick="return reopenbooking('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-arrow-left52"></i>
                                        Reopen Booking
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" title="Vehicle Review Report"
                                        onclick="return getreviewpopup('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-clipboard3"></i>
                                        Vehicle Review Report
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" title="Message History"
                                        onclick="return getmessagehistory('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-comments"></i>
                                        Message History
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" title="Reopen Booking"
                                        onclick="return gettransactionlogs('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-coin-dollar"></i>
                                        Transaction logs
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" title="Scan Vehicle"
                                        onclick="return getVehicleScanRequestPopup('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-search4"></i>
                                        Scan Vehicle
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" title="Booking Notes"
                                        onclick="return getBookingNotes('{{ base64_encode($trip->id) }}');">
                                        <i class="icon-pencil7"></i>
                                        Booking Notes
                                    </a>
                                </li>
                            </ul>
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No bookings pending review.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (isset($nonreviews) && method_exists($nonreviews, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $nonreviews, 'limit' => $limit])
@endif