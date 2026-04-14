@php
$VehicleIssueType = $VehicleIssueType ?? [
    '1' => 'Accident', '2' => 'Roadside', '3' => 'Mechanical', '4' => 'Violation',
    '5' => 'Cleaning', '6' => 'Maintenance', '7' => 'Inspection Scan', '8' => 'Pending Booking',
];
@endphp
<div class="text-right mb-10">
    Showing {{ $vehicleissues->firstItem() }}–{{ $vehicleissues->lastItem() }} of {{ $vehicleissues->total() }}
</div>
<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive vehiclelist">
    <tr>
        <th valign="top" width="5%">#</th>
        <th valign="top" width="10%">Vehicle#</th>
        <th valign="top">Vehicle Name</th>
        <th valign="top">Driver</th>
        <th valign="top">Type</th>
        <th valign="top">Logged</th>
        <th valign="top">Status</th>
        <th valign="top">Action</th>
    </tr>
    @foreach($vehicleissues as $key)
    <tr id="tr-{{ $key->id }}">
        <td valign="top" width="10%">{{ $key->id }}</td>
        <td valign="top" width="10%">{{ $key->vehicle_unique_id }}</td>
        <td valign="top">{{ $key->vehicle_name }}</td>
        <td valign="top">{{ $key->first_name }} {{ $key->last_name }}</td>
        <td valign="top">
            @if($key->type == 4){{ $key->violationType ?? '' }} @endif
            {{ $VehicleIssueType[$key->type] ?? '' }}
        </td>
        <td valign="top">{{ \Carbon\Carbon::parse($key->created)->format('m/d/Y h:i A') }}</td>
        <td valign="top" class="dropdown-menu" id="td-{{ $key->id }}">
            <span class="dropdown-submenu">
                {{ $issueStatus[$key->status] ?? 'New' }}
                <a href="#"><i class="icon-gear"></i></a>
                <ul class="dropdown-menu dropdown-menu-sm">
                    @foreach($issueStatus as $k => $issueStats)
                        @if($k == $key->status) @continue @endif
                        <li><a href="#" onclick="changemystatus('{{ base64_encode($key->id) }}',{{ $k }})">{{ $issueStats }}</a></li>
                    @endforeach
                </ul>
            </span>
        </td>
        <td class="action">
            <a href="{{ url('/admin/vehicle_issues/delete/' . base64_encode($key->id)) }}" class="text-danger" onclick="return confirm('Are you sure you want to delete this record?')"><i class="icon-trash"></i></a>
            &nbsp;
            @if($key->type == 1)
                <a href="{{ url('/admin/vehicle_issues/accident/' . base64_encode($key->id)) }}"><i class="icon-pencil5"></i></a>
            @elseif($key->type == 2)
                <a href="{{ url('/admin/vehicle_issues/roadside/' . base64_encode($key->id)) }}"><i class="icon-pencil5"></i></a>
            @elseif($key->type == 5)
                <a href="{{ url('/admin/vehicle_issues/cleaning/' . base64_encode($key->id)) }}"><i class="icon-pencil5"></i></a>
            @elseif($key->type == 3)
                <a href="{{ url('/admin/vehicle_issues/mechanical/' . base64_encode($key->id)) }}"><i class="icon-pencil5"></i></a>
            @elseif($key->type == 6)
                <a href="{{ url('/admin/vehicle_issues/maintenance/' . base64_encode($key->id)) }}"><i class="icon-pencil5"></i></a>
            @elseif($key->type == 8)
                <a href="{{ url('/admin/vehicle_issues/pendingBooking/' . base64_encode($key->id)) }}"><i class="icon-pencil5"></i></a>
            @else
                <a href="{{ url('/admin/vehicle_issues/inspectionScan/' . base64_encode($key->id)) }}"><i class="icon-pencil5"></i></a>
            @endif
        </td>
    </tr>
    @endforeach
    <tr><td height="6" colspan="8"></td></tr>
</table>
<div class="text-center">{{ $vehicleissues->appends(request()->query())->links() }}</div>
