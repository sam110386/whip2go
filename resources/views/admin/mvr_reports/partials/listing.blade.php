{{-- Cake `Elements/mvr_reports/_index.ctp` — AJAX target `#listing`. --}}
@if($users && $users->total() > 0)
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <tr>
            <th valign="top" width="5%">#</th>
            <th valign="top">First Name</th>
            <th valign="top">Last Name</th>
            <th valign="top">Email</th>
            <th valign="top">Contact#</th>
            <th valign="top" width="15%">Actions</th>
        </tr>
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
        <tr><td colspan="9" style="height:6px;"></td></tr>
    </table>
    <section class="pagging" style="margin-top:12px; overflow:hidden;">
        <div style="width:40%; float:left;">
            <form name="frmRecordsPages" action="{{ $basePath }}/index" method="get">
                <input type="hidden" name="keyword" value="{{ $keyword }}">
                <input type="hidden" name="searchin" value="{{ $searchin }}">
                <label class="text-semibold">Show</label>
                <select name="Record[limit]" class="textbox pagingcls form-control" style="display:inline-block; width:auto; min-width:70px;" onchange="this.form.submit()">
                    @foreach ([25, 50, 100, 200] as $opt)
                        <option value="{{ $opt }}" @selected((int)($limit ?? 25) === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
                <span>&nbsp;Records per page</span>
            </form>
        </div>
        <div class="pull-right" style="margin-top:4px;">
            {{ $users->appends(['keyword' => $keyword, 'searchin' => $searchin, 'Record' => ['limit' => $limit]])->links() }}
        </div>
    </section>
@else
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="borderTable">
        <tr>
            <td colspan="9" align="center">
                @if($users === null)
                    Users or user_reports table is not available.
                @else
                    No record found
                @endif
            </td>
        </tr>
    </table>
@endif
