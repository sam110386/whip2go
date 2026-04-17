@if ($record)
    @php
        $bg = 'bg-primary';
        $axleStatus = $record->axle_status ?? 0;
        if ($axleStatus == 1) $bg = 'bg-info';
        if ($axleStatus == 2 || $axleStatus == 5) $bg = 'bg-success';
        if ($axleStatus == 3) $bg = 'bg-danger';
    @endphp
    <td style="text-align:center;">{{ $record->increment_id }}</td>
    <td style="text-align:center;">{{ $record->vehicle_name }}</td>
    <td style="text-align:center;">{{ \Carbon\Carbon::parse($record->start_datetime)->format('Y-m-d h:i A') }}</td>
    <td style="text-align:center;">{{ \Carbon\Carbon::parse($record->end_datetime)->format('Y-m-d h:i A') }}</td>
    <td style="text-align:center;">{{ $record->renter_id }}</td>
    <td style="text-align:center;">{{ $record->vehicle_reservation_id }}</td>
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
                @if ((empty($record->axle_status) || in_array($axleStatus, [0, 3])) || (empty($record->account_id) && empty($record->policy)))
                    <li><a href="{{ config('app.url') }}admin/axle/axledocs/connect/{{ $record->order_deposit_rule_id }}" title="Connect to Axle" class="btn btn-success" target="_blank">Connect to Axle <i class="icon-arrow-resize7 position-right"></i></a></li>
                @endif
                @if (empty($record->axle_status) || in_array($axleStatus, [1, 2, 4]))
                    <li><a href="javascript:;" onclick="axlePolicyDisconnect('{{ $record->order_id }}')" title="Disconnect" class="btn btn-danger" target="_blank">Disconnect<i class="icon-trash position-right"></i></a></li>
                @endif
                @if (!empty($record->account_id) && empty($record->policy))
                    <li><a href="javascript:;" onclick="getAxleAccountDetails('{{ $record->order_id }}')" title="Pull Policy Details" class="btn btn-info">Account Details <i class="icon-spinner4 position-right"></i></a></li>
                @endif
                @if (!empty($record->policy) && in_array($axleStatus, [2, 4, 5, 6]))
                    <li><a href="javascript:;" onclick="getAxlePolicyDetails('{{ $record->order_id }}')" title="Pull Policy Details" class="btn btn-info">Policy Details <i class="icon-spinner4 position-right"></i></a></li>
                @endif
                @if (!empty($record->policy))
                    <li><a href="javascript:;" onclick="axlePolicyDetailsPopup('{{ $record->order_id }}')" title="Policy Checklist" class="btn btn-warning">Checklist <i class="icon-pencil7 position-right"></i></a></li>
                @endif
            </ul>
        </span>
    </td>
@endif
