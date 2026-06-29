@php
    $users ??= collect();
    $limit ??= 50;
@endphp

@if($users && $users->total() > 0)

    @include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit, 'position' => 'top'])

    <div class="table-responsive" style="margin: 10px 0px;">
        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
            <thead>
                <tr>
                    <th valign="top" width="5%">#</th>
                    <th valign="top">First Name</th>
                    <th valign="top">Last Name</th>
                    <th valign="top">Email</th>
                    <th valign="top">Contact#</th>
                    <th valign="top" width="15%">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $row)
                    <tr>
                        <td valign="top">
                            {{ data_get($row, 'id', '') }}
                        </td>
                        <td valign="top">
                            {{ data_get($row, 'first_name', '') }}
                        </td>
                        <td valign="top">
                            {{ data_get($row, 'last_name', '') }}
                        </td>
                        <td valign="top">
                            {{ data_get($row, 'email', '') }}
                        </td>
                        <td valign="top">
                            {{ data_get($row, 'contact_number', '')}}
                        </td>
                        <td class="action">

                            @if(empty(data_get($row, 'report.checkr_reportid', '')))
                                <a href="{{ url('admin/mvr_reports/checkr_status/' . base64_encode(data_get($row, 'id', ''))) }}"
                                    title="Request for Report">
                                    <i class="glyphicon glyphicon-hand-up"></i>
                                </a>
                            @endif

                            @if(!empty(data_get($row, 'report.checkr_reportid', '')))
                                <a href="javascript:void(0)" title="Individual Report"
                                    onclick="getReport('{{ data_get($row, 'report.checkr_reportid', '') }}')">
                                    <i class="glyphicon glyphicon-list-alt"></i>
                                </a>
                            @endif

                            @if(!empty(data_get($row, 'report.motor_vehicle_report_id', '')))
                                &nbsp;
                                <a href="javascript:void(0)" title="Vehicle Report"
                                    onclick="getVehicleReport('{{ data_get($row, 'report.motor_vehicle_report_id', '') }}')">
                                    <i class="icon icon-car"></i>
                                </a>
                            @endif

                            &nbsp;
                            <a href="javascript:void(0)" title="Active Booking"
                                onclick="getActiveBooking('{{ base64_encode(data_get($row, 'id', '')) }}')">
                                <i class="icon icon-stack3"></i>
                            </a>

                            @if(!empty(data_get($row, 'report.checkr_reportid', '')))
                                &nbsp;
                                <a href="{{ url('admin/mvr_reports/requestagain/' . base64_encode(data_get($row, 'id', ''))) }}"
                                    title="Request Report Again">
                                    <i class="icon icon-spinner11"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('partials.dispacher.paging_box', ['paginator' => $users, 'limit' => $limit])
@else
    <div class="table-responsive">
        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="borderTable">
            <tr>
                <td colspan="9" align="center">No record found</td>
            </tr>
        </table>
    </div>
@endif