{{-- Cake `Elements/mvr_reports/_index.ctp` — AJAX target `#listing`. --}}
@if($users && $users->total() > 0)
    <div class="table-responsive" style="margin: 10px 0px;">
        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
            <thead>
                <tr>
                    @include('partials.dispacher.sortable_header', ['columns' => [
                        ['field' => 'id', 'title' => '#', 'style' => 'width: 5%;', 'sortable' => false],
                        ['field' => 'first_name', 'title' => 'First Name', 'sortable' => false],
                        ['field' => 'last_name', 'title' => 'Last Name', 'sortable' => false],
                        ['field' => 'email', 'title' => 'Email', 'sortable' => false],
                        ['field' => 'contact_number', 'title' => 'Contact#', 'sortable' => false],
                        ['field' => 'actions', 'title' => 'Actions', 'sortable' => false, 'style' => 'width: 15%;']
                    ]])
                </tr>
            </thead>
            <tbody>
                @foreach($users as $row)
                    <tr>
                        <td valign="top">{{ $row->id }}</td>
                        <td valign="top">{{ $row->first_name }}</td>
                        <td valign="top">{{ $row->last_name }}</td>
                        <td valign="top">{{ $row->email }}</td>
                        <td valign="top">{{ $row->contact_number }}</td>
                        <td class="action">
                            @if(empty($row->checkr_reportid))
                                <a href="{{ $basePath }}/checkr_status/{{ base64_encode((string) $row->id) }}" title="Request for Report"><i class="glyphicon glyphicon-hand-up"></i></a>
                            @endif
                            @if(!empty($row->checkr_reportid))
                                <a href="javascript:;" title="Individual Report" onclick="getReport('{{ $row->checkr_reportid }}')"><i class="glyphicon glyphicon-list-alt"></i></a>
                            @endif
                            @if(!empty($row->motor_vehicle_report_id))
                                &nbsp;<a href="javascript:;" title="Vehicle Report" onclick="getVehicleReport('{{ $row->motor_vehicle_report_id }}')"><i class="icon icon-car"></i></a>
                            @endif
                            &nbsp;<a href="javascript:;" title="Active Booking" onclick="getActiveBooking('{{ base64_encode((string) $row->id) }}')"><i class="icon icon-stack3"></i></a>
                            @if(!empty($row->checkr_reportid))
                                &nbsp;<a href="{{ $basePath }}/requestagain/{{ base64_encode((string) $row->id) }}" title="Request Report Again"><i class="icon icon-spinner11"></i></a>
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
        <table class="table table-bordered">
            <tr>
                <td colspan="6" class="text-center">
                    @if($users === null)
                        Users or user_reports table is not available.
                    @else
                        No record found
                    @endif
                </td>
            </tr>
        </table>
    </div>
@endif
