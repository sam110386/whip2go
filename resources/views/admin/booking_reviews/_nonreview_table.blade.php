<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
            @include('partials.dispacher.sortable_header', [
                'columns' => [
                    ['title' => 'Booking#', 'field' => 'increment_id'],
                    ['title' => 'Vehicle#', 'field' => 'vehicle_unique_id'],
                    ['title' => 'Start', 'field' => 'start_datetime'],
                    ['title' => 'End', 'field' => 'end_datetime'],
                    ['title' => 'Customer', 'field' => 'renter_name'],
                    ['title' => 'Rent+Tax', 'sortable' => false, 'class' => 'text-right'],
                    ['title' => 'Deposit', 'sortable' => false, 'class' => 'text-right'],
                    ['title' => 'Insu. Fee', 'sortable' => false, 'class' => 'text-right'],
                    ['title' => 'Ini. Fee', 'sortable' => false, 'class' => 'text-right'],
                    ['title' => 'Actions', 'sortable' => false, 'class' => 'text-center'],
                ]
            ])
        </tr>
        </thead>
        <tbody>
            @forelse($nonreviews as $o)
                <tr id="tripRow{{ $o->id }}">
                    <td>{{ $o->increment_id }}</td>
                    <td>{{ $o->vehicle_unique_id }}</td>
                    <td>{{ date('Y-m-d h:i A', strtotime($o->start_datetime)) }}</td>
                    <td>{{ date('Y-m-d h:i A', strtotime($o->end_datetime)) }}</td>
                    <td>{{ $o->renter_name }}</td>
                    <td class="text-right">
                        {{ number_format((float)$o->rent + (float)$o->tax, 2) }}
                        @if($o->payment_status == 2) -D @elseif($o->payment_status == 1) -P @else -UP @endif
                    </td>
                    <td class="text-right">
                        {{ number_format((float)$o->deposit, 2) }}
                        @if($o->dpa_status == 2) -D @elseif($o->dpa_status == 1) -P @else -UP @endif
                    </td>
                    <td class="text-right">
                        {{ number_format((float)$o->insurance_amt, 2) }}
                        @if($o->insu_status == 2) -D @elseif($o->insu_status == 1) -P @else -UP @endif
                    </td>
                    <td class="text-right">
                        {{ number_format((float)$o->initial_fee, 2) }}
                        @if($o->infee_status == 2) -D @elseif($o->infee_status == 1) -P @else -UP @endif
                    </td>
                    <td class="text-center">
                        <ul class="icons-list">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <i class="icon-cog7"></i>
                                    <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="javascript:void(0);" onclick="reopenbooking('{{ base64_encode((string)$o->id) }}')"><i class="icon-arrow-left52"></i> Reopen Booking</a></li>
                                    <li><a href="javascript:void(0);" onclick="getreviewpopup('{{ base64_encode((string)$o->id) }}')"><i class="icon-clipboard3"></i> Vehicle Review Report</a></li>
                                    <li><a href="javascript:void(0);" onclick="getmessagehistory('{{ base64_encode((string)$o->id) }}')"><i class="icon-comments"></i> Message History</a></li>
                                    <li><a href="javascript:void(0);" onclick="gettransactionlogs('{{ base64_encode((string)$o->id) }}')"><i class="icon-coin-dollar"></i> Transaction logs</a></li>
                                    <li><a href="javascript:void(0);" onclick="getVehicleScanRequestPopup('{{ base64_encode((string)$o->id) }}')"><i class="icon-search4"></i> Scan Vehicle</a></li>
                                    <li><a href="javascript:void(0);" onclick="getBookingNotes('{{ base64_encode((string)$o->id) }}')"><i class="icon-pencil7"></i> Booking Notes</a></li>
                                </ul>
                            </li>
                        </ul>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center">No bookings pending review.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (isset($nonreviews) && method_exists($nonreviews, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $nonreviews])
@endif
