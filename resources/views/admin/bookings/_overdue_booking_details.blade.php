<div class="panel">
    <div class="panel-body">
        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
            <thead>
                <tr class="border-double border-bottom-danger">
                    <th style="text-align:center;">Booking#</th>
                    <th style="text-align:center;">Start Date Time</th>
                    <th style="text-align:center;">End Date Time</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($bookings as $booking)
                <tr class="border-top-primary">
                    <td style="text-align:center;">
                        {{ $booking->increment_id }}
                    </td>
                    <td style="text-align:center;">
                        {{ $booking->start_datetime ? \Carbon\Carbon::parse($booking->start_datetime)->format('m/d/Y h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $booking->end_datetime ? \Carbon\Carbon::parse($booking->end_datetime)->format('m/d/Y h:i A') : '--' }}
                    </td>
                </tr>
                @if($booking->extlogs && $booking->extlogs->count() > 0)
                <tr>
                    <td colspan="3">
                        <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-borderless table-responsive">
                            <thead>
                                <tr class="border-bottom-success">
                                    <th style="text-align:center;">Extended Date</th>
                                    <th style="text-align:center;">Note</th>
                                    <th style="text-align:center;">By</th>
                                    <th style="text-align:center;">Count Towards Extension</th>
                                    <th style="text-align:center;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($booking->extlogs as $list)
                                    <tr>
                                        <td style="text-align:center;">
                                            {{ ($list->ext_date && $list->ext_date != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($list->ext_date)->format('m/d/Y h:i A') : '--' }}
                                        </td>
                                        <td style="text-align:center;">
                                            {{ $list->note ?? '-' }}
                                        </td>
                                        <td style="text-align:center;">
                                            @if($list->ownerUser)
                                                {{ $list->ownerUser->first_name . ' ' . $list->ownerUser->last_name }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td style="text-align:center;">
                                            {{ $list->admin_count == 0 ? "Yes" : "No" }}
                                        </td>
                                        <td style="text-align:center;">
                                            {{ ($list->created && $list->created != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($list->created)->format('m/d/Y h:i A') : '--' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr> 
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>
