<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
            <strong>{{ $phone }}</strong>
            <a href="#" class="btn label-success" onclick="loadnewmessgae('{{ $orderid }}')" style="float:right;">Send New Message</a>
        </section>

        <div style="width:100%; overflow: visible;">
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
                <thead>
                    <tr>
                        <th align="center" style="text-align:center;">#</th>
                        <th align="center" style="text-align:center;">Type</th>
                        <th align="center" style="text-align:center;">Text</th>
                        <th align="center" style="text-align:center;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($logs->isEmpty())
                        <tr id="set_hide">
                            <th colspan="4">No Record Available!</th>
                        </tr>
                    @else
                        @foreach ($logs as $i => $row)
                            <tr>
                                <td align="center" style="text-align:center;">{{ $i + 1 }}</td>
                                <td align="center" style="text-align:center;">{{ (int)$row->type === 1 ? 'Sent' : 'Received' }}</td>
                                <td align="center" style="text-align:center;">{{ $row->msg }}</td>
                                <td align="center" style="text-align:center;">{{ !empty($row->created) ? \Carbon\Carbon::parse($row->created)->format('Y-m-d h:i A') : '' }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </section>
</div>
