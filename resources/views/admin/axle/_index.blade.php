<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th align="center" style="text-align:center;">Booking#</th>
            <th align="center" style="text-align:center;">Vehicle#</th>
            <th align="center" style="text-align:center;">Start Date</th>
            <th align="center" style="text-align:center;">End Date</th>
            <th align="center" style="text-align:center;">Customer</th>
            <th align="center" style="text-align:center;">Pending Booking#</th>
            <th align="center" style="text-align:center;">Policy Status</th>
            <th align="center" style="text-align:center;">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($records as $trip)
            @php
                $bg = 'bg-primary';
                $axleStatus = $trip->axle_status ?? 0;
                if ($axleStatus == 1) $bg = 'bg-info';
                if ($axleStatus == 2 || $axleStatus == 5) $bg = 'bg-success';
                if ($axleStatus == 3) $bg = 'bg-danger';
            @endphp
            <tr id="tripRow{{ $trip->order_deposit_rule_id }}">
                <td style="text-align:center;">{{ $trip->increment_id }}</td>
                <td style="text-align:center;">{{ $trip->vehicle_name }}</td>
                <td style="text-align:center;">{{ \Carbon\Carbon::parse($trip->start_datetime)->format('Y-m-d h:i A') }}</td>
                <td style="text-align:center;">{{ \Carbon\Carbon::parse($trip->end_datetime)->format('Y-m-d h:i A') }}</td>
                <td style="text-align:center;">{{ $trip->renter_id }}</td>
                <td style="text-align:center;">{{ $trip->vehicle_reservation_id }}</td>
                <td style="text-align:center;">
                    <span class="{{ $bg }} text-highlight">
                        {{ $policyStatus[$axleStatus] ?? '--' }}
                    </span>
                </td>
                <td>
                    <span class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-position="left" aria-expanded="true">
                            <i class="icon-cog7"></i>
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-solid pull-right">
                            @if ((empty($trip->axle_status) || in_array($axleStatus, [0, 3])) || (empty($trip->account_id) && empty($trip->policy)))
                                <li><a href="{{ config('app.url') }}admin/axle/axledocs/connect/{{ $trip->order_deposit_rule_id }}" title="Connect to Axle" class="btn btn-success" target="_blank">Connect to Axle <i class="icon-arrow-resize7 position-right"></i></a></li>
                            @endif
                            @if (empty($trip->axle_status) || in_array($axleStatus, [1, 2, 4]))
                                <li><a href="javascript:;" onclick="axlePolicyDisconnect('{{ $trip->order_id }}')" title="Disconnect" class="btn btn-danger" target="_blank">Disconnect<i class="icon-trash position-right"></i></a></li>
                            @endif
                            @if (!empty($trip->account_id) && empty($trip->policy))
                                <li><a href="javascript:;" onclick="getAxleAccountDetails('{{ $trip->order_id }}')" title="Pull Policy Details" class="btn btn-info">Account Details <i class="icon-spinner4 position-right"></i></a></li>
                            @endif
                            @if (!empty($trip->policy) && in_array($axleStatus, [2, 4, 5, 6]))
                                <li><a href="javascript:;" onclick="getAxlePolicyDetails('{{ $trip->order_id }}')" title="Pull Policy Details" class="btn btn-info">Policy Details <i class="icon-spinner4 position-right"></i></a></li>
                            @endif
                            @if (!empty($trip->policy))
                                <li><a href="javascript:;" onclick="axlePolicyDetailsPopup('{{ $trip->order_id }}')" title="Policy Checklist" class="btn btn-warning">Checklist <i class="icon-pencil7 position-right"></i></a></li>
                            @endif
                        </ul>
                    </span>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
{{ $records->links() }}
