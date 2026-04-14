<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th style="text-align:center;">#</th>
            <th style="text-align:center;">Vehicle#</th>
            <th style="text-align:center;">Start Date</th>
            <th style="text-align:center;">Customer</th>
            <th style="text-align:center;">VIN #</th>
            <th style="text-align:center;">Tasks</th>
            <th style="text-align:center;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($bookings as $trip)
        <tr id="tripRow{{ $trip->id }}">
            <td style="text-align:center;">{{ $trip->id }}</td>
            <td style="text-align:center;"><a href="javascript:;" onclick="return false;">{{ $trip->vehicle_name }}</a></td>
            <td style="text-align:center;">{{ \Carbon\Carbon::parse($trip->start_datetime)->format('Y-m-d h:i A') }}</td>
            <td style="text-align:center;">{{ $trip->renter_first_name }} {{ $trip->renter_last_name }}</td>
            <td style="text-align:center;"><a href="javascript:;" onclick="return false;">{{ $trip->vin_no }}</a></td>
            <td class="text-left">
                <a href="javascript:void(0)" class="text" title="Status Checklist" onclick="return loadPickupChecklistPopup('{{ base64_encode($trip->id) }}');">Checklist</a>
            </td>
            <td style="text-align:center;">
                <a href="javascript:void(0)" title="Vehicle Condition report" onclick="return getVehicleScanRequestPopup('{{ base64_encode($trip->id) }}',1);"><i class="icon-magazine"></i></a>
                &nbsp;<a href="javascript:void(0)" title="License Scan" onclick="return getLicenseScanRequestPopup('{{ base64_encode($trip->id) }}');"><i class="icon-users"></i></a>
                &nbsp;<a href="javascript:void(0)" title="Upload Photo" onclick="return pickUpUploadPhotoPopup('{{ base64_encode($trip->id) }}');"><i class="icon-file-upload"></i></a>
            </td>
        </tr>
        @empty
        <tr><th colspan="12">No Booking Available!</th></tr>
        @endforelse
    </tbody>
</table>
{{ $bookings->links() }}
