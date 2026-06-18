<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align: center;">#</th>
                <th style="text-align: left;">Status</th>
                <th style="text-align: left;">Note</th>
                <th style="text-align: left;">By</th>
                <th style="text-align: left;">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statuslogs as $statuslog)
                <tr>
                    <td style="text-align: center;">
                        {{ $loop->iteration }}
                    </td>
                    <td style="text-align: left;">
                        {{ data_get($allowedstatus, data_get($statuslog, 'status'), 'NA') }}
                    </td>
                    <td style="text-align: left;">
                        {{ data_get($statuslog, 'note') }}
                    </td>
                    <td style="text-align: left;">
                        {{ trim(data_get($statuslog, 'user.first_name', '') . ' ' . data_get($statuslog, 'user.last_name', '')) ?: 'System / Unknown' }}
                    </td>
                    <td style="text-align: left;">
                        @if(data_get($statuslog, 'created'))
                            {{ \Carbon\Carbon::parse(data_get($statuslog, 'created'))->timezone(session('default_timezone', config('app.timezone', 'UTC')))->format('Y-m-d h:i A') }}
                        @else
                            NA
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <th colspan="5" style="text-align: center;" class="text-muted">
                        Sorry, no record found!
                    </th>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>