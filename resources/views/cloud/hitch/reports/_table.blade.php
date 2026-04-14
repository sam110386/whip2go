@if($reportlists->hasPages())
<div class="datatable-footer">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $reportlists->links() }}
    </div>
</div>
@endif

<table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th width="5"><input type="checkbox" id="selectAllChildCheckboxs" value="1"></th>
            <th style="width:105px;">Booking#</th>
            <th>Status</th>
            <th>Vehicle</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Duration</th>
            <th>Customer</th>
            <th>Mileage</th>
            <th>Total</th>
            <th>Insurance</th>
            <th>Toll</th>
            <th>DIA Fee</th>
            <th style="width:80px;">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($reportlists as $trip)
            @php
                $openTripDetails = "openTripDetails('" . base64_encode($trip->id) . "')";
                $loadsubbooking = $openTripDetails;
                if ($trip->auto_renew || $trip->pto) {
                    $loadsubbooking = "loadsubbooking('" . $trip->id . "')";
                    $openTripDetails = "openCombinedBookingDetails('" . base64_encode($trip->id) . "')";
                }
            @endphp
            <tr id="tr_{{ $trip->id }}">
                <td><input type="checkbox" name="select[{{ $trip->id }}]" value="{{ $trip->id }}"></td>
                @if ($trip->auto_renew)
                    <td onclick="{{ $loadsubbooking }}">
                        <i class="icon-forward position-left text-warning-400"></i>{{ $trip->increment_id }}
                    </td>
                @else
                    <td onclick="{{ $openTripDetails }}">{{ $trip->increment_id }}</td>
                @endif
                <td onclick="{{ $openTripDetails }}">
                    @if (!$trip->auto_renew)
                        @if ($trip->status == 3) Completed
                        @elseif ($trip->status == 2) Canceled
                        @else Incomplete
                        @endif
                    @endif
                </td>
                <td onclick="{{ $openTripDetails }}">{{ $trip->vehicle_name }}</td>
                <td onclick="{{ $openTripDetails }}">
                    {{ \Carbon\Carbon::parse($trip->start_datetime)->timezone($trip->timezone ?? 'UTC')->format('m/d/Y h:i A') }}
                </td>
                <td onclick="{{ $openTripDetails }}">
                    {{ \Carbon\Carbon::parse($trip->end_datetime)->timezone($trip->timezone ?? 'UTC')->format('m/d/Y h:i A') }}
                </td>
                <td onclick="{{ $openTripDetails }}">
                    {{ \Carbon\Carbon::parse($trip->start_datetime)->diffInDays(\Carbon\Carbon::parse($trip->end_datetime)) }}
                </td>
                <td onclick="{{ $openTripDetails }}">
                    {{ $trip->first_name }} {{ $trip->last_name }}
                </td>
                <td onclick="{{ $openTripDetails }}">
                    {{ $trip->status == 3 ? $trip->end_odometer - $trip->start_odometer : 0 }}
                </td>
                <td onclick="{{ $openTripDetails }}">{{ $trip->paid_amount }}</td>
                <td onclick="{{ $openTripDetails }}">{{ $trip->insurance_amt + $trip->dia_insu }}</td>
                <td onclick="{{ $openTripDetails }}">{{ $trip->toll }}</td>
                <td onclick="{{ $openTripDetails }}">{{ $trip->dia_fee }}</td>
                <td>
                    @if ($trip->status == 3)
                        <a href="javascript:void(0)" title="Review Images" onclick="reviewimages('{{ base64_encode($trip->id) }}')"><i class="icon-clipboard3"></i></a>
                    @endif
                    <a href="javascript:void(0)" title="Agreement Doc" onclick="return getagreement('{{ base64_encode($trip->id) }}');"><i class="icon-file-pdf"></i></a>
                </td>
            </tr>
        @endforeach
        <tr><td height="6" colspan="17"></td></tr>
    </tbody>
</table>

@if($reportlists->hasPages())
<div class="datatable-footer">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $reportlists->links() }}
    </div>
</div>
@endif
