<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">VIN</th>
                <th style="text-align:center;">Event</th>
                <th style="text-align:center;">Referer</th>
                <th style="text-align:center;">Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td style="text-align:center;">
                        {{ e($record['vin'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ e($record['event'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ e($record['referer'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ e($record['timestamp'] ?? '') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
