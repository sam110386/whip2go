<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">VIN</th>
                <th style="text-align:center;">Event</th>
                <th style="text-align:center;">Referer</th>
                <th style="text-align:center;">Source</th>
                <th style="text-align:center;">Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ipRecords as $row)
                <tr class="anchor">
                    <td style="text-align:center;">
                        {{ e($row['vin'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ e($row['event'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ e($row['referer'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ e($row['user'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ e($row['timestamp'] ?? '') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
