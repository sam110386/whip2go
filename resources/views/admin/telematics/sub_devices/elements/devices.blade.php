@php
    $subid ??= '';
    $records ??= [];
    $limit ??= 50;
@endphp


@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit, 'position' => 'top'])

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Added On</th>
                <th style="text-align:center;">Device</th>
                <th style="text-align:center;">GPS #</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ $list->id }}
                    </td>
                    <td style="text-align:center;">
                        @if ($list->status == 1)
                            Active
                            <a href="/admin/telematics_sub_devices/status/{{ base64_encode($list->id) }}/0"
                                onclick="return confirm('Are you sure to update this Device?')">
                                <img src="/img/green2.jpg" alt="Status" title="Status">
                            </a>
                        @else
                            Inactive
                            <a href="/admin/telematics_sub_devices/status/{{ base64_encode($list->id) }}/1"
                                onclick="return confirm('Are you sure to update this Device?')">
                                <img src="/img/red3.jpg" alt="Status" title="Status">
                            </a>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        {{ $list->created != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($list->created)->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->device_name }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->gps_serialno }}
                    </td>
                    <td style="text-align:center;">
                        <a href="javascript:void(0)" title="Edit"
                            onclick="addDevice('{{ base64_encode($subid) }}','{{ base64_encode($list->id) }}')">
                            <i class="glyphicon glyphicon-edit"></i>
                        </a>
                        <a href="/admin/telematics_sub_devices/remove/{{ base64_encode($list->id) }}" title="Remove"
                            onclick="return confirm('Are you sure you want to delete this record?')">
                            <i class="glyphicon glyphicon-trash"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit])